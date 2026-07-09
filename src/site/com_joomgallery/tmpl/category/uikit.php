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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

$subcategory_num_columns   = $this->params['configs']->get('jg_category_view_subcategory_num_columns', 3, 'INT');
$subcategory_image_type    = $this->params['configs']->get('jg_category_view_subcategory_type_images', 'thumbnail', 'STRING');
$numb_subcategories        = $this->params['configs']->get('jg_category_view_numb_subcategories', 12, 'INT');
$subcategories_description = $this->params['configs']->get('jg_category_view_subcategories_category_description', 0, 'INT');
$subcategories_random      = $this->params['configs']->get('jg_category_view_subcategories_random_image', 1, 'INT');
$subcategories_image_count = $this->params['configs']->get('jg_category_view_subcategories_image_count', 0, 'INT');
$num_columns               = $this->params['configs']->get('jg_category_view_num_columns', 3, 'INT');
$image_type                = $this->params['configs']->get('jg_category_view_type_images', 'thumbnail', 'STRING');
$show_title                = $this->params['configs']->get('jg_category_view_images_show_title', 1, 'INT');
$browse_categories_link    = $this->params['configs']->get('jg_category_view_browse_categories_link', 1, 'INT');
$browse_images_link        = $this->params['configs']->get('jg_category_view_browse_images_link', 1, 'INT');
$lightbox_image            = $this->params['configs']->get('jg_lightbox_image', 'detail', 'STRING');
$uikit_gap                 = $this->params['configs']->get('jg_uikit_gallery_gap', 'small', 'STRING');
$uikit_masonry             = $this->params['configs']->get('jg_uikit_gallery_masonry', 0, 'INT');
$uikit_overlay             = $this->params['configs']->get('jg_uikit_gallery_overlay', 'default', 'STRING');
$uikit_title_position      = $this->params['configs']->get('jg_uikit_gallery_title_position', 'overlay', 'STRING');
$uikit_button_text         = $this->params['configs']->get('jg_uikit_gallery_button_text', 'View', 'STRING');
$uikit_lightbox            = $this->params['configs']->get('jg_uikit_gallery_lightbox', 1, 'INT');
$uikit_image_ratio         = $this->params['configs']->get('jg_uikit_gallery_image_ratio', 'original', 'STRING');
?>

<?php if($this->item->pw_protected) : ?>
  <form action="<?php echo Route::_('index.php?task=category.unlock&catid=' . $this->item->id); ?>" method="post" class="uk-form-stacked uk-margin" autocomplete="off">
    <h3><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_PASSWORD_PROTECTED'); ?></h3>
    <label class="uk-form-label" for="jg_password"><?php echo Text::_('JGLOBAL_PASSWORD'); ?></label>
    <input class="uk-input uk-form-width-medium" type="password" name="password" id="jg_password">
    <button type="submit" class="uk-button uk-button-primary uk-button-small" id="jg_unlock_button"><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_BUTTON_UNLOCK'); ?></button>
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
  <?php return; ?>
<?php endif; ?>

<?php foreach(ModuleHelper::getModules('jg_category_top') as $module) : ?>
  <?php $moduleparams = json_decode($module->params, true); ?>
  <div class="uk-card uk-card-default uk-card-body uk-margin">
    <?php if($module->showtitle) : ?>
      <?php echo '<' . $moduleparams['header_tag'] . ' class="uk-card-title ' . $moduleparams['header_class'] . '">' . htmlspecialchars($module->title) . '</' . $moduleparams['header_tag'] . '>'; ?>
    <?php endif; ?>
    <?php echo ModuleHelper::renderModule($module, ['style' => 'none']); ?>
  </div>
<?php endforeach; ?>

<h2 class="uk-heading-bullet">
  <?php echo $this->item->parent_id > 0 ? Text::sprintf('COM_JOOMGALLERY_CATEGORY_TITLE', $this->escape($this->item->title)) : Text::_('COM_JOOMGALLERY'); ?>
</h2>

<?php if($browse_categories_link == '1' || $browse_images_link == '1') : ?>
  <div class="uk-button-group uk-margin">
    <?php if($this->item->parent_id > 0 && $browse_categories_link == '1') : ?>
      <a class="jg-link uk-button uk-button-default uk-button-small" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=' . (int) $this->item->parent_id); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_CATEGORY_BACK_TO_PARENT'); ?>
      </a>
    <?php endif; ?>
    <?php if($browse_images_link == '1') : ?>
      <a class="jg-link uk-button uk-button-default uk-button-small" href="<?php echo Route::_('index.php?option=com_joomgallery&view=gallery'); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_CATEGORY_VIEW_BROWSE_IMAGES'); ?>
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if($this->item->description) : ?>
  <div class="jg-category-description uk-margin">
    <?php echo $this->item->description; ?>
  </div>
