<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Tag;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Tag.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
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
		/** @var ImageModel $model */
    $model = $this->getModel();

		$this->state = $model->getState();
		$this->item  = $model->getItem();
		$this->form  = $model->getForm();

		// Check for errors.
		if(count($errors = $model->getErrors()))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		/** @var Toolbar $model */
    $toolbar = $this->getToolbar();

		$user  = Factory::getApplication()->getIdentity();
		$isNew = ($this->item->id == 0);

		if(isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_TAGS').' :: '.Text::_('COM_JOOMGALLERY_TAG_EDIT'), "tag");

		// If not checked out, can save the item.
		if(!$checkedOut && ($this->getAcl()->checkACL('core.edit') || ($this->getAcl()->checkACL('core.create'))))
		{
			ToolbarHelper::apply('tag.apply', 'JTOOLBAR_APPLY');
		}

		if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
		{
			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure
			(
				function (Toolbar $childBar) use ($checkedOut, $isNew)
				{
					$childBar->save('tag.save', 'JTOOLBAR_SAVE');

					if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
					{
						$childBar->save2new('tag.save2new');
					}

					// If an existing item, can save to a copy.
					if(!$isNew && $this->getAcl()->checkACL('core.create'))
					{
						$childBar->save2copy('tag.save2copy');
					}
				}
			);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('tag.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('tag.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
