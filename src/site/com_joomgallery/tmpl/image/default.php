<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

\defined('_JEXEC') || die;

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;

// image params
$image_type          = $this->params['configs']->get('jg_detail_view_type_image', 'detail', 'STRING');
$show_title          = $this->params['configs']->get('jg_detail_view_show_title', 1, 'INT');
$title_position      = $this->params['configs']->get('jg_detail_view_title_position', 'below', 'STRING');
$header_above        = $title_position === 'above';
$show_category       = $this->params['configs']->get('jg_detail_view_show_category', 1, 'INT');
$show_description    = $this->params['configs']->get('jg_detail_view_show_description', 1, 'INT');
$show_imgdate        = $this->params['configs']->get('jg_detail_view_show_imgdate', 1, 'INT');
$show_imgauthor      = $this->params['configs']->get('jg_detail_view_show_imgauthor', 1, 'INT');
$show_created_by     = $this->params['configs']->get('jg_detail_view_show_created_by', 1, 'INT');
$show_votes          = $this->params['configs']->get('jg_detail_view_show_votes', 1, 'INT');
$show_rating         = $this->params['configs']->get('jg_detail_view_show_rating', 1, 'INT');
$show_hits           = $this->params['configs']->get('jg_detail_view_show_hits', 1, 'INT');
$show_downloads      = $this->params['configs']->get('jg_detail_view_show_downloads', 1, 'INT');
$show_tags           = $this->params['configs']->get('jg_detail_view_show_tags', 1, 'INT');
$show_comments       = $this->params['configs']->get('jg_detail_view_show_comments', 1, 'INT');
$show_copyright      = $this->params['configs']->get('jg_detail_view_show_copyright', 1, 'INT');
$show_metadata       = $this->params['configs']->get('jg_detail_view_show_metadata', 1, 'INT');
$show_image_size     = $this->params['configs']->get('jg_detail_view_show_image_size', 1, 'INT');
$show_voting_btn     = $this->params['configs']->get('jg_detail_view_show_voting_btn', 1, 'INT');
$show_download_btn   = $this->params['configs']->get('jg_detail_view_show_download_btn', 1, 'INT');
$show_comment_btn    = $this->params['configs']->get('jg_detail_view_show_comment_btn', 1, 'INT');
$show_favorite_btn   = $this->params['configs']->get('jg_detail_view_show_favorite_btn', 1, 'INT');
$show_share_btn      = $this->params['configs']->get('jg_detail_view_show_share_btn', 1, 'INT');
$meta_keys           = $this->params['configs']->get('jg_detail_view_metadata_keys', 'Model,Make,DateTimeOriginal,DateTime', 'STRING');
$frontend_framework  = $this->params['configs']->get('jg_gallery_view_framework', 'joomla', 'STRING');
$is_yootheme         = $frontend_framework === 'yootheme';
$button_class        = $is_yootheme ? 'uk-button uk-button-default uk-button-small' : 'btn btn-outline-primary';
$module_class        = $is_yootheme ? 'uk-card uk-card-default uk-card-body uk-margin' : 'card';
$module_header_class = $is_yootheme ? 'uk-card-title ' : 'card-header ';
$header_class        = $is_yootheme ? 'uk-flex uk-flex-between uk-flex-top uk-flex-wrap uk-margin-medium' : 'd-flex flex-column flex-md-row justify-content-between gap-3 py-4';
$identity_class      = $is_yootheme ? 'uk-flex uk-flex-top' : 'd-flex gap-3 align-items-start';
$avatar_class        = $is_yootheme ? 'jg-detail__avatar uk-flex uk-flex-middle uk-flex-center uk-background-primary uk-light uk-text-bold uk-border-circle uk-margin-small-right' : 'jg-detail__avatar d-inline-flex align-items-center justify-content-center rounded bg-primary text-white fw-bold';
$title_class         = $is_yootheme ? 'uk-heading-small uk-margin-remove-bottom' : 'h2 mb-1';
$muted_class         = $is_yootheme ? 'uk-text-meta' : 'text-body-secondary';
$stage_class         = $is_yootheme ? 'jg-detail__stage uk-position-relative uk-flex uk-flex-middle uk-flex-center uk-padding-small' : 'jg-detail__stage position-relative d-flex align-items-center justify-content-center p-3';
$image_wrapper_class = $is_yootheme ? 'jg-detail__protected uk-position-relative uk-width-1-1' : 'jg-detail__protected position-relative mw-100';
$image_class         = $is_yootheme ? 'jg-detail__image uk-responsive-width uk-display-block uk-margin-auto' : 'jg-detail__image img-fluid d-block mx-auto';
$image_overlay_class = $is_yootheme ? 'jg-detail__image-overlay uk-position-cover' : 'jg-detail__image-overlay position-absolute top-0 start-0 w-100 h-100';
$content_class       = $is_yootheme ? 'uk-container uk-padding-small' : 'container-fluid py-4';
$action_class        = $is_yootheme ? 'uk-flex uk-flex-wrap uk-flex-middle uk-padding-small' : 'd-flex flex-wrap align-items-center gap-2 pb-3 border-bottom';
$secondary_button    = $is_yootheme ? 'uk-button uk-button-default uk-button-small' : 'btn btn-outline-secondary';
$primary_button      = $is_yootheme ? 'uk-button uk-button-primary uk-button-small' : 'btn btn-outline-primary';
$danger_button       = $is_yootheme ? 'uk-button uk-button-danger uk-button-small' : 'btn btn-outline-danger';
$action_item_class   = $is_yootheme ? 'uk-margin-small-right uk-margin-small-bottom' : '';
$info_list_class     = $is_yootheme ? 'uk-description-list uk-description-list-divider uk-margin-medium-top' : 'row mb-0 pt-4 border-top';
$info_term_class     = $is_yootheme ? '' : 'col-sm-4 col-lg-3';
$info_value_class    = $is_yootheme ? '' : 'col-sm-8 col-lg-9';

