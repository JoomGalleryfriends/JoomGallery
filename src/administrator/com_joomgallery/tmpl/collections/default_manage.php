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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$item        = $this->managementItem;
$permissions = $item->managementPermissions;
?>
<?php if((int) $item->is_default) : ?>
  <p class="alert alert-info"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DEFAULT_NOTICE'); ?></p>
<?php endif; ?>
<?php if($permissions['reassign']) : ?>
  <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.manage'); ?>" method="post" class="mb-4">
    <label class="form-label" for="collection-owner-<?php echo (int) $item->id; ?>"><?php echo Text::_('COM_JOOMGALLERY_OWNER'); ?></label>
    <?php echo HTMLHelper::_('select.genericlist', $this->ownerOptions, 'userid', 'class="form-select mb-3" required', 'value', 'text', $item->userid, 'collection-owner-' . (int) $item->id); ?>
    <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_REASSIGN'); ?></button>
    <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
    <input type="hidden" name="operation" value="reassign">
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
<?php endif; ?>
<?php if($permissions['clear']) : ?>
  <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.manage'); ?>" method="post" class="mb-4">
    <p><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_CLEAR_DESC'); ?></p>
    <button type="submit" class="btn btn-warning"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_CLEAR'); ?></button>
    <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
    <input type="hidden" name="operation" value="clear">
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
<?php endif; ?>
<?php if($permissions['delete']) : ?>
  <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.manage'); ?>" method="post">
    <p><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DELETE_DESC'); ?></p>
    <button type="submit" class="btn btn-danger"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DELETE'); ?></button>
    <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
    <input type="hidden" name="operation" value="delete">
    <?php echo HTMLHelper::_('form.token'); ?>
  </form>
<?php endif; ?>
