<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// Image params
$image_type             = $this->params['configs']->get('jg_gallery_view_type_image', 'thumbnail', 'STRING');
$gallery_class          = $this->params['configs']->get('jg_gallery_view_class', 'masonry', 'STRING');
$num_columns            = $this->params['configs']->get('jg_gallery_view_num_columns', 3, 'INT');
$image_class            = $this->params['configs']->get('jg_gallery_view_image_class', 0, 'INT');
$justified_height       = $this->params['configs']->get('jg_gallery_view_justified_height', 200, 'INT');
$justified_gap          = $this->params['configs']->get('jg_gallery_view_justified_gap', 5, 'INT');
$image_link             = $this->params['configs']->get('jg_gallery_view_image_link', 'defaultview', 'STRING');
$caption_align          = $this->params['configs']->get('jg_gallery_view_caption_align', 'center', 'STRING');
$show_title             = $this->params['configs']->get('jg_gallery_view_images_show_title', 1, 'INT');
$title_link             = $this->params['configs']->get('jg_gallery_view_title_link', 'defaultview', 'STRING');
$show_description       = $this->params['configs']->get('jg_gallery_view_show_description', 0, 'INT');
$show_description_label = $this->params['configs']->get('jg_gallery_view_show_description_label', 1, 'INT');
$show_imgdate           = $this->params['configs']->get('jg_gallery_view_show_imgdate', 0, 'INT');
$show_imgauthor         = $this->params['configs']->get('jg_gallery_view_show_imgauthor', 0, 'INT');
$show_tags              = $this->params['configs']->get('jg_gallery_view_show_tags', 0, 'INT');
$show_tags_label        = $this->params['configs']->get('jg_gallery_view_show_tags_label', 1, 'INT');
$browse_categories_link = $this->params['configs']->get('jg_gallery_view_browse_categories_link', 0, 'INT');
$lightbox_image         = $this->params['configs']->get('jg_lightbox_image', 'detail', 'STRING');
$lightbox_thumbnails    = $this->params['configs']->get('jg_lightbox_thumbnails', 0, 'INT');
$lightbox_zoom          = $this->params['configs']->get('jg_lightbox_zoom', 0, 'INT');

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');

if($gallery_class == 'masonry')
{
  $wa->useScript('com_joomgallery.masonry');
}

if($gallery_class == 'justified')
{
  $wa->useScript('com_joomgallery.justified');
  $wa->addInlineStyle('.jg-images[class*=" justified-"] .jg-image-caption-hover { right: ' . $justified_gap . 'px; }');
}

$lightbox = false;

if($image_link == 'lightgallery')
{
  $lightbox = true;

  $wa->useScript('com_joomgallery.lightgallery');
  $wa->useScript('com_joomgallery.lg-hash');
  $wa->useScript('com_joomgallery.lg-thumbnail');
  $wa->useScript('com_joomgallery.lg-zoom');
  $wa->useStyle('com_joomgallery.lightgallery-bundle');
}

// Add and initialize the grid script
$iniJS  = 'window.joomGrid["1-' . $this->item->id . '"] = {';
$iniJS .= '  itemid: "1-' . $this->item->id . '",';
$iniJS .= '  pagination: 0,';
$iniJS .= '  layout: "' . $gallery_class . '",';
$iniJS .= '  num_columns: ' . $num_columns . ',';
$iniJS .= '  lightbox: ' . ($lightbox ? 'true' : 'false') . ',';
$iniJS .= '  lightbox_params: {container: "lightgallery-1-' . $this->item->id . '", selector: ".lightgallery-item"},';
$iniJS .= '  thumbnails: ' . ($lightbox_thumbnails ? 'true' : 'false') . ',';
$iniJS .= '  zoom: ' . ($lightbox_zoom ? 'true' : 'false') . ',';
$iniJS .= '  justified: {height: ' . $justified_height . ', gap: ' . $justified_gap . '}';
$iniJS .= '};';

$wa->useScript('com_joomgallery.joomgrid');
$wa->addInlineScript($iniJS, ['position' => 'after'], [], ['com_joomgallery.joomgrid']);
?>

