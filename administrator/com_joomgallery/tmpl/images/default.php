<?php
/**
 * @package     com_joomgallery
 * @author      JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @copyright   2008 - 2025 JoomGallery::ProjectTeam
 * @license     GNU General Public License version 3 or later
 */

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\ApprovedButton;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Button\FeaturedButton;
use Joomla\CMS\Button\PublishedButton;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin')
   ->useScript('table.columns')
   ->useScript('multiselect')
   ->useScript('com_joomgallery.tasks');

$user      = $this->app->getIdentity();
$userId    = $user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $this->getAcl()->checkACL('editstate', 'com_joomgallery');
$saveOrder = ($listOrder == 'a.ordering' && strtolower($listDirn) == 'asc');

$newTaskId   = $this->app->input->get('newTaskId', 0, 'int');
$newTaskItem = null;

if($newTaskId)
{
  // Boot Task Model manually to get the item
  $taskModel   = Factory::getApplication()->bootComponent('com_joomgallery')->getMVCFactory()->createModel('Task', 'Administrator', array('ignore_request' => true));
  $newTaskItem = $taskModel->getItem($newTaskId);
}

if($saveOrder && !empty($this->items))
{
  $saveOrderingUrl = 'index.php?option=com_joomgallery&task=images.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
  HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_joomgallery&view=images'); ?>" method="post"
    name="adminForm" id="adminForm">
  <div class="row">
  <div class="col-md-12">
   <div id="j-main-container" class="j-main-container">
   <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
        <?php if(empty($this->items)) : ?>
          <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
          </div>
        <?php else : ?>
        <div class="clearfix"></div>
        <div class="table-responsive">
          <table class="table table-striped" id="imageList">
            <caption class="visually-hidden">
              <?php echo Text::_('COM_JOOMGALLERY_IMAGES_TABLE_CAPTION'); ?>,
              <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
              <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
            </caption>
            <thead>
              <tr>
                <td class="w-1 text-center">
                  <?php echo HTMLHelper::_('grid.checkall'); ?>
                </td>
                <?php if(isset($this->items[0]->ordering)): ?>
                  <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                    <?php echo HTMLHelper::_('searchtools.sort', '', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                  </th>
                <?php endif; ?>
                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JFEATURED', 'a.featured', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-1 text-center">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JPUBLISHED', 'a.published', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-1 text-center">
                  <?php echo Text::_('COM_JOOMGALLERY_IMAGE') ?>
                </th>
                <th scope="col" style="min-width:180px">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMGALLERY_APPROVED', 'a.approved', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JAUTHOR', 'a.author', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JDATE', 'a.date', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_HITS', 'a.hits', $listDirn, $listOrder); ?>
                </th>
                <th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                  <?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMGALLERY_DOWNLOADS', 'a.downloads', $listDirn, $listOrder); ?>
                </th>              
                <th scope="col" class="w-10 d-none d-md-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'COM_JOOMGALLERY_OWNER', 'a.created_by', $listDirn, $listOrder); ?>
                </th>
                <?php if(Multilanguage::isEnabled()) : ?>
                  <th scope="col" class="w-10 d-none d-md-table-cell">
                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
                  </th>
                <?php endif; ?>
                <th scope="col" class="w-3 d-none d-lg-table-cell">
                  <?php echo HTMLHelper::_('searchtools.sort', 'JGLOBAL_FIELD_ID_LABEL', 'a.id', $listDirn, $listOrder); ?>
                </th>
              </tr>
            </thead>
            <tfoot>
            <tr>
              <td colspan="<?php echo isset($this->items[0]) ? \count(\get_object_vars($this->items[0])) : 10; ?>">
                <?php echo $this->pagination->getListFooter(); ?>
              </td>
            </tr>
            </tfoot>
            <tbody <?php if($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo \strtolower($listDirn); ?>" <?php endif; ?>>
              <?php foreach($this->items as $i => $item) :
                $ordering   = ($listOrder == 'a.ordering');
                $canEdit    = $this->getAcl()->checkACL('edit', _JOOM_OPTION.'.image', $item->id, $item->catid, true);
                $canEditCat = $this->getAcl()->checkACL('edit', _JOOM_OPTION.'.category.'.$item->catid);
                $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || is_null($item->checked_out);
                $canChange  = $this->getAcl()->checkACL('editstate', _JOOM_OPTION.'.image', $item->id, $item->catid, true) && $canCheckin;
                ?>

              <tr class="row<?php echo $i % 2; ?>">
                <td >
                  <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                </td>

                <?php if(isset($this->items[0]->ordering)) : ?>
                  <td class="text-center d-none d-md-table-cell">
                    <?php
                      $iconClass = '';

                      if(!$canChange)
                      {
                        $iconClass = ' inactive';
                      }
                      elseif(!$saveOrder)
                      {
                        $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                      }
                    ?>
                    <span class="sortable-handler<?php echo $iconClass ?>">
                      <span class="icon-ellipsis-v" aria-hidden="true"></span>
                    </span>
                    <?php if($canChange && $saveOrder) : ?>
                      <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                    <?php endif; ?>
                  </td>
                <?php endif; ?>

                <td class="text-center d-none d-md-table-cell">
                  <?php
                    $options = [
                      'task_prefix' => 'images.',
                      'disabled'    => !$canChange,
                      'id'          => 'featured-' . $item->id,
                    ];

                    echo (new FeaturedButton())->render((int) $item->featured, $i, $options);
                  ?>
                </td>

                <td class="image-status text-center">
                  <?php
                    $options = [
                      'task_prefix' => 'images.',
                      'disabled'    => !$canChange,
                      'id'          => 'state-' . $item->id,
                    ];

                    echo (new PublishedButton())->render((int) $item->published, $i, $options);
                  ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <img class="jg_minithumb" src="<?php echo JoomHelper::getImg($item, 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
                </td>

                <th scope="row" class="has-context">
                  <div class="break-word">
                    <?php if(isset($item->checked_out) && $item->checked_out && ($canEdit || $canChange)) : ?>
                      <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'images.', $canCheckin); ?>
                    <?php endif; ?>

                    <?php if($canEdit) : ?>
                      <?php
                        $ImgUrl     = Route::_('index.php?option=com_joomgallery&task=image.edit&id='.(int) $item->id);
                        $EditImgTxt = Text::_('COM_JOOMGALLERY_IMAGE_EDIT');
                      ?>
                      <a href="<?php echo $ImgUrl; ?>" title="<?php echo $EditImgTxt; ?>">
                        <?php echo $this->escape($item->title); ?>
                      </a>
                    <?php else : ?>
                      <?php echo $this->escape($item->title); ?>
                    <?php endif; ?>

                    <div class="small break-word">
                      <?php echo Text::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); ?>
                    </div>

                    <div class="small">
                      <?php echo Text::_('JCATEGORY') . ': '; ?>
                      <?php if($canEditCat) : ?>
                        <?php
                          $CatUrl     = Route::_('index.php?option=com_joomgallery&task=category.edit&id='.(int) $item->catid);
                          $EditCatTxt = Text::_('COM_JOOMGALLERY_CATEGORY_EDIT');
                        ?>
                        <a href="<?php echo $CatUrl; ?>" title="<?php echo $EditCatTxt; ?>"><?php echo $this->escape($item->cattitle); ?></a>
                      <?php else : ?>
                        <?php echo $this->escape($item->cattitle); ?>
                      <?php endif; ?>
                    </div>
                    
                    <?php if($item->hidden === 1) : ?>
                      <div class="small">
                        <span class="badge bg-secondary">
                          <?php echo Text::_('COM_JOOMGALLERY_HIDDEN'); ?>
                        </span>
                      </div>
                    <?php endif; ?>

                  </div>
                </th>

                <td class="d-none d-lg-table-cell text-center">
                  <?php
                    $options = [
                      'task_prefix' => 'images.',
                      'disabled'    => !$canChange,
                      'id'          => 'state-' . $item->id,
                    ];

                    echo (new ApprovedButton())->render((int) $item->approved, $i, $options);
                  ?>
                  <?php //echo $item->approved; ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <?php echo $this->escape($item->access); ?>
                </td>

                <td class="small d-none d-md-table-cell">
                  <?php if($item->author) : ?>
                    <?php echo $this->escape($item->author); ?>
                  <?php else : ?>
                    <?php echo Text::_('COM_JOOMGALLERY_NO_USER'); ?>
                  <?php endif; ?>
                </td>

                <td class="small d-none d-md-table-cell text-center">
                  <?php
                    $date = $item->date;
                    echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC4')) : '-';
                  ?>
                </td>

                <td class="d-none d-lg-table-cell text-center">
                  <span class="badge bg-info">
                    <?php echo (int) $item->hits; ?>
                  </span>
                </td>
                <td class="d-none d-lg-table-cell text-center">
                  <span class="badge bg-info">
                    <?php echo (int) $item->downloads; ?>
                  </span>
                </td>
                <td class="small d-none d-md-table-cell">
                  <?php if($item->created_by) : ?>
                    <a href="<?php echo Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->created_by_id); ?>">
                      <?php echo $this->escape($item->created_by); ?>
                    </a>
                  <?php else : ?>
                    <?php echo Text::_('JNONE'); ?>
                  <?php endif; ?>
                </td>
                <?php if(Multilanguage::isEnabled()) : ?>
                  <td class="small d-none d-md-table-cell">
                    <?php echo LayoutHelper::render('joomla.content.language', $item); ?>
                  </td>
                <?php endif; ?>
                <td class="d-none d-lg-table-cell">
                  <?php echo (int) $item->id; ?>
                </td>

              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="form_submited" value="1"/>
        <?php echo HTMLHelper::_('form.token'); ?>
      </div> 
    </div>
  </div>

  <?php echo LayoutHelper::render('joomgallery.task.modals'); ?>

  <div class="modal fade" id="joomgallery-new-task-modal" tabindex="-1" aria-labelledby="newTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="newTaskModalLabel"><?php echo Text::_('COM_JOOMGALLERY_IMAGES_RECREATE_TASK_CREATED'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body overflow-hidden">
          <?php if($newTaskItem) : ?>
            <?php echo LayoutHelper::render('joomgallery.task.card', $newTaskItem); ?>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=tasks'); ?>" class="btn btn-primary"><?php echo Text::_('COM_JOOMGALLERY_IMAGES_GO_TO_TASKS'); ?></a>
        </div>
      </div>
    </div>
  </div>
