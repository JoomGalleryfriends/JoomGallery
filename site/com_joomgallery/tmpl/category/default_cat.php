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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// Subcategory params
$subcategory_class           = $this->params['configs']->get('jg_category_view_subcategory_class', 'masonry', 'STRING');
$subcategory_num_columns     = $this->params['configs']->get('jg_category_view_subcategory_num_columns', 3, 'INT');
$subcategory_image_class     = $this->params['configs']->get('jg_category_view_subcategory_image_class', 0, 'INT');
$subcategory_image_type      = $this->params['configs']->get('jg_category_view_subcategory_type_images', 'thumbnail', 'STRING');
$numb_subcategories          = $this->params['configs']->get('jg_category_view_numb_subcategories', 12, 'INT');
$subcategories_pagination    = $this->params['configs']->get('jg_category_view_subcategories_pagination', 0, 'INT');
$subcategories_caption_align = $this->params['configs']->get('jg_category_view_subcategories_caption_align', 'left', 'STRING');
$subcategories_random_image  = $this->params['configs']->get('jg_category_view_subcategories_random_image', 1, 'INT');

// Image params
$category_class   = $this->params['configs']->get('jg_category_view_class', 'masonry', 'STRING');
$num_columns      = $this->params['configs']->get('jg_category_view_num_columns', 6, 'INT');
$image_type       = $this->params['configs']->get('jg_category_view_type_images', 'thumbnail', 'STRING');
$caption_align    = $this->params['configs']->get('jg_category_view_caption_align', 'right', 'STRING');
$image_class      = $this->params['configs']->get('jg_category_view_image_class', 0, 'INT');
$justified_height = $this->params['configs']->get('jg_category_view_justified_height', 320, 'INT');
$justified_gap    = $this->params['configs']->get('jg_category_view_justified_gap', 5, 'INT');
$show_title       = $this->params['configs']->get('jg_category_view_images_show_title', 0, 'INT');
$numb_images      = $this->params['configs']->get('jg_category_view_numb_images', 12, 'INT');
$use_pagination   = $this->params['configs']->get('jg_category_view_pagination', 0, 'INT');
$reloaded_images  = $this->params['configs']->get('jg_category_view_number_of_reloaded_images', 3, 'INT');
$image_link       = $this->params['configs']->get('jg_category_view_image_link', 'defaultview', 'STRING');
$title_link       = $this->params['configs']->get('jg_category_view_title_link', 'defaultview', 'STRING');
$lightbox_image   = $this->params['configs']->get('jg_category_view_lightbox_image', 'detail', 'STRING');
$thumbnails       = $this->params['configs']->get('jg_category_view_lightbox_thumbnails', 0, 'INT');
$zoom             = $this->params['configs']->get('jg_category_view_lightbox_zoom', 0, 'INT');
$show_description = $this->params['configs']->get('jg_category_view_show_description', 0, 'INT');
$show_imgdate     = $this->params['configs']->get('jg_category_view_show_imgdate', 0, 'INT');
$show_imgauthor   = $this->params['configs']->get('jg_category_view_show_imgauthor', 0, 'INT');
$show_tags        = $this->params['configs']->get('jg_category_view_show_tags', 0, 'INT');
$browse_images_link  = $this->params['configs']->get('jg_category_view_browse_images_link', 1, 'INT');

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');

?>

<?php // Password protected category form ?>
<?php if($this->item->pw_protected): ?>
  <form action="<?php echo Route::_('index.php?task=category.unlock&catid='.$this->item->id);?>" method="post" class="form-inline" autocomplete="off">
    <h3><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_PASSWORD_PROTECTED'); ?></h3>
    <label for="jg_password"><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_PASSWORD'); ?></label>
    <input type="password" name="password" id="jg_password" />
    <button type="submit" class="btn btn-primary" id="jg_unlock_button"><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_BUTTON_UNLOCK'); ?></button>
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <?php return; ?>
<?php endif; ?>

<?php // Import CSS & JS
if($subcategory_class == 'masonry' || $category_class == 'masonry')
{
  $wa->useScript('com_joomgallery.masonry');
}

if($category_class == 'justified')
{
  $wa->useScript('com_joomgallery.justified');
  $wa->addInlineStyle('.jg-images[class*=" justified-"] .jg-image-caption-hover { right: ' . $justified_gap . 'px; }');
}

$lightbox = false;
if($image_link == 'lightgallery' || $title_link == 'lightgallery')
{
  $lightbox = true;

  $wa->useScript('com_joomgallery.lightgallery');
  $wa->useScript('com_joomgallery.lg-thumbnail');
  $wa->useScript('com_joomgallery.lg-zoom');
  $wa->useStyle('com_joomgallery.lightgallery-bundle');
}

if(!empty($use_pagination))
{
  // $wa->useScript('com_joomgallery.infinite-scroll');
}

