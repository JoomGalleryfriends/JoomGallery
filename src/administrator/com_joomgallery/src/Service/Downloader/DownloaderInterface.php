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

/**
 * Coordinates protected image and collection delivery.
 *
 * @package JoomGallery
 * @since   __DEPLOY_VERSION__
 */
interface DownloaderInterface
{
  public const STATUS_ALLOWED              = 'allowed';
  public const STATUS_TERMS_REQUIRED       = 'terms_required';
  public const STATUS_PURCHASE_REQUIRED    = 'purchase_required';
  public const STATUS_DENIED               = 'denied';
  public const STATUS_PROVIDER_UNAVAILABLE = 'provider_unavailable';

  /**
   * Authorize one image and imagetype for a delivery purpose.
   *
   * @param   object  $image    Validated image record.
   * @param   string  $type     Requested imagetype.
   * @param   string  $purpose  Delivery purpose, either display or download.
   *
   * @return  array  Normalized authorization decision.
   */
  public function authorizeImage(object $image, string $type, string $purpose = 'download'): array;

  /**
   * Authorize all images represented by a prepared collection archive.
   *
   * @param   array          $archive  Prepared archive data containing image IDs.
   * @param   string         $type     Requested imagetype.
   * @param   callable|null  $access   Application-specific ordinary access callback.
   *
   * @return  array  Normalized authorization decision.
   */
  public function authorizeArchive(array $archive, string $type, ?callable $access = null): array;

  /**
   * Store acceptance of the currently configured native license terms.
   *
   * @return  void
   */
  public function acceptTerms(): void;

  /**
   * Return the configured native license terms.
   *
   * @return  string  Terms text or language key.
   */
  public function getTerms(): string;

  /**
   * Prepare a processed image resource for attachment delivery.
   *
   * @param   object  $image  Image record.
   * @param   string  $type   Requested imagetype.
   *
   * @return  array{0: object, 1: resource}  File information and readable resource.
   */
  public function prepareImageDownload(object $image, string $type): array;

  /**
   * Inspect the image IDs of a cached or newly requested collection ZIP.
   *
   * @return  array  Manifest containing image_ids.
   */
  public function inspectCollectionDownload(int $collectionId, string $type, bool $forceRecreate = false): array;

  /**
   * Return an existing collection archive or generate a new one.
   *
   * @param   int            $collectionId  Collection ID.
   * @param   string         $type          Requested imagetype.
   * @param   boolean        $forceRecreate Force regeneration of an existing archive.
   * @param   callable|null  $access        Application-specific ordinary access callback.
   * @param   array|null      $expectedIds   Previously authorized image IDs.
   *
   * @return  array{path: string, name: string, image_ids: array}  Archive data.
   */
  public function prepareCollectionDownload(
      int $collectionId,
      string $type,
      bool $forceRecreate = false,
      ?callable $access = null,
      ?array $expectedIds = null
  ): array;

  /**
   * Increment download counters after a resource is authorized for delivery.
   *
   * @param   int|array  $imageIds  One image ID or a list of image IDs.
   *
   * @return  void
   */
  public function recordDownloads($imageIds): void;

  /**
   * Open the packaged placeholder returned for locked inline images.
   *
   * @return  array{0: object, 1: resource, 2: string}  Metadata, resource and logical path.
   */
  public function getLockedImageResource(): array;
}