$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useScript('com_joomgallery.image-protection');
$wa->useStyle('com_joomgallery.jg-icon-font');
$wa->useScript('bootstrap.modal');

HTMLHelper::_(
    'bootstrap.popover',
    '.jg-detail [data-jg-feature-popover]',
    [
      'trigger'   => 'hover focus',
      'container' => 'body',
      'boundary'  => 'clippingParents',
      'html'      => true,
    ]
);

// Access check
$canEdit    = $this->getAcl()->checkACL('edit', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);
$canDelete  = $this->getAcl()->checkACL('delete', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);
$canCheckin = $this->getAcl()->checkACL('editstate', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);

// URLs & Links
$imageUrl     = JoomHelper::getImg($this->item, $image_type);
$returnToken  = rawurlencode(base64_encode($this->backUrl));
$currentRoute = JoomHelper::getViewRoute('image', $this->item->id, $this->item->catid, null, null, $this->item->language, $this->getLayout() === 'default' ? null : $this->getLayout());
$editReturn   = rawurlencode(base64_encode($currentRoute));
$imageAuthor  = trim((string) ($this->item->author ?? ''));
$createdBy    = trim((string) ($this->item->created_by_name ?? ''));
$owner        = $createdBy ?: ($imageAuthor ?: Text::_('JAUTHOR'));
$initial      = \function_exists('mb_substr') ? mb_strtoupper(mb_substr($createdBy, 0, 1)) : strtoupper(substr($createdBy, 0, 1));
$rating       = $show_rating ? (float) JoomHelper::getRating($this->item->id) : 0.0;
$stateTask    = (int) $this->item->published === 1 ? 'unpublish' : 'publish';
$fields       = FieldsHelper::getFields('com_joomgallery.image', $this->item);
$navUrl       = static function ($image) use ($returnToken) {
return $image ? Route::_(JoomHelper::getViewRoute('image', (int) $image->id, (int) $image->catid) . '&return=' . $returnToken) : '';
};
$previousUrl  = $navUrl($this->navigation['previous'] ?? null);
$nextUrl      = $navUrl($this->navigation['next'] ?? null);