<?php endif; ?>

<?php if(\count($this->item->children->items) == 0 && \count($this->item->images->items) == 0) : ?>
  <p><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_NO_ELEMENTS'); ?></p>
<?php endif; ?>

<?php if(\count($this->item->children->items) > 0 && ($this->item->id == 1 || $numb_subcategories > 0)) : ?>
  <h3 class="uk-heading-line uk-text-left"><span><?php echo $this->item->parent_id > 0 ? Text::_('COM_JOOMGALLERY_SUBCATEGORIES') : Text::_('COM_JOOMGALLERY_CATEGORIES'); ?></span></h3>
  <?php
    echo LayoutHelper::render(
        'joomgallery.uikit.subcategories',
        [
          'items'                => $this->item->children->items,
          'num_columns'          => (int) $subcategory_num_columns,
          'image_type'           => $subcategory_image_type,
          'description'          => $subcategories_description,
          'random_image'         => (bool) $subcategories_random,
          'image_count'          => (bool) $subcategories_image_count,
          'uikit_gap'            => $uikit_gap,
          'uikit_masonry'        => (bool) $uikit_masonry,
          'uikit_overlay'        => $uikit_overlay,
          'uikit_title_position' => $uikit_title_position,
          'uikit_button_text'    => $uikit_button_text,
          'uikit_image_ratio'    => $uikit_image_ratio,
        ]
    );
  ?>
  <div class="uk-flex uk-flex-center uk-margin-medium-top">
    <?php echo $this->item->children->pagination->getListFooter(); ?>
  </div>
<?php endif; ?>

<?php foreach(ModuleHelper::getModules('jg_category_before_images') as $module) : ?>
  <?php $moduleparams = json_decode($module->params, true); ?>
  <div class="uk-card uk-card-default uk-card-body uk-margin">
    <?php if($module->showtitle) : ?>
      <?php echo '<' . $moduleparams['header_tag'] . ' class="uk-card-title ' . $moduleparams['header_class'] . '">' . htmlspecialchars($module->title) . '</' . $moduleparams['header_tag'] . '>'; ?>
    <?php endif; ?>
    <?php echo ModuleHelper::renderModule($module, ['style' => 'none']); ?>
  </div>
<?php endforeach; ?>

<?php if(\count($this->item->images->items) > 0) : ?>
  <h3 class="uk-heading-line uk-text-left"><span><?php echo Text::_('COM_JOOMGALLERY_IMAGES'); ?></span></h3>
  <?php
    echo LayoutHelper::render(
        'joomgallery.uikit.images',
        [
          'id'                   => '1-' . $this->item->id,
          'items'                => $this->item->images->items,
          'num_columns'          => (int) $num_columns,
          'image_type'           => $image_type,
          'lightbox_type'        => $lightbox_image,
          'image_link'           => 'defaultview',
          'image_title'          => (bool) $show_title,
          'uikit_gap'            => $uikit_gap,
          'uikit_masonry'        => (bool) $uikit_masonry,
          'uikit_overlay'        => $uikit_overlay,
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

<?php if($browse_categories_link == '2' || $browse_images_link == '2') : ?>
  <div class="uk-button-group uk-margin">
    <?php if($this->item->parent_id > 0 && $browse_categories_link == '2') : ?>
      <a class="jg-link uk-button uk-button-default uk-button-small" href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id=' . (int) $this->item->parent_id); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_CATEGORY_BACK_TO_PARENT'); ?>
      </a>
    <?php endif; ?>
    <?php if($browse_images_link == '2') : ?>
      <a class="jg-link uk-button uk-button-default uk-button-small" href="<?php echo Route::_('index.php?option=com_joomgallery&view=gallery'); ?>">
        <?php echo Text::_('COM_JOOMGALLERY_CATEGORY_VIEW_BROWSE_IMAGES'); ?>
      </a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php foreach(ModuleHelper::getModules('jg_category_bottom') as $module) : ?>
  <?php $moduleparams = json_decode($module->params, true); ?>
  <div class="uk-card uk-card-default uk-card-body uk-margin">
    <?php if($module->showtitle) : ?>
      <?php echo '<' . $moduleparams['header_tag'] . ' class="uk-card-title ' . $moduleparams['header_class'] . '">' . htmlspecialchars($module->title) . '</' . $moduleparams['header_tag'] . '>'; ?>
    <?php endif; ?>
    <?php echo ModuleHelper::renderModule($module, ['style' => 'none']); ?>
  </div>
<?php endforeach; ?>