</form>
<?php if($newTaskId && $newTaskItem) : ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Open modal automatically if a new task is present
      var myModal = new bootstrap.Modal(document.getElementById('joomgallery-new-task-modal'));
      myModal.show();
    });
  </script>
<?php endif; ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    let currentlyVisibleModal = null;
    let modalToReopen = null;
    let isReopening = false; // Flag to prevent the re-opening loop

    // Use 'shown.bs.modal' to track the modal that is fully visible.
    document.addEventListener('shown.bs.modal', function (event) {
      currentlyVisibleModal = event.target;
      // The re-opening process is complete, so reset the flag.
      isReopening = false;
    });

    // When a modal is hidden, clear the tracker if it was the one we were tracking.
    document.addEventListener('hidden.bs.modal', function (event) {
      if (currentlyVisibleModal === event.target) {
        currentlyVisibleModal = null;
      }
    });

    // When a new modal is about to be shown, check if another one is already visible.
    document.addEventListener('show.bs.modal', function (event) {
      // If we are in the process of re-opening a modal, do nothing.
      if (isReopening) {
        return;
      }

      // If a modal was open before this new one was triggered.
      if (currentlyVisibleModal && currentlyVisibleModal !== event.target) {
        modalToReopen = currentlyVisibleModal;

        event.target.addEventListener('hidden.bs.modal', function onNewModalHidden() {
          if (modalToReopen) {
            isReopening = true;
            const instance = bootstrap.Modal.getInstance(modalToReopen) || new bootstrap.Modal(modalToReopen);
            instance.show();
            modalToReopen = null;
          }
        }, { once: true });
      }
    });
  });
</script>
