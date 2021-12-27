<?php
/**
 * @version    CVS: 4.0.0
 * @package    Com_Joomgallery
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @copyright  2008 - 2022  JoomGallery::ProjectTeam
 * @license    GNU General Public License version 2 or later
 */
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Session\Session;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('formbehavior.chosen', 'select');

$user       = Factory::getUser();
$userId     = $user->get('id');
$listOrder  = $this->state->get('list.ordering');
$listDirn   = $this->state->get('list.direction');
$canCreate  = $user->authorise('core.create', 'com_joomgallery') && file_exists(JPATH_COMPONENT . DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'imageform.xml');
$canEdit    = $user->authorise('core.edit', 'com_joomgallery') && file_exists(JPATH_COMPONENT .  DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'imageform.xml');
$canCheckin = $user->authorise('core.manage', 'com_joomgallery');
$canChange  = $user->authorise('core.edit.state', 'com_joomgallery');
$canDelete  = $user->authorise('core.delete', 'com_joomgallery');

// Import CSS
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('com_joomgallery.list');
?>

<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post"
	  name="adminForm" id="adminForm">
	<?php if(!empty($this->filterForm)) { echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); } ?>
	<div class="table-responsive">
		<table class="table table-striped" id="imageList">
			<thead>
			<tr>
				
					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_CATID', 'a.catid', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_IMGTITLE', 'a.imgtitle', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_IMGAUTHOR', 'a.imgauthor', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_IMGDATE', 'a.imgdate', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_HITS', 'a.hits', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_DOWNLOADS', 'a.downloads', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_ACCESS', 'a.access', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_PUBLISHED', 'a.published', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_APPROVED', 'a.approved', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
					</th>

					<th class=''>
						<?php echo HTMLHelper::_('grid.sort',  'COM_JOOMGALLERY_IMAGES_LANGUAGE', 'a.language', $listDirn, $listOrder); ?>
					</th>

						<?php if ($canEdit || $canDelete): ?>
					<th class="center">
						<?php echo Text::_('COM_JOOMGALLERY_IMAGES_ACTIONS'); ?>
					</th>
					<?php endif; ?>

			</tr>
			</thead>
			<tfoot>
			<tr>
				<td colspan="<?php echo isset($this->items[0]) ? count(get_object_vars($this->items[0])) : 10; ?>">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item) : ?>
				<?php $canEdit = $user->authorise('core.edit', 'com_joomgallery'); ?>
				<?php if (!$canEdit && $user->authorise('core.edit.own', 'com_joomgallery')): ?>
				<?php $canEdit = Factory::getUser()->id == $item->created_by; ?>
				<?php endif; ?>

				<tr class="row<?php echo $i % 2; ?>">
					
					<td>
						<?php echo $item->id; ?>
					</td>
					<td>
						<?php echo $item->catid; ?>
					</td>
					<td>
						<?php $canCheckin = Factory::getUser()->authorise('core.manage', 'com_joomgallery.' . $item->id) || $item->checked_out == Factory::getUser()->id; ?>
						<?php if($canCheckin && $item->checked_out > 0) : ?>
							<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.checkin&id=' . $item->id .'&'. Session::getFormToken() .'=1'); ?>">
							<?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->uEditor, $item->checked_out_time, 'image.', false); ?></a>
						<?php endif; ?>
						<a href="<?php echo Route::_('index.php?option=com_joomgallery&view=image&id='.(int) $item->id); ?>">
							<?php echo $this->escape($item->imgtitle); ?></a>
					</td>
					<td>
						<?php echo $item->imgauthor; ?>
					</td>
					<td>
						<?php
						$date = $item->imgdate;
						echo $date > 0 ? HTMLHelper::_('date', $date, Text::_('DATE_FORMAT_LC6')) : '-';
						?>
					</td>
					<td>
						<?php echo $item->hits; ?>
					</td>
					<td>
						<?php echo $item->downloads; ?>
					</td>
					<td>
						<?php echo $item->access; ?>
					</td>
					<td>
						<?php echo $item->published; ?>
					</td>
					<td>
						<?php echo $item->approved; ?>
					</td>
					<td>
								<?php echo Factory::getUser($item->created_by)->name; ?>
					</td>
					<td>
						<?php echo $item->language; ?>
					</td>
					<?php if ($canEdit || $canDelete): ?>
						<td class="center">
							<?php $canCheckin = Factory::getUser()->authorise('core.manage', 'com_joomgallery.' . $item->id) || $this->item->checked_out == Factory::getUser()->id; ?>

							<?php if($canEdit && $item->checked_out == 0): ?>
								<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.edit&id=' . $item->id, false, 2); ?>" class="btn btn-mini" type="button"><i class="icon-edit" ></i></a>
							<?php endif; ?>
							<?php if ($canDelete): ?>
								<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=imageform.remove&id=' . $item->id, false, 2); ?>" class="btn btn-mini delete-button" type="button"><i class="icon-trash" ></i></a>
							<?php endif; ?>
						</td>
					<?php endif; ?>

				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php if ($canCreate) : ?>
		<a href="<?php echo Route::_('index.php?option=com_joomgallery&task=imageform.edit&id=0', false, 0); ?>"
		   class="btn btn-success btn-small"><i
				class="icon-plus"></i>
			<?php echo Text::_('COM_JOOMGALLERY_ADD_ITEM'); ?></a>
	<?php endif; ?>

	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value=""/>
	<input type="hidden" name="filter_order_Dir" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php
	if($canDelete) {
		$wa->addInlineScript("
			jQuery(document).ready(function () {
				jQuery('.delete-button').click(deleteItem);
			});

			function deleteItem() {

				if (!confirm(\"" . Text::_('COM_JOOMGALLERY_DELETE_MESSAGE') . "\")) {
					return false;
				}
			}
		", [], [], ["jquery"]);
	}
?>