<div class="com-joomgallery-gallery">
  <?php if($this->params['menu']->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1> <?php echo $this->escape($this->params['menu']->get('page_heading')); ?> </h1>
    </div>
  <?php endif; ?>

  <?php // load modules on jg_gallery_top ?>
  <?php $modules = ModuleHelper::getModules('jg_gallery_top'); ?>
  <?php if(!empty($modules)) : ?>
    <?php foreach($modules as $module) : ?>
      <?php $moduleparams = json_decode($module->params, true); ?>
      <div class="card">
        <?php if($module->showtitle) : ?>
          <?php $moduleheader = '<' . $moduleparams['header_tag'] . ' class="card-header ' . $moduleparams['header_class'] . '">' . htmlspecialchars($module->title) . '</' . $moduleparams['header_tag'] . '>'; ?>
          <?php echo $moduleheader; ?>
        <?php endif; ?>
        <?php echo ModuleHelper::renderModule($module, ['style' => 'none']); ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <div class="gallery-header">
    <?php echo HTMLHelper::_('content.prepare', $this->item->description, '', 'com_joomgallery.gallery'); ?>
  </div>

  <?php // Link to category overview ?>
  <?php if($browse_categories_link == '1') : ?>
    <div class="center text-center">
      <a class="jg-link btn btn-outline-primary"
         href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=1'); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_GALLERY_VIEW_BROWSE_CATEGORIES'); ?>
      </a>
    </div>
  <?php endif; ?>

  <?php // Search bar
    $search_vars = [
      'provider'      => $this->component->getSearch(),
      'query'         => $this->item->query,
      'params'        => $this->params['configs'],
      'menuitem'      => ['itemid' => $this->itemid, 'option' => 'com_joomgallery', 'view' => 'gallery', 'model' => 'images'],
      'suggest_url'   => Route::_('index.php?option=com_finder&task=suggestions.suggest&format=json&tmpl=component', false),
      'search_url'    => ['option' => 'com_joomgallery', 'view' => 'gallery'],
      'filterForm'    => $this->filterForm,
      'activeFilters' => $this->activeFilters,
    ];

    echo LayoutHelper::render('joomgallery.search.searchbar', $search_vars);
  ?>

  <?php
$image_tags_data = [];

if($show_tags && !empty($this->item->images->items))
{
  $imageIds = array_map(
    static fn($item) => (int) $item->id,
    $this->item->images->items
  );

  $component       = Joomla\CMS\Factory::getApplication()->bootComponent('com_joomgallery');
  $tagsModel       = $component->getMVCFactory()->createModel('Tags', 'administrator');
  $image_tags_data = $tagsModel->getMappedTitlesByImageIds($imageIds);
}
?>

<?php // Hint for no items ?>
  <?php if(\count($this->item->images->items) == 0) : ?>
    <p><?php echo Text::_('COM_JOOMGALLERY_GALLERY_NO_IMAGES') ?></p>
  <?php else: ?>
    <?php // Display data array for grid layout
    $imgsData = [
      'id' => '1-' . $this->item->id, 'layout' => $gallery_class, 'items' => $this->item->images->items, 'num_columns' => (int) $num_columns,
      'caption_align' => $caption_align, 'image_class' => $image_class, 'image_type' => $image_type, 'lightbox_type' => $lightbox_image, 'image_link' => $image_link,
      'image_title'   => (bool) $show_title, 'title_link' => $title_link, 'image_desc' => (bool) $show_description, 'image_desc_label' => (bool) $show_description_label,
      'image_date'    => (bool) $show_imgdate, 'image_author' => (bool) $show_imgauthor, 'image_tags' => (bool) $show_tags,
      'image_tags_data' => $image_tags_data, 'image_tags_label' => (bool) $show_tags_label,
    ];
    ?>
    <?php // Images grid ?>
    <?php echo LayoutHelper::render('joomgallery.grids.images', $imgsData); ?>

    <?php // Pagination ?>
    <?php echo $this->item->images->pagination->getListFooter(); ?>
  <?php endif; ?>

  <?php // Link to category overview ?>
  <?php if($browse_categories_link == '2') : ?>
    <div class="center text-center">
      <p><a class="jg-link btn btn-outline-primary"
            href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=1'); ?>">
          <?php echo Text::_('COM_JOOMGALLERY_GALLERY_VIEW_BROWSE_CATEGORIES'); ?>
        </a></p>
    </div>
  <?php endif; ?>
</div>

<?php // load modules on jg_gallery_bottom ?>
<?php $modules = ModuleHelper::getModules('jg_gallery_bottom'); ?>
<?php if(!empty($modules)) : ?>
  <?php foreach($modules as $module) : ?>
    <?php $moduleparams = json_decode($module->params, true); ?>
    <div class="card">
      <?php if($module->showtitle) : ?>
        <?php $moduleheader = '<' . $moduleparams['header_tag'] . ' class="card-header ' . $moduleparams['header_class'] . '">' . htmlspecialchars($module->title) . '</' . $moduleparams['header_tag'] . '>'; ?>
        <?php echo $moduleheader; ?>
      <?php endif; ?>
      <?php echo ModuleHelper::renderModule($module, ['style' => 'none']); ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<script>
  // Add event listener to images
  let loadImg = function () {
    this.closest('.jg-image').classList.add('loaded');
  }
  let images = Array.from(document.getElementsByClassName('jg-image-thumb'));
  images.forEach(image => {
    image.addEventListener('load', loadImg);
  });
</script>
