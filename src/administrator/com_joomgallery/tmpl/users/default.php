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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')->useScript('com_joomgallery.admin');

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=users'); ?>" method="post" name="adminForm" id="adminForm">
  <div id="j-main-container" class="j-main-container">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
    <?php if(empty($this->items)) : ?>
      <div class="alert alert-info">
        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
      </div>
    <?php else : ?>
      <div class="table-responsive">
        <table class="table table-striped" id="userList">
          <thead><tr>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'u.name', $listDirn, $listOrder); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_USERNAME', 'u.username', $listDirn, $listOrder); ?></th>
            <th><?php echo Text::_('JGLOBAL_EMAIL'); ?></th>
            <th class="text-center"><?php echo Text::_('COM_JOOMGALLERY_COLLECTIONS'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_FIELD_CREATED_LABEL', 'a.created_time', $listDirn, $listOrder); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
          </tr></thead>
          <tbody>
          <?php foreach($this->items as $item) : ?>
            <tr>
              <th scope="row"><a href="<?php echo Route::_('index.php?option=com_users&view=user&layout=edit&id=' . (int) $item->cmsuser); ?>"><?php echo $this->escape($item->name); ?></a></th>
              <td><?php echo $this->escape($item->username); ?></td>
              <td><?php echo $this->escape($item->email); ?></td>
              <td class="text-center"><?php echo (int) $item->collection_count; ?></td>
              <td><?php echo HTMLHelper::_('date', $item->created_time, Text::_('DATE_FORMAT_LC4')); ?></td>
              <td><?php echo (int) $item->id; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php echo $this->pagination->getListFooter(); ?>
    <?php endif; ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
  </div>
</form>