// Add and initialize the grid script
$iniJS  = 'window.joomGrid["1-'.$this->item->id.'"] = {';
$iniJS .= '  itemid: "1-' . $this->item->id . '",';
$iniJS .= '  pagination: ' . $use_pagination . ',';
$iniJS .= '  layout: "' . $category_class . '",';
$iniJS .= '  num_columns: ' . $num_columns . ',';
$iniJS .= '  lightbox: ' . ($lightbox ? 'true' : 'false') . ',';
$iniJS .= '  lightbox_params: {container: "lightgallery-1-'.$this->item->id.'", selector: ".lightgallery-item"},';
$iniJS .= '  thumbnails: ' . ($thumbnails ? 'true' : 'false') . ',';
$iniJS .= '  zoom: ' . ($zoom ? 'true' : 'false') . ',';
$iniJS .= '  justified: {height: '.$justified_height.', gap: '.$justified_gap.'}';
$iniJS .= '};';

$wa->useScript('com_joomgallery.joomgrid');
$wa->addInlineScript($iniJS, ['position' => 'after'], [], ['com_joomgallery.joomgrid']);

// Permission checks
$canEdit    = $this->getAcl()->checkACL('edit', 'com_joomgallery.category', $this->item->id);
$canAdd     = $this->getAcl()->checkACL('add', 'com_joomgallery.category', 0, $this->item->id, true);
if($this->item->id > 1)
{
  $canAddImg  = $this->getAcl()->checkACL('add', 'com_joomgallery.image', 0, $this->item->id, true);
}
else
{
  $canAddImg  = true;
}
$canDelete  = $this->getAcl()->checkACL('delete', 'com_joomgallery.category', $this->item->id);
$canCheckin = $this->getAcl()->checkACL('editstate', 'com_joomgallery.category', $this->item->id) || $this->item->checked_out == Factory::getUser()->id;
$returnURL  = base64_encode(JoomHelper::getViewRoute('category', $this->item->id, $this->item->parent_id, $this->item->language, $this->getLayout()));
?>

<?php // Category title ?>
<?php if($this->item->parent_id > 0) : ?>
  <h2><?php echo Text::sprintf('COM_JOOMGALLERY_CATEGORY_TITLE', $this->escape($this->item->title)); ?></h2>
<?php else : ?>
  <h2><?php echo Text::_('COM_JOOMGALLERY') ?></h2>
<?php endif; ?>

<?php // Back to parent category ?>
<?php if($this->item->parent_id > 0) : ?>
  <a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $this->item->parent_id); ?>">
    <i class="jg-icon-arrow-left-alt"></i><span><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_BACK_TO_PARENT'); ?></span>
  </a>
<?php endif; ?>

<?php // Browse images ?>
<?php if($browse_images_link == '1') : ?>
  <a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=gallery'); ?>">
    <?php echo Text::_('COM_JOOMGALLERY_CATEGORY_VIEW_BROWSE_IMAGES'); ?>
  </a>
<?php endif; ?>

<?php // Edit buttons ?>
<?php if($canEdit || $canAdd || $canDelete): ?>
  <div class="mb-3">
    <?php if($canEdit): ?>
      <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id='.$this->item->id.'&return='.$returnURL); ?>">
        <i class="jg-icon-edit"></i><span><?php echo Text::_("JACTION_EDIT"); ?></span>
      </a>
    <?php endif; ?>

    <?php /*if($canAdd): ?>
      <a class="btn btn-outline-success" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.add&id=0&catid='.$this->item->id.'&return='.$returnURL); ?>">
        <?php echo Text::_("JGLOBAL_FIELD_ADD"); ?>
      </a>
    <?php endif; */?>

    <?php if($canDelete) : ?>
      <a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
        <i class="jg-icon-delete"></i><span><?php echo Text::_("JACTION_DELETE"); ?></span>
      </a>
      <?php echo HTMLHelper::_( 'bootstrap.renderModal',
                                'deleteModal',
                                array(
                                    'title'  => Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION'),
                                    'height' => '50%',
                                    'width'  => '20%',
                                    'modalWidth'  => '50',
                                    'bodyHeight'  => '100',
                                    'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_joomgallery&task=category.remove&id='. $this->item->id.'&return='.$returnURL.'&'.Session::getFormToken().'=1', false, 2) .'" class="btn btn-danger">' . Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION') .'</a>'
                                ),
                                Text::_('COM_JOOMGALLERY_COMMON_ALERT_SURE_DELETE_SELECTED_ITEM')
                              );
      ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php // Category text ?>
<p><?php echo $this->item->description; ?></p>

<?php // Hint for no items ?>
<?php if(count($this->item->children->items) == 0 && count($this->item->images->items) == 0) : ?>
  <p><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_NO_ELEMENTS') ?></p>
<?php endif; ?>

