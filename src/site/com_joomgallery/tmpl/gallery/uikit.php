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

$image_type             = $this->params['configs']->get('jg_gallery_view_type_image', 'thumbnail', 'STRING');
$num_columns            = $this->params['configs']->get('jg_gallery_view_num_columns', 3, 'INT');
$image_link             = $this->params['configs']->get('jg_gallery_view_image_link', 'defaultview', 'STRING');
$browse_categories_link = $this->params['configs']->get('jg_gallery_view_browse_categories_link', 1, 'INT');
$lightbox_image         = $this->params['configs']->get('jg_lightbox_image', 'detail', 'STRING');
$uikit_gap              = $this->params['configs']->get('jg_uikit_gallery_gap', 'small', 'STRING');
$uikit_masonry          = $this->params['configs']->get('jg_uikit_gallery_masonry', 0, 'INT');
$uikit_overlay          = $this->params['configs']->get('jg_uikit_gallery_overlay', 'default', 'STRING');
$uikit_overlay_text     = $this->params['configs']->get('jg_uikit_gallery_overlay_text', 'inherit', 'STRING');
$uikit_title_position   = $this->params['configs']->get('jg_uikit_gallery_title_position', 'overlay', 'STRING');
$uikit_button_text      = $this->params['configs']->get('jg_uikit_gallery_button_text', 'View', 'STRING');
$uikit_lightbox         = $this->params['configs']->get('jg_uikit_gallery_lightbox', 1, 'INT');
$uikit_image_ratio      = $this->params['configs']->get('jg_uikit_gallery_image_ratio', 'original', 'STRING');
?>

<div class="com-joomgallery-gallery uk-margin">
  <?php if($this->params['menu']->get('show_page_heading')) : ?>
    <div class="uk-margin-medium-bottom">
      <h1 class="uk-heading-bullet"><?php echo $this->escape($this->params['menu']->get('page_heading')); ?></h1>
    </div>
  <?php endif; ?>

  <?php $modules = ModuleHelper::getModules('jg_gallery_top'); ?>
  <?php foreach($modules as $module) : ?>
    <?php $moduleparams = json_decode($module->params, true); ?>
    <div class="uk-card uk-card-default uk-card-body uk-margin">
      <?php if($module->showtitle) : ?>
        <?php echo '<' . $moduleparams['header_tag'] . ' class="uk-card-title ' . $moduleparams['header_class'] . '">' . htmlspecialchars($module->title) . '</' . $moduleparams['header_tag'] . '>'; ?>
      <?php endif; ?>
      <?php echo ModuleHelper::renderModule($module, ['style' => 'none']); ?>
    </div>
  <?php endforeach; ?>

  <?php if($this->item->description) : ?>
    <div class="gallery-header uk-margin">
      <?php echo HTMLHelper::_('content.prepare', $this->item->description, '', 'com_joomgallery.gallery'); ?>
    </div>
  <?php endif; ?>

  <?php if($browse_categories_link == '1') : ?>
    <div class="uk-text-center uk-margin">
      <a class="jg-link uk-button uk-button-default uk-button-small" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=1'); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_GALLERY_VIEW_BROWSE_CATEGORIES'); ?>
      </a>
    </div>
  <?php endif; ?>

  <?php if(\count($this->item->images->items) == 0) : ?>
    <p><?php echo Text::_('COM_JOOMGALLERY_GALLERY_NO_IMAGES'); ?></p>
  <?php else : ?>
    <?php
    echo LayoutHelper::render(
        'joomgallery.uikit.images',
        [
          'id'                   => '1-' . $this->item->id,
          'items'                => $this->item->images->items,
          'num_columns'          => (int) $num_columns,
          'image_type'           => $image_type,
          'lightbox_type'        => $lightbox_image,
          'image_link'           => $image_link,
          'image_title'          => true,
          'uikit_gap'            => $uikit_gap,
          'uikit_masonry'        => (bool) $uikit_masonry,
          'uikit_overlay'        => $uikit_overlay,
          'uikit_overlay_text'   => $uikit_overlay_text,
          'uikit_title_position' => $uikit_title_position,
          'uikit_button_text'    => $uikit_button_text,
          'uikit_lightbox'       => (bool) $uikit_lightbox,
          'uikit_image_ratio'    => $uikit_image_ratio,
        ]
    );
    ?>
    <div class="uk-flex uk-flex-center uk-margin-medium-top">
      <?php echo $this->item->images->pagination->getListFooter(); ?>
    </div>
  <?php endif; ?>

  <?php if($browse_categories_link == '2') : ?>
    <div class="uk-text-center uk-margin">
      <a class="jg-link uk-button uk-button-default uk-button-small" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=1'); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_GALLERY_VIEW_BROWSE_CATEGORIES'); ?>
      </a>
    </div>
  <?php endif; ?>
</div>

<?php $modules = ModuleHelper::getModules('jg_gallery_bottom'); ?>
<?php foreach($modules as $module) : ?>
  <?php $moduleparams = json_decode($module->params, true); ?>
  <div class="uk-card uk-card-default uk-card-body uk-margin">
    <?php if($module->showtitle) : ?>
      <?php echo '<' . $moduleparams['header_tag'] . ' class="uk-card-title ' . $moduleparams['header_class'] . '">' . htmlspecialchars($module->title) . '</' . $moduleparams['header_tag'] . '>'; ?>
    <?php endif; ?>
    <?php echo ModuleHelper::renderModule($module, ['style' => 'none']); ?>
  </div>
<?php endforeach; ?>
