<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
******************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

// Image params
$image_type              = $this->params['configs']->get('jg_gallery_view_type_image', 'thumbnail', 'STRING');
$gallery_class           = $this->params['configs']->get('jg_gallery_view_class', 'masonry', 'STRING');
$num_columns             = $this->params['configs']->get('jg_gallery_view_num_columns', 3, 'INT');
$image_class             = $this->params['configs']->get('jg_gallery_view_image_class', 0, 'INT');
$justified_height        = $this->params['configs']->get('jg_gallery_view_justified_height', 200, 'INT');
$justified_gap           = $this->params['configs']->get('jg_gallery_view_justified_gap', 5, 'INT');
$image_link              = $this->params['configs']->get('jg_gallery_view_image_link', 'defaultview', 'STRING');
$lightbox_image          = $this->params['configs']->get('jg_category_view_lightbox_image', 'detail', 'STRING'); // Same as category view
$browse_categories_link  = $this->params['configs']->get('jg_gallery_view_browse_categories_link', 1, 'INT');

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
  $wa->useScript('com_joomgallery.lg-thumbnail');
  $wa->useStyle('com_joomgallery.lightgallery-bundle');
}

// Add and initialize the grid script
$iniJS  = 'window.joomGrid = {';
$iniJS .= '  itemid: ' . $this->item->id . ',';
$iniJS .= '  pagination: 0,';
$iniJS .= '  layout: "' . $gallery_class . '",';
$iniJS .= '  num_columns: ' . $num_columns . ',';
$iniJS .= '  lightbox: ' . ($lightbox ? 'true' : 'false') . ',';
$iniJS .= '  justified: {height: '.$justified_height.', gap: '.$justified_gap.'}';
$iniJS .= '};';

$wa->addInlineScript($iniJS, ['position' => 'before'], [], ['com_joomgallery.joomgrid']);
$wa->useScript('com_joomgallery.joomgrid');
?>

<div class="com-joomgallery-gallery">
  <?php if ($this->params['menu']->get('show_page_heading')) : ?>
    <div class="page-header">
      <h1> <?php echo $this->escape($this->params['menu']->get('page_heading')); ?> </h1>
    </div>
  <?php endif; ?>

  <div class="gallery-header">
    <?php echo HTMLHelper::_('content.prepare', $this->item->description); ?>
  </div>

  <?php // Link to category overview ?>
  <?php if($browse_categories_link == '1') : ?>
    <div class="center text-center">
      <a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=1'); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_GALLERY_VIEW_BROWSE_CATEGORIES'); ?>
      </a>
    </div>
  <?php endif; ?>

  <?php // Hint for no items ?>
  <?php if(count($this->item->images->items) == 0) : ?>
    <p><?php echo Text::_('COM_JOOMGALLERY_GALLERY_NO_IMAGES') ?></p>
  <?php else: ?>
    <?php // Display data array for grid layout
    $imgsData = [ 'id' => (int) $this->item->id, 'layout' => $gallery_class, 'items' => $this->item->images->items, 'num_columns' => (int) $num_columns,
                  'caption_align' => 'center', 'image_class' => $image_class, 'image_type' => $image_type, 'lightbox_type' => $lightbox_image, 'image_link' => $image_link,
                  'image_title' => false, 'title_link' => 'defaultview', 'image_desc' => false, 'image_date' => false,
                  'image_author' => false, 'image_tags' => false
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
      <p><a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=1'); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_GALLERY_VIEW_BROWSE_CATEGORIES'); ?>
      </a></p>
    </div>
  <?php endif; ?>

  <script>
    if(window.joomGrid.layout != 'justified') {
      var loadImg = function() {
        this.closest('.' + window.joomGrid.imgboxclass).classList.add('loaded');
      }

      let images = Array.from(document.getElementsByClassName(window.joomGrid.imgclass));
      images.forEach(image => {
        image.addEventListener('load', loadImg);
      });
    }
  </script>
</div>
