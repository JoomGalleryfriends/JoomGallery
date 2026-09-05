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
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;

// image params
$image_type       = $this->params['configs']->get('jg_detail_view_type_image', 'detail', 'STRING');
$show_title       = $this->params['configs']->get('jg_detail_view_show_title', 0, 'INT');
$show_category    = $this->params['configs']->get('jg_detail_view_show_category', 0, 'INT');
$show_description = $this->params['configs']->get('jg_detail_view_show_description', 0, 'INT');
$show_imgdate     = $this->params['configs']->get('jg_detail_view_show_imgdate', 0, 'INT');
$show_imgauthor   = $this->params['configs']->get('jg_detail_view_show_imgauthor', 0, 'INT');
$show_created_by  = $this->params['configs']->get('jg_detail_view_show_created_by', 0, 'INT');
$show_votes       = $this->params['configs']->get('jg_detail_view_show_votes', 0, 'INT');
$show_rating      = $this->params['configs']->get('jg_detail_view_show_rating', 0, 'INT');
$show_hits        = $this->params['configs']->get('jg_detail_view_show_hits', 0, 'INT');
$show_downloads   = $this->params['configs']->get('jg_detail_view_show_downloads', 0, 'INT');
$show_tags        = $this->params['configs']->get('jg_detail_view_show_tags', 0, 'INT');
$show_metadata    = $this->params['configs']->get('jg_detail_view_show_metadata', 0, 'INT');

$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');
$wa->useScript('bootstrap.modal');

// Access check
$canEdit    = $this->getAcl()->checkACL('edit', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);
$canDelete  = $this->getAcl()->checkACL('delete', 'com_joomgallery.image', $this->item->id, $this->item->catid, true);
$canCheckin = $this->getAcl()->checkACL('editstate', 'com_joomgallery.image', $this->item->id, $this->item->catid, true) || $this->item->checked_out == $this->getCurrentUser()->id;

// URLs & Links
$imageUrl     = JoomHelper::getImg($this->item, $image_type);
$returnToken  = rawurlencode(base64_encode($this->backUrl));
$currentRoute = JoomHelper::getViewRoute('image', $this->item->id, $this->item->catid, $this->item->language, $this->getLayout());
$editReturn   = rawurlencode(base64_encode($currentRoute));
$owner        = $this->item->created_by_name ?: ($this->item->author ?: Text::_('JAUTHOR'));
$initial      = function_exists('mb_substr') ? mb_strtoupper(mb_substr($owner, 0, 1)) : strtoupper(substr($owner, 0, 1));
$stateTask    = (int) $this->item->published === 1 ? 'unpublish' : 'publish';
$fields       = FieldsHelper::getFields('com_joomgallery.image', $this->item);
$navUrl       = static function($image) use ($returnToken) {return $image ? Route::_(JoomHelper::getViewRoute('image', (int) $image->id, (int) $image->catid) . '&return=' . $returnToken) : '';};
$previousUrl  = $navUrl($this->navigation['previous'] ?? null);
$nextUrl      = $navUrl($this->navigation['next'] ?? null);

// Tags
$tagLayout = new FileLayout('joomgallery.content.tags');
$tags      = $tagLayout->render($this->item->tags);

// Image Metadata
$metadataRegistry = new Registry($this->item->imgmetadata);
$metadataItems    = [];
$flattenMetadata  = static function($values) use (&$metadataItems, &$flattenMetadata): void {
  foreach((array) $values as $key => $value)
  {
    if(is_array($value) || is_object($value))
    {
      $flattenMetadata($value);
      continue;
    }
    if($value !== '' && $value !== null)
    {
      $metadataItems[(string) $key] = (string) $value;
    }
  }
};
$flattenMetadata($metadataRegistry->toArray());

$importantMetadataKey = null;
foreach(['Model', 'Make', 'DateTimeOriginal', 'DateTime'] as $key)
{
  if(isset($metadataItems[$key]))
  {
    $importantMetadataKey = $key;
    break;
  }
}
$otherMetadata = $metadataItems;
if($importantMetadataKey !== null)
{
  unset($otherMetadata[$importantMetadataKey]);
}

// HTML Metadata
$app   = Factory::getApplication();
$doc   = $app->getDocument();
// Title
$title            = $this->item->title ?? '';
$baseTitle        = trim(Text::_('COM_JOOMGALLERY_META_TITLE_PREFIX') . ' ' . $title);
$sitename         = $app->get('sitename');
$siteNamePosition = (int) $app->get('sitename_pagetitles', 0);
$app->getDocument()->setTitle($siteNamePosition === 1 ? $sitename . ' - ' . $baseTitle : ($siteNamePosition === 2 ? $baseTitle . ' - ' . $sitename : $baseTitle));

// Custom Fields
$fields = FieldsHelper::getFields('com_joomgallery.image', $this->item);
?>