// Use the actual return URL so this also works with menu aliases and SEF URLs.
$backUri   = Uri::getInstance(html_entity_decode($this->backUrl, ENT_QUOTES, 'UTF-8'));
$backView  = $backUri->getVar('view', '');
$backPath  = trim($backUri->getPath(), '/');
$backLabel = 'COM_JOOMGALLERY_IMAGE_BACK';

if($backView === 'category' || preg_match('#(?:^|/)categor(?:y|ies)(?:/|$)#i', $backPath))
{
  $backLabel = 'COM_JOOMGALLERY_IMAGE_BACK_TO_CATEGORY';
}
elseif($backView === 'gallery' || preg_match('#(?:^|/)gallery(?:/|$)#i', $backPath))
{
  $backLabel = 'COM_JOOMGALLERY_IMAGE_BACK_TO_GALLERY';
}

// Tags
$tagLayout = new FileLayout('joomgallery.content.tags');
$tags      = !empty((array) ($this->item->tags ?? [])) ? $tagLayout->render($this->item->tags) : '';

// Image Metadata
$this->component->createMetadata($this->params['configs']->get('jg_metaprocessor', 'php'));
[$importantMetadata, $allMetadata] = $this->component->getMetadata()->renderPrep($this->item->imgmetadata, $meta_keys);
$metadataLayout                    = new FileLayout('joomgallery.content.metadata');
$metadataModalLayout               = new FileLayout('joomgallery.content.metadatamodal');
$imageDateLabel                    = 'COM_JOOMGALLERY_DATE_UPLOAD';
$databaseDate                      = !empty($this->item->date) ? \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $this->item->date) : false;
$metadataItems                     = $allMetadata->get('items', []);

// The view loads this information with JoomHelper::getImgInfo() for
// jg_detail_view_type_image. Keep it separate from the original file's EXIF.
if($show_metadata)
{
  $fileItems  = [];
  $fileLabels = [
    'mime_type' => 'COM_JOOMGALLERY_MIME_TYPE',
    'dimensions' => 'COM_JOOMGALLERY_IMAGE_SIZE',
    'adapter'   => 'COM_JOOMGALLERY_CONFIG_FILESYSTEM',
  ];

  $fileInfo = (array) $this->imageInfo;

  if(!empty($fileInfo['width']) && !empty($fileInfo['height']))
  {
    $fileInfo['dimensions'] = (int) $fileInfo['width'] . ' x ' . (int) $fileInfo['height'] . ' px';
  }

  if(!empty($fileInfo['adapter']))
  {
    $filesystem = JoomHelper::getService('Filesystem', [$this->item->filesystem]);

    foreach($filesystem->getProviders() as $provider)
    {
      foreach($provider->adapterNames as $adapterName)
      {
        if($provider->name . '-' . $adapterName === $fileInfo['adapter'])
        {
          $fileInfo['adapter'] = Text::_($provider->displayName) . ' (' . $adapterName . ')';
          break 2;
        }
      }
    }
  }

  foreach($fileLabels as $key => $label)
  {
    $value = $fileInfo[$key] ?? null;

    if(!\is_scalar($value) || (string) $value === '')
    {
      continue;
    }

    $fileItems[] = [
      'path'  => 'file.' . $key,
      'key'   => $key,
      'label' => $label,
      'value' => (string) $value,
    ];
  }

  // Include file-only images even when no remaining EXIF/IPTC is available.
  $allMetadata->set('items', array_merge($fileItems, $allMetadata->get('items', [])));
}

// Adjust image date field label
foreach($metadataItems as $metadataItem)
{
  if(!\in_array($metadataItem['key'], ['DateTimeOriginal', 'DateTime'], true))
  {
    continue;
  }

  $exifDate = \DateTimeImmutable::createFromFormat('!Y:m:d H:i:s', $metadataItem['rawValue'] ?? '');

  if($databaseDate !== false && $exifDate !== false && $databaseDate->format('Y-m-d H:i') === $exifDate->format('Y-m-d H:i'))
  {
    $imageDateLabel = 'COM_JOOMGALLERY_DATE';
    break;
  }
}

