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
$wa->useStyle('com_joomgallery.admin')->useScript('com_joomgallery.admin')->useScript('bootstrap.modal');
$modals = [];

$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=collections'); ?>" method="post"
    name="adminForm" id="adminForm">
  <div id="j-main-container" class="j-main-container">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
    <?php if(empty($this->items)) : ?>
      <div class="alert alert-info">
        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
      </div>
    <?php else : ?>
      <div class="table-responsive">
        <table class="table table-striped" id="collectionList">
          <thead><tr>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMGALLERY_OWNER', 'u.name', $listDirn, $listOrder); ?></th>
            <th class="text-center"><?php echo Text::_('COM_JOOMGALLERY_COLLECTION_DEFAULT'); ?></th>
            <th class="text-center"><?php echo Text::_('COM_JOOMGALLERY_IMAGES'); ?></th>
            <th><?php echo Text::_('JACTION_EDIT'); ?></th>
            <th><?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?></th>
          </tr></thead>
          <tbody>
          <?php foreach($this->items as $item) : ?>
            <tr>
              <th scope="row"><?php echo $this->escape($item->title); ?></th>
              <td><?php echo $this->escape($item->owner); ?> (<?php echo $this->escape($item->username); ?>)</td>
              <td class="text-center"><?php echo Text::_((int) $item->is_default ? 'JYES' : 'JNO'); ?></td>
              <td class="text-center"><?php echo (int) $item->image_count; ?></td>
              <td>
                <?php if(\in_array(true, $item->managementPermissions, true)) : ?>
                  <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal"
                          data-bs-target="#collection-manage-<?php echo (int) $item->id; ?>"
                          aria-label="<?php echo $this->escape(Text::_('JACTION_EDIT') . ': ' . $item->title); ?>">
                    <span class="icon-edit" aria-hidden="true"></span> <?php echo Text::_('JACTION_EDIT'); ?>
                  </button>
                  <?php
                  $this->managementItem = $item;
                $modals[]               = HTMLHelper::_(
                    'bootstrap.renderModal',
                    'collection-manage-' . (int) $item->id,
                    ['title' => $this->escape($item->title), 'closeButton' => true],
                    $this->loadTemplate('manage')
                );
                  ?>
                <?php endif; ?>
              </td>
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

<?php echo implode("\n", $modals); ?>
