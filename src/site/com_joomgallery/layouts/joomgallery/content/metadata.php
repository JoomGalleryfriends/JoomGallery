<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

\defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

$metadata = $displayData instanceof Registry ? $displayData : new Registry($displayData);
?>
<?php foreach($metadata->get('items', []) as $item) : ?>
  <dt class="col-sm-3 col-lg-2"><?php echo $this->escape(Text::_($item['label'])); ?></dt>
  <dd class="col-sm-9 col-lg-10"><?php echo $this->escape($item['value']); ?></dd>
<?php endforeach; ?>