// HTML Metadata
$app = Factory::getApplication();
$doc = $app->getDocument();
// Reuse the document title/description prepared by the view for social previews.
$socialType = $this->params['configs']->get('jg_detail_view_social_image_type', 'detail', 'STRING');
$socialInfo = $this->imageInfo;

if($socialType !== $image_type)
{
  try
  {
    $socialInfo = JoomHelper::getImgInfo($this->item, $socialType);
  }
  catch(\Throwable $e)
  {
    // Do not advertise the displayed image's dimensions for a different rendition.
    $socialInfo = new \stdClass();
  }
}
$socialImage = html_entity_decode(JoomHelper::getImg($this->item, $socialType), ENT_QUOTES, 'UTF-8');

if(str_starts_with($socialImage, '//'))
{
  $socialImage = Uri::getInstance(Uri::root())->getScheme() . ':' . $socialImage;
}
elseif(!preg_match('#^[a-z][a-z0-9+.-]*:#i', $socialImage) && $socialImage !== '')
{
  $socialImage = str_starts_with($socialImage, '/')
    ? Uri::getInstance(Uri::root())->toString(['scheme', 'host', 'port']) . $socialImage
    : Uri::root() . $socialImage;
}
$plainSocialText = static function ($value): string {
  return trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
};
$socialData      = [
  'title'       => $plainSocialText($doc->getTitle()),
  'description' => $plainSocialText($doc->getDescription()) ?: $plainSocialText($this->item->description ?? ''),
  'url'         => Route::_($currentRoute, false, Route::TLS_IGNORE, true),
  'image'       => preg_match('#^https?://#i', $socialImage) ? $socialImage : '',
  'alt'         => $plainSocialText($this->item->description ?? ''),
  'imageInfo'   => $socialInfo,
  'siteName'    => $plainSocialText($app->get('sitename')),
  'locale'      => str_replace('-', '_', $app->getLanguage()->getTag()),
];

foreach(['joomgallery.opengraph.image', 'joomgallery.twitter.image'] as $socialLayout)
{
  $socialTags = trim((new FileLayout($socialLayout))->render($socialData));

  if($socialTags !== '')
  {
    $doc->addCustomTag($socialTags);
  }
}

// Build one complete copyright notice for both HTML and structured data.
// Embedded notices are already complete; only the fallback gets a symbol/year.
$metadata   = new Registry($this->item->imgmetadata);
$noticeText = static function ($value): string {
  if(\is_array($value) || \is_object($value))
  {
    $value = implode(', ', array_filter((array) $value, 'is_scalar'));
  }

  return \is_scalar($value) ? trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
};

$copyright = $noticeText($metadata->get('exif.IFD0.Copyright', '')) ?: $noticeText($metadata->get('iptc.2#116', ''));

// Embedded notices always remain in JSON-LD; generate a fallback only when enabled.
if($show_copyright && $copyright === '')
{
  $copyrightOwner = $noticeText($imageAuthor) ?: $noticeText($createdBy);

  if($copyrightOwner !== '')
  {
    $copyrightYear = $databaseDate !== false && (int) $databaseDate->format('Y') > 0
      ? HTMLHelper::_('date', $this->item->date, 'Y') : '';
    $copyright     = html_entity_decode('&copy;', ENT_QUOTES, 'UTF-8') . ' ' . ($copyrightYear !== '' ? $copyrightYear . ' ' : '') . $copyrightOwner;
  }
}

// Render structured data through an overridable layout
$jsonLdLayout = new FileLayout('joomgallery.jsonld.image');
$jsonLd       = trim(
    $jsonLdLayout->render(
        [
          'item'          => $this->item,
          'metadata'      => $this->item->imgmetadata,
          'metadataItems' => $metadataItems,
          'showMetadata'  => (bool) $show_metadata,
          'copyright'     => $copyright,
          'imageInfo'     => $this->imageInfo,
          'pageUrl'       => Route::_($currentRoute, false, Route::TLS_IGNORE, true),
          'pageTitle'     => $doc->getTitle(),
          'imageUrl'      => $imageUrl,
          'thumbnailUrl'  => JoomHelper::getImg($this->item, 'thumbnail'),
        ]
    )
);

