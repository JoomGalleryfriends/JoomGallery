<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Tags;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a list of Tags.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $items;

	protected $pagination;	

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
    /** @var TagsModel $model */
    $model = $this->getModel();

    $this->state         = $model->getState();
    $this->items         = $model->getItems();		
		$this->pagination    = $model->getPagination();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();

		// Check for errors.
		if(count($errors = $model->getErrors()))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
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
    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_TAGS'), "tags");

    /** @var Toolbar $model */
    $toolbar = $this->getDocument()->getToolbar();

    // Check if the form exists before showing the add/edit buttons
    $formPath = _JOOM_PATH_ADMIN . '/src/View/Tags';

    // Show button back to control panel
    $html = '<a href="index.php?option=com_joomgallery&amp;view=control" class="btn btn-primary"><span class="icon-arrow-left-4" title="'.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'"></span> '.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'</a>';
    $toolbar->appendButton('Custom', $html);

    // New button
    if(file_exists($formPath))
    {
      if($this->getAcl()->checkACL('add', 'tag'))
      {
        $toolbar->addNew('tag.add');
      }
    }

    if($this->getAcl()->checkACL('core.edit.state'))
    {
      // Batch button
      if($this->getAcl()->checkACL('core.edit'))
      {
        $batch_dropdown = $toolbar->dropdownButton('batch-group')
          ->text('JTOOLBAR_BATCH')
          ->toggleSplit(false)
          ->icon('fas fa-tags')
          ->buttonClass('btn btn-action')
          ->listCheck(true);
        
        $batch_childBar = $batch_dropdown->getChildToolbar();

        // Duplicate button inside batch dropdown
        $batch_childBar->standardButton('duplicate')
          ->text('JTOOLBAR_DUPLICATE')
          ->icon('fas fa-copy')
          ->task('tags.duplicate')
          ->listCheck(true);
      }

      // State button
      $dropdown = $toolbar->dropdownButton('status-group')
        ->text('JSTATUS')
        ->toggleSplit(false)
        ->icon('far fa-check-circle')
        ->buttonClass('btn btn-action')
        ->listCheck(true);

      $status_childBar = $dropdown->getChildToolbar();

      if(isset($this->items[0]->published))
      {
        $status_childBar->publish('tags.publish')->listCheck(true);
        $status_childBar->unpublish('tags.unpublish')->listCheck(true);
      }
    }

    // Delete button
    if($this->getAcl()->checkACL('delete', 'tag'))
    {
      $toolbar->delete('tags.delete')
        ->text('JTOOLBAR_DELETE')
        ->message(Text::_('COM_JOOMGALLERY_CONFIRM_DELETE_TAGS'))
        ->listCheck(true);
    }

    // Show trash and delete for components that uses the state field
    if(isset($this->items[0]->published))
    {

      if($this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED && $this->getAcl()->checkACL('core.delete'))
      {
        $toolbar->delete('tags.delete')
          ->text('JTOOLBAR_EMPTY_TRASH')
          ->message('JGLOBAL_CONFIRM_DELETE')
          ->listCheck(true);
      }
    }

    if($this->getAcl()->checkACL('core.admin'))
    {
      $toolbar->preferences('com_joomgallery');
    }

    // Set sidebar action
    Sidebar::setAction('index.php?option=com_joomgallery&view=tags');
}

	/**
	 * Method to order fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'a.`ordering`' => Text::_('JGRID_HEADING_ORDERING'),
			'a.`title`' => Text::_('JGLOBAL_TITLE'),
			'a.`published`' => Text::_('JSTATUS'),
			'a.`access`' => Text::_('JGRID_HEADING_ACCESS'),
			'a.`language`' => Text::_('JGRID_HEADING_LANGUAGE'),
			'a.`id`' => Text::_('JGRID_HEADING_ID'),
		);
	}
}
