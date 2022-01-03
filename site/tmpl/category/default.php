<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

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

<h2><?php echo $this->item->title; ?></h2>
<p><?php echo nl2br($this->item->description); ?></p>

</br />

<?php $canCheckin = Factory::getUser()->authorise('core.manage', 'com_joomgallery.' . $this->item->id) || $this->item->checked_out == Factory::getUser()->id; ?>

<?php if($canEdit && $this->item->checked_out == 0): ?>
  <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.edit&id='.$this->item->id); ?>">
    <?php echo Text::_("COM_JOOMGALLERY_COMMON_EDIT_CATEGORY_TIPCAPTION"); ?>
  </a>
<?php elseif($canCheckin && $this->item->checked_out > 0) : ?>
  <a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_joomgallery&task=category.checkin&id=' . $this->item->id .'&'. Session::getFormToken() .'=1'); ?>">
    <?php echo Text::_("JLIB_HTML_CHECKIN"); ?>
  </a>
<?php endif; ?>

<?php if (Factory::getUser()->authorise('core.delete','com_joomgallery.category.'.$this->item->id)) : ?>

	<a class="btn btn-danger" rel="noopener noreferrer" href="#deleteModal" role="button" data-bs-toggle="modal">
		<?php echo Text::_("COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION"); ?>
	</a>

	<?php echo HTMLHelper::_( 'bootstrap.renderModal',
                            'deleteModal',
                            array(
                                'title'  => Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION'),
                                'height' => '50%',
                                'width'  => '20%',

                                'modalWidth'  => '50',
                                'bodyHeight'  => '100',
                                'footer' => '<button class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button><a href="' . Route::_('index.php?option=com_joomgallery&task=category.remove&id=' . $this->item->id, false, 2) .'" class="btn btn-danger">' . Text::_('COM_JOOMGALLERY_COMMON_DELETE_CATEGORY_TIPCAPTION') .'</a>'
                            ),
                            Text::_('COM_JOOMGALLERY_COMMON_ALERT_SURE_DELETE_SELECTED_ITEM')
                          );
?>
<?php endif; ?>
