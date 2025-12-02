<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

/**
 * Category controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryController extends JoomBaseController
{
	/**
	 * Edit a category
	 * Checkout and redirect to from view
	 *
	 * @return  void|false
	 *
	 * @since   4.0.0
	 *
	 * @throws  \Exception
	 */
	public function edit(): bool
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState(_JOOM_OPTION.'.edit.category.id');
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
			$this->setRedirect(Route::_($this->getReturnPage().$this->getItemAppend($editId),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('edit', 'category', $editId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().$this->getItemAppend($editId),false));

			return false;
		}

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.edit.category.id', $editId);

		// Get the model.
		$model = $this->getModel('Category', 'Site');

		// Check out the item
		if(!$model->checkout($editId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().$this->getItemAppend($editId),false));
			
			return false;
		}

		// Check in the previous user.
		if($previousId && $previousId !== $editId)
		{
			$model->checkin($previousId);
		}

		// Redirect to the form screen.
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=categoryform&'.$this->getItemAppend($editId), false));

    return true;
	}

	/**
	 * Add a new category
   * Checkout and redirect to from view
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function add() : bool
	{
		// Get the previous edit id (if any) and the current edit id.
		$previousId = (int) $this->app->getUserState(_JOOM_OPTION.'.add.category.id');
    $cid        = (array) $this->input->post->get('cid', [], 'int');
		$editId     = (int) (\count($cid) ? $cid[0] : $this->input->getInt('id', 0));
		$addCatId   = (int) $this->input->getInt('catid', 0);

		// Access check
		if(!$this->acl->checkACL('add', 'category', $editId, $addCatId, true))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().$this->getItemAppend($editId),false));

			return false;
		}

		// Clear form data from session
		$this->app->setUserState(_JOOM_OPTION.'.edit.category.data', array());

		// Set the current edit id in the session.
		$this->app->setUserState(_JOOM_OPTION.'.add.category.id', $addCatId);
		$this->app->setUserState(_JOOM_OPTION.'.edit.category.id', 0);

		// Check in the previous user.
		if($previousId && $previousId !== $addCatId)
		{
      // Get the model.
		  $model = $this->getModel('Category', 'Site');

			$model->checkin($previousId);
		}

		// Redirect to the form screen.
		$this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=categoryform&'.$this->getItemAppend(0, $addCatId), false));

    return true;
	}

	/**
   * Unlocks a password protected category
   *
   * @return  void
   * @since   4.0.0
   */
  public function unlock()
  {
		$this->checkToken();

		$cid = $this->input->getInt('id', 0);
		
    try
    {
			$model = $this->getModel('Category', 'Site');
      $model->unlock($cid, $this->input->getString('password', ''));
    }
    catch(\Exception $e)
    {
      $this->setMessage($e->getMessage(), 'error');
    }

    $this->setRedirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&id='.$cid, false));
  }

	/**
	 * Remove a category
	 *
	 * @throws \Exception
	 */
	public function remove()
	{
		throw new \Exception('Removing category not possible. Use categoryform controller instead.', 503);
	}

	/**
	 * Checkin a checked out category.
	 *
	 * @throws \Exception
	 */
	public function checkin()
	{
		throw new \Exception('Check-in category not possible. Use categoryform controller instead.', 503);
	}

  /**
	 * Method to publish a category
	 *
	 * @throws \Exception
	 */
	public function publish()
	{
    throw new \Exception('Publish category not possible. Use categoryform controller instead.', 503);
  }

  /**
	 * Method to unpublish a category
	 *
	 * @throws \Exception
	 */
	public function unpublish()
	{
    throw new \Exception('Unpublish category not possible. Use categoryform controller instead.', 503);
  }
}
