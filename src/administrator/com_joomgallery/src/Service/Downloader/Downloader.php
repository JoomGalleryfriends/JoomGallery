<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Downloader;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use Joomgallery\Component\Joomgallery\Administrator\Model\CollectionModel;
use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
use Joomla\Session\SessionInterface;

/**
 * Shared service for protected image delivery and collection archive creation.
 *
 * Ordinary content access remains application-specific and is completed before
 * this service is called. The service builds trusted manifests, obtains optional
 * locked-resource decisions, enforces native terms and prepares deliverables.
 *
 * @package JoomGallery
 * @since   __DEPLOY_VERSION__
 */
class Downloader implements DownloaderInterface
{
  /** @var JoomgalleryComponent */
  private JoomgalleryComponent $component;

  /** @var MVCFactoryInterface */
  private MVCFactoryInterface $mvcFactory;

  /** @var CMSApplicationInterface */
  private CMSApplicationInterface $app;

  /** @var SessionInterface */
  private SessionInterface $session;

  /**
   * Constructor.
   *
   * @param   JoomgalleryComponent     $component   Booted component instance.
   * @param   MVCFactoryInterface      $mvcFactory  Component MVC factory.
   * @param   CMSApplicationInterface  $app         Current application.
   * @param   SessionInterface         $session     Current Joomla session.
   */
  public function __construct(
      JoomgalleryComponent $component,
      MVCFactoryInterface $mvcFactory,
      CMSApplicationInterface $app,
      SessionInterface $session
  ) {
    $this->component  = $component;
    $this->mvcFactory = $mvcFactory;
    $this->app        = $app;
    $this->session    = $session;
  }

  /**
   * Authorize one image and imagetype for a delivery purpose.
   *
   * @param   object  $image    Validated image record.
   * @param   string  $type     Requested imagetype.
   * @param   string  $purpose  Delivery purpose.
   *
   * @return  array  Normalized authorization decision.
   */
  public function authorizeImage(object $image, string $type, string $purpose = 'download'): array
  {
    $type    = strtolower(trim($type));
    $purpose = strtolower(trim($purpose));

    if((int) ($image->id ?? 0) <= 0 || $type === '')
    {
      return $this->decision(self::STATUS_DENIED, Text::_('COM_JOOMGALLERY_LICENSE_INVALID_REQUEST'));
    }

    // Reject unavailable download variants before consulting a paywall. An
    // invalid output must never be presented as something payment can fix.
    if($purpose === 'download')
    {
      /** @var ImageModel $model */
      $model = $this->mvcFactory->createModel('Image', 'Administrator', ['ignore_request' => true]);

      if(!\in_array($type, $model->getDownloadTypes($image), true))
      {
        return $this->decision(self::STATUS_DENIED, Text::_('COM_JOOMGALLERY_DOWNLOAD_NOT_ALLOWED'));
      }
    }

    return $this->authorizeManifest([$this->createManifestItem($image, $type)], $purpose);
  }

