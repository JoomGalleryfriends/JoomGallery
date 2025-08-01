<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Layout\LayoutHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.list')
   ->useStyle('com_joomgallery.site')
   ->useScript('com_joomgallery.list-view')
   ->useScript('multiselect');

// Access check
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canAdd    = $this->getAcl()->checkACL('add', 'com_joomgallery.category', 0, 1, true);
$canOrder  = $this->getAcl()->checkACL('editstate', 'com_joomgallery.category');
$saveOrder = ($listOrder == 'a.lft' && strtolower($listDirn) == 'asc');
$returnURL = base64_encode(JoomHelper::getListRoute('categories', null, $this->getLayout()));

if($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = Route::_('index.php?option=com_joomgallery&task=categories.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1');
	HTMLHelper::_('draggablelist.draggable');
}
?>

<?php if ($this->params['menu']->get('show_page_heading')) : ?>
    <div class="page-header page-title">
        <h1> <?php echo $this->escape($this->params['menu']->get('page_heading')); ?> </h1>
    </div>
<?php endif; ?>

<form class="jg-categories" action="<?php echo Route::_('index.php?option=com_joomgallery&view=categories'); ?>" method="post" name="adminForm" id="adminForm">
	<?php if(!empty($this->filterForm)) { echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); } ?>
	<div class="row">
		<div class="col-md-12">

			<?php if (empty($this->items)) : ?>
        <div class="alert alert-info">
          <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
          <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
        </div>
      <?php else : ?>
        <div class="clearfix"></div>	
				<div class="table-responsive">
					<table class="table table-striped itemList" id="categoryList">
						<caption class="visually-hidden">
              <?php echo Text::_('COM_JOOMGALLERY_CATEGORIES_TABLE_CAPTION'); ?>,
              <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
              <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
            </caption>
						<thead>
							<tr>
                  <?php if($canOrder && $saveOrder) : ?>
                    <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                      <?php echo HTMLHelper::_('grid.sort', '', 'a.lft', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                    </th>
                  <?php else : ?>
                    <th scope="col" class="w-1 d-md-table-cell"></th>
                  <?php endif; ?>

									<th scope="col" style="min-width:180px">
										<?php echo HTMLHelper::_('grid.sort',  'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
									</th>

									<th scope="col" class="w-3 d-none d-lg-table-cell text-center">
                    <?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES', 'img_count', $listDirn, $listOrder); ?>
                  </th>

									<th scope="col" style="min-width:180px" class="w-3 d-none d-lg-table-cell text-center">
										<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_PARENT_CATEGORY', 'parent_title', $listDirn, $listOrder); ?>
									</th>

									<th scope="col" class="w-3 d-none d-lg-table-cell text-center">
										<?php echo Text::_('COM_JOOMGALLERY_ACTIONS'); ?>
									</th>

									<th scope="col" class="w-3 d-none d-lg-table-cell text-center">
										<?php echo HTMLHelper::_('grid.sort',  'JPUBLISHED', 'a.published', $listDirn, $listOrder); ?>
									</th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
									<?php echo $this->pagination->getListFooter(); ?>
								</td>
							</tr>
						</tfoot>
						<tbody <?php if($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" <?php endif; ?>>
							<?php foreach ($this->items as $i => $item) :
                  // Access check
                  $ordering   = ($listOrder == 'a.ordering');
                  $canEdit    = $this->getAcl()->checkACL('edit', 'com_joomgallery.category', $item->id);
                  $canDelete  = $this->getAcl()->checkACL('delete', 'com_joomgallery.category', $item->id);
                  $canChange  = $this->getAcl()->checkACL('editstate', 'com_joomgallery.category', $item->id);
									$canCheckin = $canChange || $item->checked_out == $this->getCurrentUser()->id;
									$disabled   = ($item->checked_out > 0) ? 'disabled' : '';
                
									// Get the parents of item for sorting
									if ($item->level > 1)
									{
										$parentsStr = '';
										$_currentParentId = $item->parent_id;
										$parentsStr = ' ' . $_currentParentId;
										for ($i2 = 0; $i2 < $item->level; $i2++)
										{
											foreach ($this->ordering as $k => $v)
											{
												$v = implode('-', $v);
												$v = '-' . $v . '-';
												if (strpos($v, '-' . $_currentParentId . '-') !== false)
												{
													$parentsStr .= ' ' . $k;
													$_currentParentId = $k;
													break;
												}
											}
										}
									}
									else
									{
										$parentsStr = '';
									}
								?>

								<tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->parent_id; ?>"
                	data-item-id="<?php echo $item->id ?>" data-parents="<?php echo $parentsStr ?>"
                	data-level="<?php echo $item->level ?>">

									<?php if(isset($this->items[0]->lft)) : ?>
                    <td class="text-center d-none d-md-table-cell sort-cell">
											<?php
											$iconClass = '';
											if (!$canChange)
											{
												$iconClass = ' inactive';
											}
											elseif (!$saveOrder)
											{
												$iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
											}
											?>
                      <?php if($canChange && $saveOrder) : ?>
                        <span class="sortable-handler<?php echo $iconClass ?>">
                          <span class="icon-ellipsis-v"></span>
                        </span>
                        <label>
                          <input type="text" name="order[]" size="5" value="<?php echo $item->lft; ?>" class="hidden">
                        </label>
                      <?php endif; ?>

                      <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                    </td>
                  <?php endif; ?>

									<th scope="row" class="has-context title-cell">
										<?php echo LayoutHelper::render('joomla.html.treeprefix', array('level' => $item->level)); ?>
										<?php if($canCheckin && $item->checked_out > 0) : ?>
                      <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>" data-item-id="cb<?php echo $i; ?>" data-item-task="category.checkin" <?php echo $disabled; ?>>
                        <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'category.', false); ?>
                      </button>
										<?php endif; ?>
										<a href="<?php echo Route::_('index.php?option=com_joomgallery&view=category&id='.(int) $item->id); ?>">
											<?php echo $this->escape($item->title); ?>
										</a>
									</th>

									<td class="d-none d-lg-table-cell text-center">
                    <span class="badge bg-info">
                      <?php echo (int) $item->img_count; ?>
                    </span>
                  </td>

									<td class="d-none d-lg-table-cell text-center">
										<?php echo ($item->parent_title == 'Root') ? '--' : $this->escape($item->parent_title); ?>
									</td>

									<td class="d-none d-lg-table-cell text-center">
										<?php if($canEdit || $canDelete): ?>
											<?php if($canEdit): ?>
                        <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>" data-item-id="cb<?php echo $i; ?>" data-item-task="category.edit" <?php echo $disabled; ?>>
                          <span class="icon-edit" aria-hidden="true"></span>
                        </button>
											<?php endif; ?>
											<?php if ($canDelete): ?>
                        <button class="js-grid-item-delete tbody-icon <?php echo $disabled; ?>" data-item-confirm="<?php echo Text::_('JGLOBAL_CONFIRM_DELETE'); ?>" data-item-id="cb<?php echo $i; ?>" data-item-task="categoryform.remove" <?php echo $disabled; ?>>
                          <span class="icon-trash" aria-hidden="true"></span>
                        </button>
											<?php endif; ?>
										<?php endif; ?>
									</td>

									<td class="d-none d-lg-table-cell text-center">
                    <?php if($canChange): ?>
                      <?php $statetask = ((int) $item->published) ? 'unpublish': 'publish'; ?>
                      <button class="js-grid-item-action tbody-icon <?php echo $disabled; ?>" data-item-id="cb<?php echo $i; ?>" data-item-task="categoryform.<?php echo $statetask; ?>" <?php echo $disabled; ?>>
                        <span class="icon-<?php echo (int) $item->published ? 'check': 'cancel'; ?>" aria-hidden="true"></span>
                      </button>
                    <?php else : ?>
                      <i class="icon-<?php echo (int) $item->published ? 'check': 'cancel'; ?>"></i>
                    <?php endif; ?>
                  </td>

								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<input type="hidden" name="task" value=""/>
      <input type="hidden" name="return" value="<?php echo $returnURL; ?>"/>
			<input type="hidden" name="boxchecked" value="0"/>
      <input type="hidden" name="form_submited" value="1"/>
      <input type="hidden" name="filter_order" value=""/>
      <input type="hidden" name="filter_order_Dir" value=""/>
			<?php echo HTMLHelper::_('form.token'); ?>

      <?php /*if($canAdd) : ?>
        <div class="mb-2">
          <a href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.add&return='.$returnURL, false, 0); ?>" class="btn btn-success btn-small">
            <i class="icon-plus"></i> <?php echo Text::_('JGLOBAL_ADD_CUSTOM_CATEGORY'); ?>
          </a>
        </div>				
			<?php endif; */?>
		</div>
	</div>
</form>
