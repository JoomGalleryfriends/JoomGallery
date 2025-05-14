<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Category;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Category.
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
		/** @var CategoryModel $model */
    $model = $this->getModel();

		$this->state = $model->getState();
		$this->item  = $model->getItem();
		$this->form  = $model->getForm();

		// JS to deactivate filesystem form field
		$js  = 'var callback = function() {';
		$js .=    'let catid = document.getElementById("jform_id");';
		$js .=    'let filesystem = document.getElementById("jform_params__jg_filesystem");';
		$js .=    'if(catid && filesystem && catid.value > 1) {filesystem.setAttribute("disabled", "disabled"); filesystem.classList.add("readonly");};';
		$js .= '};';
		$js .= 'if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)){callback();} else {document.addEventListener("DOMContentLoaded", callback);}';
		$this->filesystem_js = $js;

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
	 * @throws Exception
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

		ToolbarHelper::title(Text::_('JCATEGORIES').' :: '.Text::_('COM_JOOMGALLERY_CATEGORY_EDIT'), "folder-open");

		// If not checked out, can save the item.
		if(!$checkedOut && ($this->getAcl()->checkACL('core.edit') || ($this->getAcl()->checkACL('core.create'))))
		{
			ToolbarHelper::apply('category.apply', 'JTOOLBAR_APPLY');
		}

		if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
		{
			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure
			(
				function (Toolbar $childBar) use ($checkedOut, $isNew)
				{
					$childBar->save('category.save', 'JTOOLBAR_SAVE');

					if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
					{
						$childBar->save2new('category.save2new');
					}

					// If an existing item, can save to a copy.
					if(!$isNew && $this->getAcl()->checkACL('core.create'))
					{
						$childBar->save2copy('category.save2copy');
					}
				}
			);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('category.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('category.cancel', 'JTOOLBAR_CLOSE');
		}
	}
}
