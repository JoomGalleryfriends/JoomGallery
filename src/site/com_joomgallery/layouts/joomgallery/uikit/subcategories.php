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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

extract($displayData);

$columns         = max(1, min(6, (int) ($num_columns ?? 3)));
$gap             = $uikit_gap ?? 'small';
$masonry         = !empty($uikit_masonry);
$overlay         = $uikit_overlay ?? 'default';
$overlay_text    = $uikit_overlay_text ?? 'inherit';
$title_position  = $uikit_title_position ?? 'overlay';
$button_text     = $uikit_button_text ?? 'View';
$image_ratio     = $uikit_image_ratio ?? 'original';
$ratio_map       = [
  'square'    => [1000, 1000],
  'landscape' => [1200, 900],
  'wide'      => [1600, 900],
  'portrait'  => [900, 1200],
];
$ratio           = $ratio_map[$image_ratio] ?? null;
$grid_attributes = $masonry ? ' uk-grid="masonry: pack"' : ' uk-grid';
$grid_class      = 'uk-grid uk-child-width-1-1 uk-child-width-1-2@s uk-child-width-1-' . $columns . '@m uk-grid-' . $gap;
$overlay_text_class = \in_array($overlay_text, ['light', 'dark'], true) ? ' uk-' . $overlay_text : '';
?>

<div class="jg-images jg-subcategories <?php echo $grid_class; ?>"<?php echo $grid_attributes; ?>>
  <?php foreach($items as $item) : ?>
    <?php
      $img_type = $image_type;

      if($item->thumbnail == 0 && $random_image)
      {
        $item->thumbnail = $item->id;
        $img_type        = 'rnd_cat:' . $image_type;
      }
    ?>
    <div class="jg-image">
      <div class="el-item">
        <a class="uk-display-block uk-width-1-1 uk-transition-toggle uk-inline-clip uk-link-toggle<?php echo $ratio ? ' uk-cover-container' : ''; ?>"
           href="<?php echo Route::_(JoomHelper::getViewRoute('category', (int) $item->id)); ?>">
          <?php if($ratio) : ?>
            <canvas width="<?php echo $ratio[0]; ?>" height="<?php echo $ratio[1]; ?>"></canvas>
          <?php endif; ?>
          <img class="jg-image-thumb el-image uk-transition-opaque"
               src="<?php echo JoomHelper::getImg($item->thumbnail, $img_type); ?>"
               alt="<?php echo $this->escape($item->title); ?>"
               loading="lazy"
               <?php echo $ratio ? 'uk-cover' : ''; ?>>
          <?php if($overlay != 'none') : ?>
            <div class="uk-overlay-<?php echo $overlay; ?> uk-transition-fade uk-position-cover"></div>
            <div class="uk-position-center uk-transition-fade">
              <div class="uk-overlay uk-margin-remove-first-child uk-text-center<?php echo $overlay_text_class; ?>">
                <?php if($title_position == 'overlay') : ?>
                  <div class="uk-h4"><?php echo $this->escape($item->title); ?></div>
                <?php endif; ?>
                <div class="uk-margin-top"><span class="el-link uk-button uk-button-text"><?php echo $this->escape($button_text); ?></span></div>
              </div>
            </div>
          <?php endif; ?>
        </a>
        <?php if($title_position == 'below') : ?>
          <div class="jg-image-caption uk-text-center uk-margin-small-top">
            <a class="jg-link" href="<?php echo Route::_(JoomHelper::getViewRoute('category', (int) $item->id)); ?>">
              <?php echo $this->escape($item->title); ?>
            </a>
            <?php if(!empty($image_count)) : ?>
              <?php $numberofimages = JoomHelper::getTotalImagesInCategory($item->id); ?>
              <div class="uk-text-meta">
                <?php echo Text::sprintf($numberofimages === 1 ? 'COM_JOOMGALLERY_NUMBER_IMAGE' : 'COM_JOOMGALLERY_NUMBER_IMAGES', $numberofimages); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