<?php // Subcategories ?>
<?php if(count($this->item->children->items) > 0 && ($this->item->id == 1 || $numb_subcategories  > 0)) : ?>
  <?php if($this->item->parent_id > 0) : ?>
    <h3><?php echo Text::_('COM_JOOMGALLERY_SUBCATEGORIES') ?></h3>
  <?php else : ?>
    <h3><?php echo Text::_('COM_JOOMGALLERY_CATEGORIES') ?></h3>
  <?php endif; ?>

  <?php // Display data array for layout
    $subcatData = [ 'layout' => $subcategory_class, 'items' => $this->item->children->items, 'num_columns' => (int) $subcategory_num_columns, 'image_type' => $subcategory_image_type,
                    'caption_align' => $subcategories_caption_align, 'image_class' => $subcategory_image_class, 'random_image' => (bool) $subcategories_random_image
                  ];
  ?>

  <?php // Subcategories grid ?>
  <?php echo LayoutHelper::render('joomgallery.grids.subcategories', $subcatData); ?>
  <?php if($subcategories_pagination == 0) : ?>
    <?php echo $this->item->children->pagination->getListFooter(); ?>
  <?php endif; ?>

<?php endif; ?>

<?php // Category ?>
<?php if(count($this->item->images->items) > 0) : ?>
  <h3><?php echo Text::_('COM_JOOMGALLERY_IMAGES') ?></h3>
  <?php if(!empty($this->item->images->filterForm) && $use_pagination == '0') : ?>
    <?php // Show image filters ?>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.$this->item->id.'&Itemid='.$this->menu->id); ?>" method="post" name="adminForm" id="adminForm">
      <?php
        {
          echo LayoutHelper::render('joomla.searchtools.default', array(
            'view' => $this->item->images, 
            'options' => array('showSelector' => false, 'filterButton' => false, 'showNoResults' => false, 'showSearch' => false, 'showList' => false, 'barClass' => 'flex-end')
          ));
        }
      ?>
      <input type="hidden" name="contenttype" value="image"/>
      <input type="hidden" name="task" value=""/>
      <input type="hidden" name="filter_order" value=""/>
      <input type="hidden" name="filter_order_Dir" value=""/>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  <?php endif; ?>

  <?php // Display data array for layout
    $imgsData = [ 'id' => '1-'.$this->item->id, 'layout' => $category_class, 'items' => $this->item->images->items, 'num_columns' => (int) $num_columns,
                  'caption_align' => $caption_align, 'image_class' => $image_class, 'image_type' => $image_type, 'lightbox_type' => $lightbox_image, 'image_link' => $image_link,
                  'image_title' => (bool) $show_title, 'title_link' => $title_link, 'image_desc' => (bool) $show_description, 'image_date' => (bool) $show_imgdate,
                  'image_author' => (bool) $show_imgauthor, 'image_tags' => (bool) $show_tags
                ];
  ?>

  <?php // Images grid ?>
  <?php echo LayoutHelper::render('joomgallery.grids.images', $imgsData); ?>

  <?php // Pagination ?>
  <?php if($use_pagination == 1) : ?>
  <div class="load-more-container">
    <div class="infinite-scroll"></div>
    <div id="noMore" class="btn btn-outline-primary no-more-images hidden"><?php echo Text::_('COM_JOOMGALLERY_NO_MORE_IMAGES') ?></div>
  </div>
  <?php elseif($use_pagination == 2) : ?>
    <div class="load-more-container">
      <div id="loadMore" class="btn btn-outline-primary load-more"><span><?php echo Text::_('COM_JOOMGALLERY_LOAD_MORE') ?></span><i class="jg-icon-expand-more"></i></div>
      <div id="noMore" class="btn btn-outline-primary no-more-images hidden"><?php echo Text::_('COM_JOOMGALLERY_NO_MORE_IMAGES') ?></div>
    </div>
  <?php else : ?>
    <?php echo $this->item->images->pagination->getListFooter(); ?>
  <?php endif; ?>
<?php endif; ?>

<?php // Add image button ?>
<?php /*if($canAddImg) : ?>
  <div class="mb-2">
    <a href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.add&id=0&catid='.$this->item->id.'&return='.$returnURL, false, 0); ?>" class="btn btn-success btn-small">
      <i class="icon-plus"></i>
      <?php echo Text::_('COM_JOOMGALLERY_IMG_UPLOAD_IMAGE'); ?>
    </a>
  </div>
<?php endif; */?>

<?php // Browse images ?>
<?php if($browse_images_link == '2') : ?>
  <div class="center text-center">
    <p><a class="jg-link btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&view=gallery'); ?>">
      <?php echo Text::_('COM_JOOMGALLERY_CATEGORY_VIEW_BROWSE_IMAGES'); ?>
    </a></p>
  </div>
<?php endif; ?>

<script>
  // Add event listener to images
  let loadImg = function() {
    this.closest('.jg-image').classList.add('loaded');
  }
  let images = Array.from(document.getElementsByClassName('jg-image-thumb'));
  images.forEach(image => {
    image.addEventListener('load', loadImg);
  });
</script>