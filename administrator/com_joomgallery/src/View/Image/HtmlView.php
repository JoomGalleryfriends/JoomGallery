<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Image.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state      = $this->get('State');
		$this->item       = $this->get('Item');
		$this->form       = $this->get('Form');
    $this->imagetypes = JoomHelper::getRecords('imagetypes');

    if($this->item->id == 0)
    {
      // create a new image record
      $this->form->setFieldAttribute('image', 'required', true);
    }

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if(isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = JoomHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMGMAN_IMAGE_MANAGER').' :: '.Text::_('COM_JOOMGALLERY_IMGMAN_IMAGE_EDIT'), "image");

		// If not checked out, can save the item.
		if(!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('image.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('image.save', 'JTOOLBAR_SAVE');
		}

		if(!$checkedOut && ($canDo->get('core.create')))
		{
			ToolbarHelper::custom('image.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		// If an existing item, can save to a copy.
		if(!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::custom('image.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		// Button for version control
		if($this->state->params->get('save_history', 1) && $user->authorise('core.edit')) {
			ToolbarHelper::versions('com_joomgallery.image', $this->item->id);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