  /**
   * Authorize every image represented by a prepared collection archive.
   *
   * Cached archives are authorized from their stored manifest rather than the
   * collection's current mappings, preserving the exact archived selection.
   *
   * @param   array          $archive  Prepared archive data.
   * @param   string         $type     Requested imagetype.
   * @param   callable|null  $access   Application-specific ordinary access callback.
   *
   * @return  array  Normalized authorization decision.
   */
  public function authorizeArchive(array $archive, string $type, ?callable $access = null): array
  {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($archive['image_ids'] ?? [])))));

    if(!$ids)
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_EMPTY'), 404);
    }

    /** @var CollectionModel $model */
    $model  = $this->mvcFactory->createModel('Collection', 'Administrator', ['ignore_request' => true]);
    $images = $model->getDownloadImagesByIds($ids);

    if(\count($images) !== \count($ids))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
    }

    $byId = [];

    foreach($images as $image)
    {
      $byId[(int) $image->id] = $image;
    }

    $manifest = [];

    foreach($ids as $id)
    {
      $image = $byId[$id] ?? null;

      if(!$image || ($access !== null && !$access($image, $type)))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
      }

      $manifest[] = $this->createManifestItem($image, $type);
    }

    return $this->authorizeManifest($manifest, 'download');
  }

  /**
   * Store acceptance of the currently configured native license terms.
   *
   * @return  void
   */
  public function acceptTerms(): void
  {
    $this->session->set($this->getTermsSessionKey(), $this->getTermsFingerprint());
  }

  /**
   * Return the configured native license terms.
   *
   * @return  string  Terms text or language key.
   */
  public function getTerms(): string
  {
    $config = $this->getGlobalConfig();
    $terms  = trim((string) $config->get('jg_license_terms_text', ''));

    return $terms !== '' ? $terms : 'COM_JOOMGALLERY_LICENSE_TERMS_DEFAULT';
  }

  /**
   * Prepare a processed image resource for attachment delivery.
   *
   * @param   object  $image  Image record.
   * @param   string  $type   Requested imagetype.
   *
   * @return  array{0: object, 1: resource}  File information and readable resource.
   */
  public function prepareImageDownload(object $image, string $type): array
  {
    /** @var ImageModel $model */
    $model = $this->mvcFactory->createModel('Image', 'Administrator', ['ignore_request' => true]);

    return $model->prepareDownloadResource($image, $type);
  }

  /**
   * Return an existing collection archive or generate and register a new one.
   *
   * @param   int            $collectionId  Collection ID.
   * @param   string         $type          Requested imagetype.
   * @param   boolean        $forceRecreate Force archive regeneration.
   * @param   callable|null  $access        Application-specific ordinary access callback.
   *
   * @return  array{path: string, name: string, image_ids: array}  Archive data.
   */
  public function prepareCollectionDownload(
      int $collectionId,
      string $type,
      bool $forceRecreate = false,
      ?callable $access = null
  ): array {
    $type = strtolower(trim($type));

    if($collectionId <= 0 || $type === '' || !preg_match('/^[a-z0-9_-]+$/', $type))
    {
      throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_INVALID_REQUEST'), 400);
    }

    /** @var CollectionModel $collectionModel */
    $collectionModel = $this->mvcFactory->createModel('Collection', 'Administrator', ['ignore_request' => true]);
    $collection      = $collectionModel->getTable();

    if(!$collection->load($collectionId))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'), 404);
    }

    $profile = $this->mvcFactory->createTable('User', 'Administrator');

    if(!$profile->load((int) $collection->userid))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_OWNER'), 404);
    }

    $config = $this->getGlobalConfig();
    /** @var ImageModel $imageModel */
    $imageModel = $this->mvcFactory->createModel('Image', 'Administrator', ['ignore_request' => true]);

    if(!\in_array($type, $imageModel->getConfiguredDownloadTypes($config), true))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_DOWNLOAD_NOT_ALLOWED'), 403);
    }

    $retentionHours = max(1, (int) $config->get('jg_download_zip_retention', 24));
    $directory      = Path::clean(
        rtrim((string) $this->app->get('tmp_path'), '/\\')
        . \DIRECTORY_SEPARATOR . 'joomgallery'
        . \DIRECTORY_SEPARATOR . 'collection-downloads'
        . \DIRECTORY_SEPARATOR . (int) $profile->id
    );

    if(!is_dir($directory) && !Folder::create($directory))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED'));
    }

    $this->protectArchiveDirectory($directory);

    $cacheKey    = 'collection-' . $collectionId . '-' . $type;
    $archiveName = $this->getCollectionArchiveName($collection, $type);
    $archivePath = Path::clean($directory . \DIRECTORY_SEPARATOR . $cacheKey . '.zip');
    $lock        = fopen($directory . \DIRECTORY_SEPARATOR . '.profile.lock', 'c');

    if($lock === false || !flock($lock, \LOCK_EX))
    {
      if(\is_resource($lock))
      {
        fclose($lock);
      }

      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED'));
    }

    try
    {
      $profile->load((int) $collection->userid);
      $files   = new Registry((string) $profile->files);
      $entries = (array) $files->get('collection_downloads', []);
      $entries = $this->purgeExpiredDownloadArchives($entries, $directory);
      $entry   = isset($entries[$cacheKey]) ? (array) $entries[$cacheKey] : [];

      $cachedPath = !empty($entry['path']) ? Path::clean((string) $entry['path']) : '';
      $cacheRoot  = rtrim(Path::clean($directory), '/\\') . \DIRECTORY_SEPARATOR;

      if(!$forceRecreate
        && $cachedPath !== ''
        && str_starts_with(strtolower($cachedPath), strtolower($cacheRoot))
        && is_file($cachedPath)
        && is_readable($cachedPath)
        && isset($entry['image_ids'])
        && \is_array($entry['image_ids']))
      {
        $files->set('collection_downloads', $entries);
        $this->storeProfileFiles($profile, $files);

        return [
          'path'      => $cachedPath,
          'name'      => $archiveName,
          'image_ids' => array_values(array_map('intval', $entry['image_ids'])),
        ];
      }

      $temporaryPath = $archivePath . '.' . bin2hex(random_bytes(8)) . '.part';
      $zip           = new \ZipArchive();

      if($zip->open($temporaryPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true)
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED'));
      }

      try
      {
        $images = $collectionModel->getCollectionDownloadImages($collectionId);

        if(!$images)
        {
          throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_EMPTY'), 404);
        }

        $imageIds = [];

        foreach($images as $image)
        {
          // A collection archive is all-or-nothing. Ordinary access failure of
          // one member must not silently produce a different partial archive.
          if($access !== null && !$access($image, $type))
          {
            throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
          }

          [$fileInfo, $resource] = $imageModel->prepareDownloadResource($image, $type);

          try
          {
            $content = stream_get_contents($resource);

            if($content === false
              || !$zip->addFromString((int) $image->id . '-' . basename((string) $image->filename), $content))
            {
              throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED'));
            }

            $imageIds[] = (int) $image->id;
          }
          finally
          {
            if(\is_resource($resource))
            {
              fclose($resource);
            }
          }
        }
      }
      catch(\Throwable $e)
      {
        $zip->close();
        @unlink($temporaryPath);
        throw $e;
      }

      $closed = $zip->close();

      if(is_file($archivePath))
      {
        @unlink($archivePath);
      }

      if(!$closed || !@rename($temporaryPath, $archivePath))
      {
        @unlink($temporaryPath);
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED'));
      }

      $created            = time();
      $entries[$cacheKey] = [
        'collection_id' => $collectionId,
        'imagetype'     => $type,
        'path'          => $archivePath,
        'created'       => $created,
        'expires'       => $created + ($retentionHours * 3600),
        'image_ids'     => $imageIds,
      ];
      $files->set('collection_downloads', $entries);
      $this->storeProfileFiles($profile, $files);

      return ['path' => $archivePath, 'name' => $archiveName, 'image_ids' => $imageIds];
    }
    finally
    {
      flock($lock, \LOCK_UN);
      fclose($lock);
    }
  }

  /**
   * Increment image download counters after successful authorization.
   *
   * @param   int|array  $imageIds  One image ID or a list of IDs.
   *
   * @return  void
   */
  public function recordDownloads($imageIds): void
  {
    /** @var ImageModel $model */
    $model = $this->mvcFactory->createModel('Image', 'Administrator', ['ignore_request' => true]);
    $model->incrementDownloads($imageIds);
  }

  /**
   * Open the packaged placeholder returned for locked inline images.
   *
   * @return  array{0: object, 1: resource, 2: string}  Metadata, resource and logical path.
   */
  public function getLockedImageResource(): array
  {
    $path = Path::clean(JPATH_ROOT . '/media/com_joomgallery/images/locked-image.svg');

    if(!is_file($path) || !is_readable($path))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_LICENSE_LOCKED_IMAGE_NOT_FOUND'), 500);
    }

    $resource = fopen($path, 'rb');

    if($resource === false)
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_LICENSE_LOCKED_IMAGE_NOT_FOUND'), 500);
    }

    $fileInfo = (object) [
      'mime_type' => 'image/svg+xml',
      'size'      => (int) filesize($path),
      'filename'  => basename($path),
    ];

    return [$fileInfo, $resource, 'media/com_joomgallery/images/locked-image.svg'];
  }

  /**
   * Authorize a trusted image/variant manifest.
   *
   * @param   array   $manifest  Canonical image/variant manifest.
   * @param   string  $purpose   Delivery purpose.
   *
   * @return  array  Normalized authorization decision.
   */
  private function authorizeManifest(array $manifest, string $purpose): array
  {
    $purpose = strtolower(trim($purpose));

    if(!$manifest || !\in_array($purpose, ['display', 'download'], true))
    {
      return $this->decision(self::STATUS_DENIED, Text::_('COM_JOOMGALLERY_LICENSE_INVALID_REQUEST'));
    }

    // Administrator delivery keeps its existing application-specific bypass.
    if(!$this->app->isClient('site'))
    {
      return $this->decision(self::STATUS_ALLOWED);
    }

    $authorization = (object) [
      'status'       => self::STATUS_ALLOWED,
      'message'      => '',
      'purchase_url' => '',
      'provider'     => '',
    ];

    // The future JoomGallery locked-resource plugin is the only adapter that
    // knows about Sesame. No Sesame class or table is referenced here.
    PluginHelper::importPlugin('lockedresources');
    $arguments = [$this->app->getIdentity(), $manifest, $purpose, &$authorization];
    $results   = $this->app->triggerEvent('onJoomAuthorizeDownload', $arguments);
    $decision  = $this->normalizeProviderDecision($authorization, $results);

    if($decision['status'] !== self::STATUS_ALLOWED)
    {
      return $decision;
    }

    if($this->nativeTermsRequired() && !$this->termsAccepted())
    {
      return $this->decision(self::STATUS_TERMS_REQUIRED, Text::_('COM_JOOMGALLERY_LICENSE_TERMS_REQUIRED'));
    }

    return $decision;
  }

  /**
   * Create one canonical manifest item from trusted gallery data.
   *
   * @param   object  $image  Image record.
   * @param   string  $type   Requested imagetype.
   *
   * @return  array  Canonical manifest item.
   */
  private function createManifestItem(object $image, string $type): array
  {
    return [
      'image_id'     => (int) ($image->id ?? 0),
      'category_id'  => (int) ($image->catid ?? 0),
      'variant_key'  => strtolower(trim($type)),
      'imagetype'    => strtolower(trim($type)),
    ];
  }

  /**
   * Normalize mutable and returned provider decisions into one contract.
   *
   * @param   object  $authorization  Mutable authorization object.
   * @param   array   $results        Plugin return values.
   *
   * @return  array  Normalized decision.
   */
  private function normalizeProviderDecision(object $authorization, array $results): array
  {
    $candidate = [
      'status'       => strtolower((string) ($authorization->status ?? self::STATUS_ALLOWED)),
      'message'      => (string) ($authorization->message ?? ''),
      'purchase_url' => (string) ($authorization->purchase_url ?? ''),
      'provider'     => (string) ($authorization->provider ?? ''),
    ];

    $priority = [
      self::STATUS_ALLOWED              => 1,
      self::STATUS_TERMS_REQUIRED       => 2,
      self::STATUS_PURCHASE_REQUIRED    => 3,
      self::STATUS_DENIED               => 4,
      self::STATUS_PROVIDER_UNAVAILABLE => 5,
    ];

    foreach($results as $result)
    {
      if($result === false)
      {
        if(($priority[self::STATUS_DENIED] ?? 0) >= ($priority[$candidate['status']] ?? 0))
        {
          $candidate['status'] = self::STATUS_DENIED;
        }

        continue;
      }

      if(\is_string($result))
      {
        $result = strtolower($result);
        $result = isset($priority[$result]) ? $result : self::STATUS_PROVIDER_UNAVAILABLE;

        if($priority[$result] >= ($priority[$candidate['status']] ?? 0))
        {
          $candidate['status'] = $result;
        }

        continue;
      }

      if(\is_array($result) || \is_object($result))
      {
        $result = (array) $result;

        if(isset($result['status']))
        {
          $result['status'] = strtolower((string) $result['status']);
        }

        if(isset($result['status']) && !isset($priority[(string) $result['status']]))
        {
          $result['status']  = self::STATUS_PROVIDER_UNAVAILABLE;
          $result['message'] = Text::_('COM_JOOMGALLERY_LICENSE_PROVIDER_UNAVAILABLE');
        }

        if(isset($result['status'])
          && ($priority[(string) $result['status']] ?? 0) < ($priority[$candidate['status']] ?? 0))
        {
          continue;
        }

        foreach(['status', 'message', 'purchase_url', 'provider'] as $key)
        {
          if(isset($result[$key]))
          {
            $candidate[$key] = (string) $result[$key];
          }
        }
      }
    }

    $valid = [
      self::STATUS_ALLOWED,
      self::STATUS_TERMS_REQUIRED,
      self::STATUS_PURCHASE_REQUIRED,
      self::STATUS_DENIED,
      self::STATUS_PROVIDER_UNAVAILABLE,
    ];

    if(!\in_array($candidate['status'], $valid, true))
    {
      $candidate['status']  = self::STATUS_PROVIDER_UNAVAILABLE;
      $candidate['message'] = Text::_('COM_JOOMGALLERY_LICENSE_PROVIDER_UNAVAILABLE');
    }

    return $candidate;
  }

  /**
   * Build a normalized decision.
   *
   * @param   string  $status   Decision status.
   * @param   string  $message  Optional user-facing message.
   *
   * @return  array  Decision structure.
   */
  private function decision(string $status, string $message = ''): array
  {
    return ['status' => $status, 'message' => $message, 'purchase_url' => '', 'provider' => ''];
  }

  /**
   * Return whether native terms are enabled for protected resource delivery.
   *
   * @return  boolean  True when acceptance is required.
   */
  private function nativeTermsRequired(): bool
  {
    return (int) $this->getGlobalConfig()->get('jg_license_terms_required', 0) === 1;
  }

  /**
   * Return whether the current session accepted the active terms version.
   *
   * @return  boolean  True when current terms have been accepted.
   */
  private function termsAccepted(): bool
  {
    return hash_equals(
        $this->getTermsFingerprint(),
        (string) $this->session->get($this->getTermsSessionKey(), '')
    );
  }

  /**
   * Build a session key scoped to the current Joomla identity.
   *
   * @return  string  Per-user terms acceptance key.
   */
  private function getTermsSessionKey(): string
  {
    return 'com_joomgallery.downloader.terms.' . (int) $this->app->getIdentity()->id;
  }

  /**
   * Create a stable fingerprint that invalidates acceptance after terms change.
   *
   * @return  string  SHA-256 terms fingerprint.
   */
  private function getTermsFingerprint(): string
  {
    $config  = $this->getGlobalConfig();
    $version = (string) $config->get('jg_license_terms_version', '1');

    return hash('sha256', $version . "\0" . $this->getTerms());
  }

  /**
   * Load the effective global configuration.
   *
   * @return  object  Config service instance.
   */
  private function getGlobalConfig(): object
  {
    $this->component->createConfig();

    return $this->component->getConfig();
  }

  /**
   * Remove expired archive files and metadata.
   *
   * @param   array   $entries    Registered archive metadata.
   * @param   string  $directory  Absolute owner cache directory.
   *
   * @return  array  Remaining metadata entries.
   */
  private function purgeExpiredDownloadArchives(array $entries, string $directory): array
  {
    $now    = time();
    $prefix = rtrim(Path::clean($directory), '/\\') . \DIRECTORY_SEPARATOR;

    foreach($entries as $key => $entry)
    {
      $entry       = (array) $entry;
      $path        = !empty($entry['path']) ? Path::clean((string) $entry['path']) : '';
      $insideCache = $path !== '' && str_starts_with(strtolower($path), strtolower($prefix));

      if((int) ($entry['expires'] ?? 0) > $now && $insideCache && is_file($path))
      {
        $entries[$key] = $entry;
        continue;
      }

      if($insideCache && is_file($path))
      {
        @unlink($path);
      }

      unset($entries[$key]);
    }

    return $entries;
  }

  /**
   * Persist collection archive metadata on a JoomGallery user profile.
   *
   * @param   object    $profile  User table instance.
   * @param   Registry  $files    Updated files registry.
   *
   * @return  void
   */
  private function storeProfileFiles(object $profile, Registry $files): void
  {
    $profile->files = (string) $files;

    if(!$profile->store())
    {
      throw new \RuntimeException($profile->getError() ?: Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED'));
    }
  }

  /**
   * Build a safe browser filename for a collection archive.
   *
   * @param   object  $collection  Collection record.
   * @param   string  $type        Included imagetype.
   *
   * @return  string  Archive filename.
   */
  private function getCollectionArchiveName(object $collection, string $type): string
  {
    $alias = OutputFilter::stringURLSafe((string) ($collection->alias ?: $collection->title));
    $alias = $alias !== '' ? $alias : 'collection-' . (int) $collection->id;

    return $alias . '-' . $type . '.zip';
  }

  /**
   * Install web-server deny files in a potentially web-accessible temp path.
   *
   * @param   string  $directory  Owner archive directory.
   *
   * @return  void
   */
  private function protectArchiveDirectory(string $directory): void
  {
    $root      = dirname($directory);
    $htaccess  = $root . \DIRECTORY_SEPARATOR . '.htaccess';
    $webConfig = $root . \DIRECTORY_SEPARATOR . 'web.config';

    if(!is_file($htaccess))
    {
      File::write($htaccess, "Require all denied\nDeny from all\n");
    }

    if(!is_file($webConfig))
    {
    File::write(
        $webConfig,
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
          . "<configuration><system.webServer><security><authorization>"
          . "<remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/>"
          . "</authorization></security></system.webServer></configuration>\n"
    );
    }
  }
}
