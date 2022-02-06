<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Categories;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a list of Categories.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $items;

	protected $pagination;

	protected $state;

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
		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->filterForm = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();

		$this->sidebar = Sidebar::render();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function addToolbar()
	{
		$state = $this->get('State');
		$canDo = JoomHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_CATEGORY_MANAGER'), "folder-open");

		$toolbar = Toolbar::getInstance('toolbar');

		// Check if the form exists before showing the add/edit buttons
		$formPath = JPATH_COMPONENT_ADMINISTRATOR . '/src/View/Categories';

		if(file_exists($formPath))
		{
			if($canDo->get('core.create'))
			{
				$toolbar->addNew('category.add');
			}
		}

		if($canDo->get('core.edit.state')  || count($this->transitions))
		{
			$dropdown = $toolbar->dropdownButton('status-group')
				->text('JTOOLBAR_CHANGE_STATUS')
				->toggleSplit(false)
				->icon('fas fa-ellipsis-h')
				->buttonClass('btn btn-action')
				->listCheck(true);

			$childBar = $dropdown->getChildToolbar();

			if(isset($this->items[0]->state))
			{
				$childBar->publish('categories.publish')->listCheck(true);
				$childBar->unpublish('categories.unpublish')->listCheck(true);
				$childBar->archive('categories.archive')->listCheck(true);
			}
			elseif(isset($this->items[0]))
			{
				// If this component does not use state then show a direct delete button as we can not trash
				$toolbar->delete('categories.delete')
				->text('JTOOLBAR_EMPTY_TRASH')
				->message('JGLOBAL_CONFIRM_DELETE')
				->listCheck(true);
			}

			$childBar->standardButton('duplicate')
				->text('JTOOLBAR_DUPLICATE')
				->icon('fas fa-copy')
				->task('categories.duplicate')
				->listCheck(true);

			if(isset($this->items[0]->checked_out))
			{
				$childBar->checkin('categories.checkin')->listCheck(true);
			}

			if(isset($this->items[0]->state))
			{
				$childBar->trash('categories.trash')->listCheck(true);
			}
		}

		if($canDo->get('core.admin'))
		{
			$toolbar->standardButton('refresh')
				->text('JTOOLBAR_REBUILD')
				->task('categories.rebuild');
		}

		// Show trash and delete for components that uses the state field
		if(isset($this->items[0]->state))
		{
			if($this->state->get('filter.state') == ContentComponent::CONDITION_TRASHED && $canDo->get('core.delete'))
			{
				$toolbar->delete('categories.delete')
					->text('JTOOLBAR_EMPTY_TRASH')
					->message('JGLOBAL_CONFIRM_DELETE')
					->listCheck(true);
			}
		}

		if($canDo->get('core.admin'))
		{
			$toolbar->preferences('com_joomgallery');
		}

		// Set sidebar action
		Sidebar::setAction('index.php?option=com_joomgallery&view=categories');
	}

	/**
	 * Method to order fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'a.`title`' => Text::_('JGLOBAL_TITLE'),
			'a.`parent_id`' => Text::_('COM_JOOMGALLERY_COMMON_PARENT_CATEGORY'),
			'a.`published`' => Text::_('JSTATUS'),
			'a.`access`' => Text::_('JGRID_HEADING_ACCESS'),
			'a.`language`' => Text::_('JGRID_HEADING_LANGUAGE'),
			'a.`created_by`' => Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL'),
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
		);
	}

	/**
	 * Check if state is set
	 *
	 * @param   mixed  $state  State
	 *
	 * @return bool
	 */
	public function getState($state)
	{
		return isset($this->state->{$state}) ? $this->state->{$state} : false;
	}
}
