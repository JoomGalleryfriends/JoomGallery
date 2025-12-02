<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Router\Route;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $layout          Layout selection (columns, masonry, justified)
 * @var   array    $items           List of objects that are displayed in a grid layout (properties: id, title, thumbnail)
 * @var   int      $num_columns     Number of columns of this layout
 * @var   string   $image_type      The imagetype used for the grid
 * @var   string   $image_class     Class to be added to the image box
 * @var   string   $caption_align   Alignment class for the caption
 * @var   string   $description     Category description
 * @var   bool     $random_image    True, if a random inage should be loaded (only for categories)
 */
?>

<div class="jg-gallery" itemscope="" itemtype="https://schema.org/ImageGallery">
  <div class="jg-loader"></div>
  <div class="jg-images <?php echo $layout; ?>-<?php echo $num_columns; ?> jg-subcategories" data-masonry="{ pollDuration: 175 }">
    <?php foreach($items as $key => $item) : ?>
      <?php
        $img_type = $image_type;
        if($item->thumbnail == 0 && $random_image)
        {
          $item->thumbnail = $item->id;
          $img_type = 'rnd_cat:' . $image_type;
        }
      ?>

      <div class="jg-image">
        <div class="jg-image-thumbnail<?php if($image_class && $layout != 'justified') : ?><?php echo ' boxed'; ?><?php endif; ?>">
          <a href="<?php echo Route::_(JoomHelper::getViewRoute('category', (int) $item->id)); ?>">
            <img src="<?php echo JoomHelper::getImg($item->thumbnail, $img_type); ?>" class="jg-image-thumb" alt="<?php echo $this->escape($item->title); ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if( $layout != 'justified') : ?> loading="lazy"<?php endif; ?>>
            <?php if($layout == 'justified') : ?>
              <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
                <?php echo $this->escape($item->title); ?>
              </div>
            <?php endif; ?>
          </a>
        </div>
        <?php if($layout != 'justified') : ?>
          <div class="jg-image-caption <?php echo $caption_align; ?>">
            <a class="jg-link" href="<?php echo Route::_(JoomHelper::getViewRoute('category', (int) $item->id)); ?>">
              <?php echo $this->escape($item->title); ?>
            </a>
          </div>
          <?php if($description) : ?>
            <?php echo $item->description; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
