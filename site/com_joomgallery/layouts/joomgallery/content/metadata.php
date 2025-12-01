<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

$exifData = new Registry($displayData);
$i        = 0;
?>

<?php if(\count((array) $exifData->get('exif.IFD0')) > 0) : ?>
  <ul class="metadata list-inline">
    <?php foreach($exifData->get('exif.IFD0') as $key => $value) : ?>
      <?php
        if(\is_object($value))
        {
          // Get object properties as an array
          $value = get_object_vars($value);
        }

        if(\is_array($value))
        {
          // Array to comma separated string
          $value = implode(',', $value);
        }
      ?>
      <li class="list-inline-item metadata-<?php echo $key; ?> metadata-list<?php echo $i; ?>" itemprop="keywords">
        <span><?php echo Text::_($key); ?></span>: <span><?php echo $this->escape($value); ?></span>;
      </li>
      <?php $i++; ?>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
  <span>-</span>
<?php endif; ?>
