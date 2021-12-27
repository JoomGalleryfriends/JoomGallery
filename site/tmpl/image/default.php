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
use \Joomla\CMS\Session\Session;

$canEdit = Factory::getUser()->authorise('core.edit', 'com_joomgallery.' . $this->item->id);

if (!$canEdit && Factory::getUser()->authorise('core.edit.own', 'com_joomgallery' . $this->item->id))
{
	$canEdit = Factory::getUser()->id == $this->item->created_by;
}
?>

<div class="item_fields">

	<table class="table">
		

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_IMGTITLE'); ?></th>
			<td><?php echo $this->item->imgtitle; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_ALIAS'); ?></th>
			<td><?php echo $this->item->alias; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_CATID'); ?></th>
			<td><?php echo $this->item->catid; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_PUBLISHED'); ?></th>
			<td><?php echo $this->item->published; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_IMGAUTHOR'); ?></th>
			<td><?php echo $this->item->imgauthor; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_LANGUAGE'); ?></th>
			<td><?php echo $this->item->language; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_IMGTEXT'); ?></th>
			<td><?php echo nl2br($this->item->imgtext); ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_ACCESS'); ?></th>
			<td><?php echo $this->item->access; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_HIDDEN'); ?></th>
			<td><?php echo $this->item->hidden; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_FEATURED'); ?></th>
			<td><?php echo $this->item->featured; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_CREATED_TIME'); ?></th>
			<td><?php echo $this->item->created_time; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_CREATED_BY'); ?></th>
			<td><?php echo $this->item->created_by_name; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_MODIFIED_TIME'); ?></th>
			<td><?php echo $this->item->modified_time; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_MODIFIED_BY'); ?></th>
			<td><?php echo $this->item->modified_by_name; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_ID'); ?></th>
			<td><?php echo $this->item->id; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_METADESC'); ?></th>
			<td><?php echo nl2br($this->item->metadesc); ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_METAKEY'); ?></th>
			<td><?php echo nl2br($this->item->metakey); ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_ROBOTS'); ?></th>
			<td>
			<?php

			if (!empty($this->item->robots) || $this->item->robots === 0)
			{
				echo Text::_('COM_JOOMGALLERY_IMAGES_ROBOTS_OPTION_' . $this->item->robots);
			}
			?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_FILENAME'); ?></th>
			<td><?php echo $this->item->filename; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_IMGDATE'); ?></th>
			<td><?php echo $this->item->imgdate; ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_IMGMETADATA'); ?></th>
			<td><?php echo nl2br($this->item->imgmetadata); ?></td>
		</tr>

		<tr>
			<th><?php echo Text::_('COM_JOOMGALLERY_FORM_LBL_IMAGE_PARAMS'); ?></th>
			<td><?php echo $this->item->params; ?></td>
		</tr>

	</table>

</div>

<?php $canCheckin = Factory::getUser()->authorise('core.manage', 'com_joomgallery.' . $this->item->id) || $this->item->checked_out == Factory::getUser()->id; ?>
	<?php if($canEdit && $this->item->checked_out == 0): ?>

	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.edit&id='.$this->item->id); ?>"><?php echo Text::_("COM_JOOMGALLERY_EDIT_ITEM"); ?></a>
	<?php elseif($canCheckin && $this->item->checked_out > 0) : ?>
	<a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=image.checkin&id=' . $this->item->id .'&'. Session::getFormToken() .'=1'); ?>"><?php echo Text::_("JLIB_HTML_CHECKIN"); ?></a>

<?php endif; ?>

<?php if (Factory::getUser()->authorise('core.delete','com_joomgallery.image.'.$this->item->id)) : ?>

	<a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
		<?php echo Text::_("COM_JOOMGALLERY_DELETE_ITEM"); ?>
	</a>

	<?php echo HTMLHelper::_(
                                    'bootstrap.renderModal',
                                    'deleteModal',
                                    array(
                                        'title'  => Text::_('COM_JOOMGALLERY_DELETE_ITEM'),
                                        'height' => '50%',
                                        'width'  => '20%',
                                        
                                        'modalWidth'  => '50',
                                        'bodyHeight'  => '100',
                                        'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_joomgallery&task=image.remove&id=' . $this->item->id, false, 2) .'" class="btn btn-danger">' . Text::_('COM_JOOMGALLERY_DELETE_ITEM') .'</a>'
                                    ),
                                    Text::sprintf('COM_JOOMGALLERY_DELETE_CONFIRM', $this->item->id)
                                ); ?>

<?php endif; ?>