<?php // load modules on jg_image_top ?>
<?php $modules = ModuleHelper::getModules('jg_image_top'); ?>
<?php if(!empty($modules)) : ?>
  <?php foreach($modules as $module) : ?>
    <div class="mt-3"><?php echo ModuleHelper::renderModule($module, ['style' => 'card']); ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<article class="jg-detail overflow-hidden" itemscope itemtype="https://schema.org/ImageObject">
  <a class="jg-detail__back btn btn-outline-primary" href="<?php echo htmlspecialchars($this->backUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo Text::_('JBACK'); ?>" title="<?php echo Text::_('JBACK'); ?>">
    <i class="icon-arrow-left" aria-hidden="true"></i><span><?php echo Text::_('JBACK'); ?></span>
  </a>
  <div class="jg-detail__stage position-relative d-flex align-items-center justify-content-center p-3">
    <?php if($previousUrl) : ?>
      <a class="jg-detail__nav jg-detail__nav--prev btn btn-light rounded-circle position-absolute top-50 start-0 translate-middle-y ms-2 shadow" href="<?php echo $previousUrl; ?>" aria-label="<?php echo Text::_('JPREV'); ?>"><i class="icon-chevron-left" aria-hidden="true"></i></a>
    <?php endif; ?>
    <img class="jg-detail__image img-fluid d-block mx-auto" src="<?php echo $this->escape($imageUrl); ?>" alt="<?php echo $this->escape($this->item->title); ?>" itemprop="contentUrl" loading="eager">
    <?php if($nextUrl) : ?>
      <a class="jg-detail__nav jg-detail__nav--next btn btn-light rounded-circle position-absolute top-50 end-0 translate-middle-y me-2 shadow" href="<?php echo $nextUrl; ?>" aria-label="<?php echo Text::_('JNEXT'); ?>"><i class="icon-chevron-right" aria-hidden="true"></i></a>
    <?php endif; ?>
  </div>

  <div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center gap-2 pb-3 border-bottom" aria-label="Image actions">
      <button class="btn btn-outline-secondary" type="button" disabled title="Favorites are coming soon">
        <span class="icon-heart me-1" aria-hidden="true"></span>Favorite
      </button>
      <button class="btn btn-outline-secondary" type="button" disabled title="Comments are coming soon">
        <span class="icon-comment me-1" aria-hidden="true"></span>Comment
      </button>
      <button class="btn btn-outline-secondary" type="button" disabled title="Downloads are coming soon">
        <span class="icon-download me-1" aria-hidden="true"></span>Download
      </button>
      <button class="btn btn-outline-secondary ms-md-auto" type="button" data-jg-copy-link>
        <span class="icon-link me-1" aria-hidden="true"></span><span>Copy link</span>
      </button>

      <?php if($canEdit || $canDelete || $canCheckin) : ?>
        <div class="d-flex flex-wrap gap-2">
          <?php if($canEdit) : ?>
            <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=userimage.edit&id=' . (int) $this->item->id . '&return=' . $editReturn); ?>"><span class="icon-edit me-1" aria-hidden="true"></span><?php echo Text::_('JACTION_EDIT'); ?></a>
          <?php endif; ?>
          <form class="d-flex gap-2" action="<?php echo Route::_('index.php?option=com_joomgallery'); ?>" method="post" data-jg-manage-form>
            <input type="hidden" name="cid[]" value="<?php echo (int) $this->item->id; ?>">
            <input type="hidden" name="return" value="<?php echo base64_encode($this->backUrl); ?>">
            <?php if($canCheckin) : ?>
              <button class="btn btn-outline-secondary" type="submit" name="task" value="imageform.<?php echo $stateTask; ?>"><span class="icon-<?php echo $stateTask === 'publish' ? 'check' : 'cancel'; ?> me-1" aria-hidden="true"></span><?php echo Text::_($stateTask === 'publish' ? 'JTOOLBAR_PUBLISH' : 'JTOOLBAR_UNPUBLISH'); ?></button>
            <?php endif; ?>
            <?php if($canDelete) : ?>
              <button class="btn btn-outline-danger" type="submit" name="task" value="imageform.remove" data-jg-delete><span class="icon-trash me-1" aria-hidden="true"></span><?php echo Text::_('JACTION_DELETE'); ?></button>
            <?php endif; ?>
            <?php echo HTMLHelper::_('form.token'); ?>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <header class="d-flex flex-column flex-md-row justify-content-between gap-3 py-4">
      <div class="d-flex gap-3 align-items-start"><span class="jg-detail__avatar d-inline-flex align-items-center justify-content-center rounded bg-primary text-white fw-bold" aria-hidden="true"><?php echo $this->escape($initial); ?></span><div><h1 class="h2 mb-1" itemprop="name"><?php echo $this->escape($this->item->title); ?></h1><div class="text-body-secondary"><?php echo Text::_('JAUTHOR'); ?> <strong><?php echo $this->escape($owner); ?></strong></div></div></div>
      <time class="text-body-secondary text-md-end" datetime="<?php echo $this->escape($this->item->date); ?>"><?php echo Text::_('JPUBLISHED'); ?>: <?php echo HTMLHelper::_('date', $this->item->date, Text::_('DATE_FORMAT_LC3')); ?></time>
    </header>

    <div class="d-flex flex-wrap gap-4 py-3 border-bottom text-body-secondary"><span><span class="icon-heart"></span> <?php echo (int) $this->item->votes; ?> <?php echo Text::_('COM_JOOMGALLERY_VOTES'); ?></span><span><span class="icon-comment"></span> 0 Comments</span><span><span class="icon-eye"></span> <?php echo (int) $this->item->hits; ?> <?php echo Text::_('JGLOBAL_HITS'); ?></span><span><span class="icon-download"></span> <?php echo (int) $this->item->downloads; ?> <?php echo Text::_('COM_JOOMGALLERY_DOWNLOADS'); ?></span></div>

    <?php if($show_tags && trim($tags) !== '') : ?><div class="d-flex flex-wrap gap-2 py-4 jg-detail__tags"><?php echo $tags; ?></div><?php endif; ?>
    <?php if($show_description && trim((string) $this->item->description) !== '') : ?><div class="lead mb-4" itemprop="description"><?php echo JoomHelper::sanitizeHtml($this->item->description); ?></div><?php endif; ?>

    <dl class="row mb-0">
      <?php if(!empty($this->imageInfo->width) && !empty($this->imageInfo->height)) : ?><dt class="col-sm-3 col-lg-2">Image size</dt><dd class="col-sm-9 col-lg-10"><?php echo (int) $this->imageInfo->width; ?> &times; <?php echo (int) $this->imageInfo->height; ?> px</dd><?php endif; ?>
      <?php if($importantMetadataKey !== null) : ?><dt class="col-sm-3 col-lg-2"><?php echo $this->escape(Text::_($importantMetadataKey)); ?></dt><dd class="col-sm-9 col-lg-10"><?php echo $this->escape($metadataItems[$importantMetadataKey]); ?></dd><?php endif; ?>
      <dt class="col-sm-3 col-lg-2"><?php echo Text::_('JCATEGORY'); ?></dt><dd class="col-sm-9 col-lg-10"><a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=' . (int) $this->item->catid); ?>"><?php echo $this->escape($this->item->cattitle); ?></a></dd>
      <?php foreach($fields as $field) : ?><?php if($this->component->getAccess()->checkViewLevel($field->access) && $field->params->get('display') > 0) : ?><dt class="col-sm-3 col-lg-2"><?php echo $this->escape($field->title); ?></dt><dd class="col-sm-9 col-lg-10"><?php echo $this->escape($field->value); ?></dd><?php endif; ?><?php endforeach; ?>
    </dl>

    <?php if($show_metadata && !empty($otherMetadata)) : ?>
      <button class="btn btn-outline-secondary mt-2" type="button" data-bs-toggle="modal" data-bs-target="#jg-metadata-modal"><span class="icon-info-circle me-1" aria-hidden="true"></span><?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?></button>
    <?php endif; ?>
    <div class="mt-4 text-body-secondary">&copy; <?php echo HTMLHelper::_('date', $this->item->date, 'Y'); ?> <?php echo $this->escape($owner); ?></div>
  </div>
