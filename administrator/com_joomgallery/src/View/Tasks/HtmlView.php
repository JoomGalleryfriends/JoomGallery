<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Tasks;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use \Joomla\CMS\HTML\Helpers\Sidebar;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\Component\Content\Administrator\Extension\ContentComponent;

/**
 * View class for a list of batch tasks.
 * 
 * @package JoomGallery
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{
  protected $items;

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
    /** @var TasksModel $model */
    $model = $this->getModel();

    $this->state          = $model->getState();
    $this->items          = $model->getItems();
    $this->filterForm       = $model->getFilterForm();
    $this->activeFilters    = $model->getActiveFilters();
    $this->scheduledTasks = $model->getScheduledTasks();

    // Check for errors.
    if(\count($errors = $model->getErrors()))
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
   * @since   4.2.0
   */
  protected function addToolbar()
  {
    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_TASKS_MANAGER'), 'play-circle');

    /** @var Toolbar $model */
    $toolbar = $this->getToolbar();

    // Check if the form exists before showing the add/edit buttons
    $formPath = _JOOM_PATH_ADMIN . '/src/View/Tasks';

    // Show button back to control panel
    $html = '<a href="index.php?option=com_joomgallery&amp;view=control" class="btn btn-primary"><span class="icon-arrow-left-4" title="'.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'"></span> '.Text::_('COM_JOOMGALLERY_CONTROL_PANEL').'</a>';
    $toolbar->appendButton('Custom', $html);

    // New button
    if(file_exists($formPath))
    {
      if($this->getAcl()->checkACL('add', 'task'))
      {
        $toolbar->linkButton('new', 'JTOOLBAR_NEW')
                ->url('index.php?option=com_joomgallery&view=task&layout=select')
                ->buttonClass('btn btn-success')
                ->icon('icon-new');
      }
    }

    // Delete button
    if($this->getAcl()->checkACL('delete', 'task'))
    {
      $toolbar->delete('tasks.delete')
        ->text('JTOOLBAR_DELETE')
        ->message(Text::_('COM_JOOMGALLERY_CONFIRM_DELETE_TASKS'))
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
    Sidebar::setAction('index.php?option=com_joomgallery&view=tasks');
}

  /**
   * Method to order fields
   *
   * @return void
   */
  protected function getSortFields()
  {
    return [
      'a.`ordering`'  => Text::_('JGRID_HEADING_ORDERING'),
      'a.`published`' => Text::_('JSTATUS'),
      'a.`failed`'    => Text::_('JSTATUS'),
      'a.`completed`' => Text::_('JSTATUS'),
      'a.`id`'        => Text::_('JGRID_HEADING_ID'),
    ];
  }
}
