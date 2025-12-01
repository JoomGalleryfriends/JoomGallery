<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
******************************************************************************************/

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.site');
$wa->useStyle('com_joomgallery.jg-icon-font');
?>

<?php if($this->item->pw_protected): ?>
  <form action="<?php echo Route::_('index.php?task=category.unlock&catid='.$this->item->id);?>" method="post" class="form-inline" autocomplete="off">
    <h3><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_PASSWORD_PROTECTED'); ?></h3>
    <label for="jg_password"><?php echo Text::_('JGLOBAL_PASSWORD'); ?></label>
    <input type="password" name="password" id="jg_password" />
    <button type="submit" class="btn btn-primary" id="jg_unlock_button"><?php echo Text::_('COM_JOOMGALLERY_CATEGORY_BUTTON_UNLOCK'); ?></button>
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
<?php else: ?>
  <?php echo $this->loadTemplate('cat'); ?>
<?php endif; ?>
