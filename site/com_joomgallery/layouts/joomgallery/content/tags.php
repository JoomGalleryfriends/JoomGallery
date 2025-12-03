<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

$nmb = \count((array) $displayData);
?>

<?php if(!empty($displayData) && \count((array) $displayData) > 0) : ?>
  <ul class="tags list-inline">
    <?php foreach($displayData as $i => $tag) : ?>
      <li class="list-inline-item tag-<?php echo $tag->id; ?> tag-list<?php echo $i; ?>" itemprop="keywords">
        <span class="badge text-bg-primary"><?php echo $this->escape($tag->title); ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <span>-</span>
<?php endif; ?>
