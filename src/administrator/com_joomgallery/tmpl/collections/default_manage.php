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
<?php if($permissions['owner'] && (int) $item->image_count > 0) : ?>
  <section class="pb-4 mb-4 border-bottom">
    <h3 class="h5 mb-2"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD'); ?></h3>
    <p class="text-body-secondary"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_DESC'); ?></p>
    <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.download&id=' . (int) $item->id . '&type=detail'); ?>">
      <span class="icon-download" aria-hidden="true"></span>
      <?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD'); ?>
    </a>
  </section>
<?php endif; ?>
<?php if($permissions['reassign']) : ?>
  <section class="pb-4 mb-4 border-bottom">
    <h3 class="h5 mb-2"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_MANAGE_OWNER'); ?></h3>
    <p class="text-body-secondary"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_REASSIGN_DESC'); ?></p>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.manage'); ?>" method="post">
      <label class="form-label" for="collection-owner-<?php echo (int) $item->id; ?>"><?php echo Text::_('COM_JOOMGALLERY_OWNER'); ?></label>
      <?php echo HTMLHelper::_('select.genericlist', $this->ownerOptions, 'userid', 'class="form-select mb-3" required', 'value', 'text', $item->userid, 'collection-owner-' . (int) $item->id); ?>
      <button type="submit" class="btn btn-primary"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_REASSIGN'); ?></button>
      <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
      <input type="hidden" name="operation" value="reassign">
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  </section>
<?php endif; ?>
<?php if($permissions['clear']) : ?>
  <section class="pb-4 mb-4 border-bottom">
    <h3 class="h5 mb-2"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_MANAGE_IMAGES'); ?></h3>
    <p class="text-body-secondary"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_CLEAR_DESC'); ?></p>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.manage'); ?>" method="post">
      <button type="submit" class="btn btn-warning"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_CLEAR'); ?></button>
      <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
      <input type="hidden" name="operation" value="clear">
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  </section>
<?php endif; ?>
<?php if($permissions['state']) : ?>
  <section class="pb-4 mb-4 border-bottom">
    <h3 class="h5 mb-2"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_MANAGE_STATE'); ?></h3>
    <p class="text-body-secondary"><?php echo Text::sprintf('COM_JOOMGALLERY_COLLECTION_STATE_DESC', Text::_((int) $item->published ? 'JPUBLISHED' : 'JUNPUBLISHED')); ?></p>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.manage'); ?>" method="post">
      <button type="submit" class="btn btn-secondary">
        <span class="<?php echo (int) $item->published ? 'icon-unpublish' : 'icon-publish'; ?>" aria-hidden="true"></span>
        <?php echo Text::_((int) $item->published ? 'UNPUBLISH' : 'PUBLISH'); ?>
      </button>
      <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
      <input type="hidden" name="operation" value="<?php echo (int) $item->published ? 'unpublish' : 'publish'; ?>">
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  </section>
<?php endif; ?>
<?php if($permissions['delete']) : ?>
  <section class="border border-danger rounded p-3">
    <h3 class="h5 text-danger mb-2"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_MANAGE_DELETE'); ?></h3>
    <p><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DELETE_DESC'); ?></p>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&task=collection.manage'); ?>" method="post">
      <button type="submit" class="btn btn-danger"><span class="icon-delete" aria-hidden="true"></span> <?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DELETE'); ?></button>
      <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
      <input type="hidden" name="operation" value="delete">
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  </section>
<?php endif; ?>
