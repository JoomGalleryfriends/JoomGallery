<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Images;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a list of Images.
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
    /** @var ImagesModel $model */
    $model = $this->getModel();

    $this->state         = $model->getState();
    $this->items         = $model->getItems();		
		$this->pagination    = $model->getPagination();
		$this->filterForm    = $model->getFilterForm();
		$this->activeFilters = $model->getActiveFilters();
    
		// Check if filesystem plugins are available
		JoomHelper::checkFilesystems();

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
    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES'), "image");

    /** @var Toolbar $model */
    $toolbar = $this->getDocument()->getToolbar();

    // Check if the form exists before showing the add/edit buttons
    $formPath = _JOOM_PATH_ADMIN . '/src/View/Images';

    // Show button back to control panel
    $html = '<a href="index.php?option=com_joomgallery&amp;view=control" class="btn btn-primary"><span class="icon-arrow-left-4" title="'.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'"></span> '.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'</a>';
    $toolbar->appendButton('Custom', $html);

    // New button
    if(\file_exists($formPath))
    {
      if($this->getAcl()->checkACL('add'))
      {
        $add_dropdown = $toolbar->dropdownButton('add-group')
          ->text('JTOOLBAR_NEW')
          ->toggleSplit(true)
          ->icon('fas fa-plus')
          ->buttonClass('btn btn-action');

        $add_childBar = $add_dropdown->getChildToolbar();

        $add_childBar->addNew('image.add');
        $add_childBar->standardButton('multipleadd')
          ->text('COM_JOOMGALLERY_MULTIPLE_NEW')
          ->icon('fas fa-upload')
          ->task('image.multipleadd');
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
          ->icon('fas fa-ellipsis-h')
          ->buttonClass('btn btn-action')
          ->listCheck(true);
        
        $batch_childBar = $batch_dropdown->getChildToolbar();

        // Duplicate button inside batch dropdown
        $batch_childBar->standardButton('duplicate')
          ->text('JTOOLBAR_DUPLICATE')
          ->icon('fas fa-copy')
          ->task('images.duplicate')
          ->listCheck(true);
      }

      // Image processing button
      if($this->getAcl()->checkACL('core.edit'))
      {
        $process_dropdown = $toolbar->dropdownButton('process-group')
          ->text('COM_JOOMGALLERY_CONFIG_TAB_IMAGE_PROCESSING')
          ->toggleSplit(false)
          ->icon('fas fa-images')
          ->buttonClass('btn btn-action')
          ->listCheck(true);
        
        $process_childBar = $process_dropdown->getChildToolbar();

        // Recreate button inside image manipulation
        $process_childBar->standardButton('recreate')
          ->text('COM_JOOMGALLERY_RECREATE')
          ->icon('icon-refresh')
          ->task('images.recreate')
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
        $status_childBar->publish('images.publish')->listCheck(true);
        $status_childBar->unpublish('images.unpublish')->listCheck(true);
      }
    }

    // Show delete button
    $toolbar->delete('images.delete')
      ->text('JTOOLBAR_DELETE')
      ->message(Text::_('COM_JOOMGALLERY_CONFIRM_DELETE_IMAGES'))
      ->listCheck(true);

    // Show trash and delete for components that uses the state field
    if(isset($this->items[0]->published))
    {
      if($this->state->get('filter.published') == ContentComponent::CONDITION_TRASHED)
      {
        $toolbar->delete('images.delete')
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
    Sidebar::setAction('index.php?option=com_joomgallery&view=images');
  }

	/**
	 * Method to order fields
	 *
	 * @return void
	 */
	protected function getSortFields()
	{
		return array(
			'a.`ordering`'   => Text::_('JGRID_HEADING_ORDERING'),
			'a.`hits`'       => Text::_('COM_JOOMGALLERY_COMMON_HITS'),
			'a.`downloads`'  => Text::_('COM_JOOMGALLERY_DOWNLOADS'),
			'a.`approved`'   => Text::_('COM_JOOMGALLERY_APPROVED'),
			'a.`title`'   => Text::_('JGLOBAL_TITLE'),
			'a.`catid`'      => Text::_('JCATEGORY'),
			'a.`published`'  => Text::_('JSTATUS'),
			'a.`author`'  => Text::_('JAUTHOR'),
			'a.`language`'   => Text::_('JGRID_HEADING_LANGUAGE'),
			'a.`access`'     => Text::_('JGRID_HEADING_ACCESS'),
			'a.`created_by`' => Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL'),
			'a.`id`'         => Text::_('JGRID_HEADING_ID'),
			'a.`date`'    => Text::_('JDATE'),
		);
	}
}