</article>

<?php if($show_metadata && !empty($otherMetadata)) : ?>
  <div class="modal fade" id="jg-metadata-modal" tabindex="-1" aria-labelledby="jg-metadata-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
      <div class="modal-header"><h2 class="modal-title fs-5" id="jg-metadata-modal-title"><?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button></div>
      <div class="modal-body"><dl class="row mb-0"><?php foreach($otherMetadata as $key => $value) : ?><dt class="col-sm-5"><?php echo $this->escape(Text::_($key)); ?></dt><dd class="col-sm-7 text-break"><?php echo $this->escape($value); ?></dd><?php endforeach; ?></dl></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCLOSE'); ?></button></div>
    </div></div>
  </div>
<?php endif; ?>

<?php foreach(ModuleHelper::getModules('jg_image_before_info') as $module) : ?><div class="mt-3"><?php echo ModuleHelper::renderModule($module, ['style' => 'card']); ?></div><?php endforeach; ?>
<?php foreach(ModuleHelper::getModules('jg_image_bottom') as $module) : ?><div class="mt-3"><?php echo ModuleHelper::renderModule($module, ['style' => 'card']); ?></div><?php endforeach; ?>

<script>
  document.querySelector('[data-jg-copy-link]')?.addEventListener('click', async function () {
    try { await navigator.clipboard.writeText(window.location.href); this.querySelector('span:last-child').textContent = 'Copied'; }
    catch (error) { window.prompt('Copy this link:', window.location.href); }
  });

  document.querySelector('[data-jg-manage-form]')?.addEventListener('submit', function (event) {
    if (event.submitter?.hasAttribute('data-jg-delete') && !window.confirm(<?php echo json_encode(Text::_('JGLOBAL_CONFIRM_DELETE')); ?>)) event.preventDefault();
  });
</script>