if($jsonLd !== '')
{
  $doc->addCustomTag('<script type="application/ld+json">' . $jsonLd . '</script>');
}

// Keep module positions consistent while allowing YOOtheme to supply its own chrome.
$renderModules = static function (string $position) use ($is_yootheme, $module_class, $module_header_class): void {
  foreach(ModuleHelper::getModules($position) as $module)
  {
    if(!$is_yootheme)
    {
      echo '<div class="my-2">' . ModuleHelper::renderModule($module) . '</div>';
      continue;
    }

    $moduleParams = json_decode($module->params ?? '{}', true) ?: [];
    $headerTag    = strtolower((string) ($moduleParams['header_tag'] ?? 'h3'));
    $headerTag    = \in_array($headerTag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $headerTag : 'h3';
    $headerClass  = trim($module_header_class . (string) ($moduleParams['header_class'] ?? ''));

    echo '<div class="' . htmlspecialchars($module_class, ENT_QUOTES, 'UTF-8') . '">';

    if($module->showtitle)
    {
      echo '<' . $headerTag . ' class="' . htmlspecialchars($headerClass, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($module->title, ENT_QUOTES, 'UTF-8') . '</' . $headerTag . '>';
    }

    echo ModuleHelper::renderModule($module, ['style' => 'none']);
    echo '</div>';
  }
};
?>

<?php // Capture the header once for either position.
ob_start(); ?>
    <?php if($show_title || ($show_created_by && $createdBy !== '') || ($show_imgdate && !empty($this->item->date))) : ?>
      <header class="<?php echo $header_class; ?>">
        <?php if($show_title || ($show_created_by && $createdBy !== '')) : ?>
          <div class="<?php echo $identity_class; ?>">
            <?php if($show_created_by && $createdBy !== '') : ?>
              <span class="<?php echo $avatar_class; ?>" aria-hidden="true"><?php echo $this->escape($initial); ?></span>
            <?php endif; ?>
            <div>
              <?php if($show_title) : ?>
                <h1 class="<?php echo $title_class; ?>" itemprop="name"><?php echo $this->escape($this->item->title); ?></h1>
              <?php endif; ?>
              <?php if($show_created_by && $createdBy !== '') : ?>
                <div class="<?php echo $muted_class; ?>"><?php echo Text::_('COM_JOOMGALLERY_OWNER'); ?>: <strong><?php echo $this->escape($createdBy); ?></strong></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if($show_imgdate && !empty($this->item->date)) : ?>
          <time class="<?php echo $muted_class; ?>" datetime="<?php echo $this->escape($this->item->date); ?>"><?php echo Text::_($imageDateLabel); ?>: <?php echo HTMLHelper::_('date', $this->item->date, Text::_('DATE_FORMAT_LC4')); ?></time>
        <?php endif; ?>
      </header>
    <?php endif; ?>
<?php $imageHeader = ob_get_clean(); ?>

<article class="jg-detail<?php echo $is_yootheme ? '' : ' overflow-hidden'; ?>" itemscope itemtype="https://schema.org/ImageObject">
  <a class="jg-detail__back <?php echo $button_class; ?>" href="<?php echo htmlspecialchars($this->backUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo Text::_($backLabel); ?>" title="<?php echo Text::_($backLabel); ?>">
    <i class="icon-arrow-left" aria-hidden="true"></i><span><?php echo Text::_($backLabel); ?></span>
  </a>

  <?php if($header_above) echo $imageHeader; ?>

  <?php $renderModules('jg_image_top'); ?>

  <div class="<?php echo $stage_class; ?>">
    <?php if($previousUrl) : ?>
      <a class="jg-detail__nav jg-detail__nav--prev <?php echo $is_yootheme ? 'uk-icon-button uk-position-center-left uk-margin-small-left' : 'btn btn-light rounded-circle position-absolute top-50 start-0 translate-middle-y ms-2 shadow'; ?>" href="<?php echo $previousUrl; ?>" aria-label="<?php echo Text::_('JPREV'); ?>"><i class="icon-chevron-left" aria-hidden="true"></i></a>
    <?php endif; ?>
    <?php // A transparent overlay deters casual saving; it cannot secure a public image. ?>
    <div class="<?php echo $image_wrapper_class; ?>" data-jg-image-protection>
      <img class="<?php echo $image_class; ?>" src="<?php echo $this->escape($imageUrl); ?>" alt="<?php echo $this->escape($this->item->title); ?>" itemprop="contentUrl" loading="eager" draggable="false">
      <span class="<?php echo $image_overlay_class; ?>" aria-hidden="true"></span>
    </div>
    <?php if($nextUrl) : ?>
      <a class="jg-detail__nav jg-detail__nav--next <?php echo $is_yootheme ? 'uk-icon-button uk-position-center-right uk-margin-small-right' : 'btn btn-light rounded-circle position-absolute top-50 end-0 translate-middle-y me-2 shadow'; ?>" href="<?php echo $nextUrl; ?>" aria-label="<?php echo Text::_('JNEXT'); ?>"><i class="icon-chevron-right" aria-hidden="true"></i></a>
    <?php endif; ?>
  </div>

  <div class="<?php echo $content_class; ?>">
    <?php $renderModules('jg_image_bottom'); ?>

    <div class="<?php echo $action_class; ?>" aria-label="Image actions">
      <?php if($show_favorite_btn) : ?>
        <span class="<?php echo $is_yootheme ? 'uk-display-inline-block ' . $action_item_class : 'd-inline-block'; ?>" tabindex="0" data-jg-feature-popover data-bs-html="false" data-bs-content="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_FEATURE_COMING_SOON')); ?>" data-bs-toggle="popover" data-bs-trigger="hover focus">
          <button class="<?php echo $secondary_button; ?>" style="pointer-events:none" type="button" disabled><span class="icon-heart" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMGALLERY_FAVORITE'); ?></button>
        </span>
      <?php endif; ?>
      <?php if($show_download_btn) : ?>
        <span class="<?php echo $is_yootheme ? 'uk-display-inline-block ' . $action_item_class : 'd-inline-block'; ?>" tabindex="0" data-jg-feature-popover data-bs-html="false" data-bs-content="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_FEATURE_COMING_SOON')); ?>" data-bs-toggle="popover" data-bs-trigger="hover focus">
          <button class="<?php echo $secondary_button; ?>" style="pointer-events:none" type="button" disabled><span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMGALLERY_DOWNLOAD'); ?></button>
        </span>
      <?php endif; ?>
      <?php if($show_voting_btn) : ?>
        <span class="<?php echo $is_yootheme ? 'uk-display-inline-block ' . $action_item_class : 'd-inline-block'; ?>" tabindex="0" data-jg-feature-popover data-bs-html="false" data-bs-content="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_FEATURE_COMING_SOON')); ?>" data-bs-toggle="popover" data-bs-trigger="hover focus">
          <button class="<?php echo $secondary_button; ?>" style="pointer-events:none" type="button" disabled><span class="icon-star" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMGALLERY_VOTE'); ?></button>
        </span>
      <?php endif; ?>
      <?php if($show_comment_btn) : ?>
        <span class="<?php echo $is_yootheme ? 'uk-display-inline-block ' . $action_item_class : 'd-inline-block'; ?>" tabindex="0" data-jg-feature-popover data-bs-html="false" data-bs-content="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_FEATURE_COMING_SOON')); ?>" data-bs-toggle="popover" data-bs-trigger="hover focus">
          <button class="<?php echo $secondary_button; ?>" style="pointer-events:none" type="button" disabled><span class="icon-comment" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMGALLERY_COMMENT'); ?></button>
        </span>
      <?php endif; ?>
      <?php if($show_share_btn) : ?>
        <button class="<?php echo $secondary_button . ' ' . ($is_yootheme ? 'uk-margin-auto-left ' . $action_item_class : 'ms-md-auto'); ?>" type="button" data-jg-copy-link>
          <span class="icon-link" aria-hidden="true"></span> <span>Copy link</span>
        </button>
      <?php endif; ?>

      <?php if($canEdit || $canDelete || $canCheckin) : ?>
        <div class="<?php echo $is_yootheme ? 'uk-flex uk-flex-wrap' : 'd-flex flex-wrap gap-2'; ?>">
          <?php if($canEdit) : ?>
            <a class="<?php echo $primary_button . ' ' . $action_item_class; ?>" href="<?php echo Route::_('index.php?option=com_joomgallery&task=userimage.edit&id=' . (int) $this->item->id . '&return=' . $editReturn); ?>"><span class="icon-edit" aria-hidden="true"></span> <?php echo Text::_('JACTION_EDIT'); ?></a>
          <?php endif; ?>
          <form class="<?php echo $is_yootheme ? 'uk-flex uk-flex-wrap' : 'd-flex gap-2'; ?>" action="<?php echo Route::_('index.php?option=com_joomgallery'); ?>" method="post" data-jg-manage-form>
            <input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>">
            <input type="hidden" name="return" value="<?php echo base64_encode($this->backUrl); ?>">
            <?php if($canCheckin) : ?>
              <button class="<?php echo $secondary_button . ' ' . $action_item_class; ?>" type="submit" name="task" value="userimage.<?php echo $stateTask; ?>"><span class="icon-<?php echo $stateTask === 'publish' ? 'check' : 'cancel'; ?>" aria-hidden="true"></span> <?php echo Text::_($stateTask === 'publish' ? 'PUBLISH' : 'UNPUBLISH'); ?></button>
            <?php endif; ?>
            <?php if($canDelete) : ?>
              <button class="<?php echo $danger_button . ' ' . $action_item_class; ?>" type="submit" name="task" value="userimage.remove" data-jg-delete><span class="icon-trash" aria-hidden="true"></span> <?php echo Text::_('JACTION_DELETE'); ?></button>
            <?php endif; ?>
            <?php echo HTMLHelper::_('form.token'); ?>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <?php if(!$header_above) echo $imageHeader; ?>

    <?php $renderModules('jg_image_before_info'); ?>

    <div class="<?php echo $is_yootheme ? 'uk-flex uk-flex-wrap uk-text-meta uk-padding-small' : 'd-flex flex-wrap gap-4 py-3 border-bottom text-body-secondary'; ?>">
      <?php if($show_votes) : ?>
        <span><span class="icon-heart" aria-hidden="true"></span> <?php echo (int) $this->item->votes; ?> <?php echo Text::_('COM_JOOMGALLERY_VOTES'); ?></span>
      <?php endif; ?>
      <?php if($show_rating) : ?>
        <span><span class="icon-star" aria-hidden="true"></span> <?php echo $this->escape(number_format($rating, 2)); ?> <?php echo Text::_('COM_JOOMGALLERY_IMAGE_RATING'); ?></span>
      <?php endif; ?>
      <?php if($show_comments) : ?>
        <span><span class="icon-comment" aria-hidden="true"></span> 0 <?php echo Text::_('COM_JOOMGALLERY_COMMENTS'); ?></span>
      <?php endif; ?>
      <?php if($show_hits) : ?>
        <span><span class="icon-eye" aria-hidden="true"></span> <?php echo (int) $this->item->hits; ?> <?php echo Text::_('JGLOBAL_HITS'); ?></span>
      <?php endif; ?>
      <?php if($show_downloads) : ?>
        <span><span class="icon-download" aria-hidden="true"></span> <?php echo (int) $this->item->downloads; ?> <?php echo Text::_('COM_JOOMGALLERY_DOWNLOADS'); ?></span>
      <?php endif; ?>
    </div>

    <?php if($show_tags && trim($tags) !== '') : ?>
      <div class="jg-detail__tags <?php echo $is_yootheme ? 'uk-flex uk-flex-wrap uk-padding-small' : 'd-flex flex-wrap gap-2 py-4'; ?>"><?php echo $tags; ?></div>
    <?php endif; ?>
    <?php if($show_description && trim((string) $this->item->description) !== '') : ?>
      <div class="<?php echo $is_yootheme ? 'uk-text-lead uk-margin-medium-bottom' : 'lead mb-4'; ?>" itemprop="description"><?php echo JoomHelper::sanitizeHtml($this->item->description, true); ?>
    </div>
    <?php endif; ?>

    <dl class="<?php echo $info_list_class; ?>">
      <?php if($show_imgauthor && $imageAuthor !== '') : ?>
        <dt class="<?php echo $info_term_class; ?>"><?php echo Text::_('JAUTHOR'); ?></dt>
        <dd class="<?php echo $info_value_class; ?>"><?php echo $this->escape($imageAuthor); ?></dd>
      <?php endif; ?>
      <?php if($show_category) : ?>
        <dt class="<?php echo $info_term_class; ?>"><?php echo Text::_('JCATEGORY'); ?></dt>
        <dd class="<?php echo $info_value_class; ?>"><a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=' . (int) $this->item->catid); ?>"><?php echo $this->escape($this->item->cattitle); ?></a></dd>
      <?php endif; ?>
      <?php if($show_image_size && !empty($this->imageInfo->width) && !empty($this->imageInfo->height)) : ?>
        <dt class="<?php echo $info_term_class; ?>"><?php echo Text::_('COM_JOOMGALLERY_IMAGE_SIZE'); ?></dt>
        <dd class="<?php echo $info_value_class; ?>"><?php echo (int) $this->imageInfo->width; ?> &times; <?php echo (int) $this->imageInfo->height; ?> px</dd>
      <?php endif; ?>

      <?php // Custom fields ?>
      <?php foreach($fields as $field) : ?>
        <?php if($this->component->getAccess()->checkViewLevel($field->access) && $field->params->get('display') > 0) : ?>
          <dt class="<?php echo $info_term_class; ?>"><?php echo $this->escape($field->title); ?></dt>
          <dd class="<?php echo $info_value_class; ?>"><?php echo $this->escape($field->value); ?></dd>
        <?php endif; ?>
      <?php endforeach; ?>

      <?php // Important metadata fields ?>
      <?php if($show_metadata) : ?>
        <?php echo $metadataLayout->render($importantMetadata); ?>
      <?php endif; ?>
    </dl>
    <br>
    <?php // Important metadata modal button ?>
    <?php if($show_metadata && \count($allMetadata->get('items', [])) > 0) : ?>
      <button class="<?php echo $secondary_button . ($is_yootheme ? ' uk-margin-small-top' : ' mt-2'); ?>" type="button" data-bs-toggle="modal" data-bs-target="#jg-metadata-modal">
        <span class="icon-info-circle" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?>
      </button>
    <?php endif; ?>

    <?php $renderModules('jg_image_after_info'); ?>

    <?php if($show_copyright && $copyright) : ?>
      <div class="<?php echo $is_yootheme ? 'uk-margin-medium-top uk-text-meta' : 'mt-4 text-body-secondary'; ?>"><?php echo $this->escape($copyright); ?></div>
    <?php endif; ?>
  </div>
</article>

<?php if($show_metadata && \count($allMetadata->get('items', [])) > 0) : ?>
  <?php echo $metadataModalLayout->render($allMetadata); ?>
<?php endif; ?>

<script>
  document.querySelector('[data-jg-copy-link]')?.addEventListener('click', async function () {
    try { await navigator.clipboard.writeText(window.location.href); this.querySelector('span:last-child').textContent = 'Copied'; }
    catch (error) { window.prompt('Copy this link:', window.location.href); }
  });

  document.querySelector('[data-jg-manage-form]')?.addEventListener('submit', function (event) {
    if (event.submitter?.hasAttribute('data-jg-delete') && !window.confirm(<?php echo json_encode(Text::_('JGLOBAL_CONFIRM_DELETE')); ?>)) event.preventDefault();
  });
</script>
