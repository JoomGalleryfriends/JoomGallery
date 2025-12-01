<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;

/**
 * Image controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageController extends JoomBaseController
{
	/**
	 * Edit an existing image.
	 * Redirect to form view.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function edit()
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId   = (int) $this->app->getUserState(_JOOM_OPTION.'.edit.image.id');
    $cid        = (array) $this->input->post->get('cid', [], 'int');
    $boxchecked = (bool) $this->input->getInt('boxchecked', 0);

    if($boxchecked)
    {
      $editId = (int) $cid[0];
    }
    else
    {
      $editId = $this->input->getInt('id', 0);
    }

    // ID check
		if(!$editId)
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId), false));

			return false;
		}

    // Access check
		$parent_id = JoomHelper::getParent('image', $editId);

		if(!$this->acl->checkACL('edit', 'image', $editId, $parent_id, true))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId), false));

			return false;
		}

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.edit.image.id', $editId);

		// Get the model.
		$model = $this->getModel('Image', 'Site');

		// Check out the item
		if(!$model->checkout($editId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId), false));

			return false;
		}

		// Check in the previous user.
		if($previousId && $previousId !== $editId)
		{
			$model->checkin($previousId);
		}

		// Redirect to the form screen.
    $this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=imageform&'.$this->getItemAppend($editId), false));
	}

	/**
	 * Add a new image: Not available
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function add()
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState(_JOOM_OPTION.'.add.image.id');
    $cid      = (array) $this->input->post->get('cid', [], 'int');
		$editId     = (int) (\count($cid) ? $cid[0] : $this->input->getInt('id', 0));
		$addCatId   = (int) $this->input->getInt('catid', 0);

		// Access check
		if(!$this->acl->checkACL('add', 'image', $editId, $addCatId, true))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($editId), false));

			return false;
		}

		// Clear form data from session
		$this->app->setUserState(_JOOM_OPTION.'.edit.image.data', []);

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.add.image.catid', $addCatId);
		$this->app->setUserState(_JOOM_OPTION.'.edit.image.id', 0);

		// Check in the previous user.
		if($previousId && $previousId !== $addCatId)
		{
      // Get the model.
		  $model = $this->getModel('Image', 'Site');

			$model->checkin($previousId);
		}

		// Redirect to the form screen.
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=imageform&'.$this->getItemAppend(0, $addCatId), false));
  }

	/**
	 * Remove an image
	 *
	 * @throws \Exception
	 */
	public function remove()
	{
		throw new \Exception('Removing image not possible. Use imageform controller instead.', 503);
	}

	/**
	 * Checkin a checked out image.
	 *
	 * @throws \Exception
	 */
	public function checkin()
	{
		throw new \Exception('Check-in image not possible. Use imageform controller instead.', 503);
	}

	/**
	 * Method to publish an image
	 *
	 * @throws \Exception
	 */
	public function publish()
	{
    throw new \Exception('Publish image not possible. Use imageform controller instead.', 503);
  }

	/**
	 * Method to unpublish an image
	 *
	 * @throws \Exception
	 */
	public function unpublish()
	{
    throw new \Exception('Unpublish image not possible. Use imageform controller instead.', 503);
  }
}
