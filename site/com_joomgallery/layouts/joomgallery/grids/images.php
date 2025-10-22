<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   int      $id                Layout id
 * @var   string   $layout            Layout selection (columns, masonry, justified)
 * @var   array    $items             List of objects that are displayed in a grid layout (id, catid, title, description, date, author)
 * @var   int      $num_columns       Number of columns of this layout
 * @var   string   $caption_align     Alignment class for the caption
 * @var   string   $image_class       Class to be added to the image box
 * @var   string   $image_type        The imagetype used for the grid
 * @var   string   $lightbox_type     The imagetype used for the lightbox
 * @var   string   $image_link        Type of link to be added to the image
 * @var   bool     $image_title       True to display the image title
 * @var   string   $title_link        Type of link to be added to the image title
 * @var   bool     $image_desc        True to display the image description
 * @var   bool     $image_desc_label  True to display the image description label
 * @var   bool     $image_date        True to display the image date
 * @var   bool     $image_author      True to display the image author
 * @var   bool     $image_tags        True to display the image tags
 */
?>

<div class="jg-gallery <?php echo $layout; ?>" itemscope="" itemtype="https://schema.org/ImageGallery">
  <div class="jg-loader"></div>
  <div id="lightgallery-<?php echo $id; ?>" class="jg-images <?php echo $layout; ?>-<?php echo $num_columns; ?> jg-category" data-masonry="{ pollDuration: 175 }">
    <?php $index = 0; ?>
    <?php foreach($items as $key => $item) : ?>

      <div class="jg-image">
        <div class="jg-image-thumbnail<?php if($image_class && $layout != 'justified') : ?><?php echo ' boxed'; ?><?php endif; ?>">
          <?php if($layout != 'justified') : ?>
            <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
          <?php endif; ?>

          <?php if($image_link == 'lightgallery') : ?>
            <a class="lightgallery-item" href="<?php echo JoomHelper::getImg($item, $lightbox_type); ?>" data-sub-html="#jg-image-caption-<?php echo $item->id; ?>" data-thumb="<?php echo JoomHelper::getImg($item, $image_type); ?>">
              <img src="<?php echo JoomHelper::getImg($item, $image_type); ?>" class="jg-image-thumb" alt="<?php echo $item->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if( $layout != 'justified') : ?> loading="lazy"<?php endif; ?>>
              <?php if($image_title && $layout == 'justified') : ?>
                <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
                  <?php echo $this->escape($item->title); ?>
                </div>
              <?php endif; ?>
            </a>
              <?php // lightgallery image caption via data-sub-html ?>
              <?php if($image_title) : ?>
                <div id="jg-image-caption-<?php echo $item->id; ?>" style="display: none">
                  <div class="jg-image-caption <?php echo $caption_align; ?>">
                    <?php echo $this->escape($item->title); ?>
                  </div>
                  <?php if($image_desc) : ?>
                    <div class="jg-image-desc <?php echo $caption_align; ?>">
                      <?php echo $item->description; ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
          <?php elseif($image_link == 'defaultview') : ?>
            <a href="<?php echo Route::_(JoomHelper::getViewRoute('image', (int) $item->id, (int) $item->catid)); ?>">
              <img src="<?php echo JoomHelper::getImg($item, $image_type); ?>" class="jg-image-thumb" alt="<?php echo $item->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if( $layout != 'justified') : ?> loading="lazy"<?php endif; ?>>
              <?php if($image_title && $layout == 'justified') : ?>
                <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
                  <?php echo $this->escape($item->title); ?>
                </div>
              <?php endif; ?>
            </a>
          <?php else : ?>
            <img src="<?php echo JoomHelper::getImg($item, $image_type); ?>" class="jg-image-thumb" alt="<?php echo $item->title; ?>" itemprop="image" itemscope="" itemtype="https://schema.org/image"<?php if( $layout != 'justified') : ?> loading="lazy"<?php endif; ?>>
          <?php endif; ?>

          <?php if($layout != 'justified') : ?>
            </div>
          <?php endif; ?>

          <?php if($layout == 'justified') : ?>
            <div class="jg-image-caption-hover <?php echo $caption_align; ?>">
              <?php echo $this->escape($item->title); ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if($layout != 'justified') : ?>
          <div class="jg-image-caption <?php echo $caption_align; ?>">
            <?php if($image_title) : ?>
              <?php if($title_link == 'lightgallery' && $image_link != 'lightgallery') : ?>
                <a class="lightgallery-item" href="<?php echo JoomHelper::getImg($item, $lightbox_type); ?>" data-sub-html="#jg-image-caption-<?php echo $item->id; ?>" data-thumb="<?php echo JoomHelper::getImg($item, $image_type); ?>">
                  <?php echo $this->escape($item->title); ?>
                </a>
              <?php else : ?>
                <?php if($title_link == 'defaultview') : ?>
                  <a href="<?php echo Route::_(JoomHelper::getViewRoute('image', (int) $item->id, (int) $item->catid)); ?>">
                    <?php echo $this->escape($item->title); ?>
                  </a>
                <?php elseif($title_link != 'lightgallery') : ?>
                  <?php echo $this->escape($item->title); ?>
                <?php endif; ?>
                <?php if($layout != 'justified' && $title_link == 'lightgallery') : ?>
                  <a href="#" class="caption-trigger-<?php echo $id; ?>" data-index="<?php echo $index; ?>">
                    <?php echo $this->escape($item->title); ?>
                  </a>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>

            <?php if($image_desc) : ?>
              <div>
              <?php if($image_desc_label) : ?>
                <?php echo Text::_('JGLOBAL_DESCRIPTION') . ': '; ?>
              <?php endif; ?>
              <?php echo $item->description; ?>
              </div>
            <?php endif; ?>
            <?php if($image_date) : ?>
              <div><?php echo Text::_('COM_JOOMGALLERY_DATE') . ': ' . HTMLHelper::_('date', $item->date, Text::_('DATE_FORMAT_LC4')); ?></div>
            <?php endif; ?>
            <?php if($image_author) : ?>
              <div><?php echo Text::_('JAUTHOR') . ': ' . $this->escape($item->author); ?></div>
            <?php endif; ?>
            <?php if($image_tags) : ?>
              <div><?php echo Text::_('COM_JOOMGALLERY_TAGS') . ': '; ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php $index++; ?>
    <?php endforeach; ?>
  </div>
</div>
