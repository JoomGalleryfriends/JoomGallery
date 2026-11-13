<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Collections;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for the administrator list of collections.
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class HtmlView extends JoomGalleryView
{
  /** @var array  Collections displayed by the view. */
  protected $items;

  /** @var \Joomla\CMS\Pagination\Pagination  Pagination object for the collection list. */
  protected $pagination;

  protected $ownerOptions;
  protected $managementItem;

  /**
   * Display the collection list.
   *
   * @param   string|null  $tpl  Optional template name.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   * @throws  GenericDataException  When the model reports an error.
   */
  public function display($tpl = null)
  {
    $model               = $this->getModel();
    $this->state         = $model->getState();
    $this->items         = $model->getItems();
    $this->pagination    = $model->getPagination();
    $this->filterForm    = $model->getFilterForm();
    $this->activeFilters = $model->getActiveFilters();

    if(\count($errors = $model->getErrors()))
    {
      throw new GenericDataException(implode("\n", $errors), 500);
    }

    $this->ownerOptions = $model->getOwnerOptions();
    $collectionModel    = $this->component->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);

    foreach($this->items as $item)
    {
      $item->managementPermissions = $collectionModel->getManagementPermissions($item);
    }

    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_COLLECTIONS'), 'folder');

    if($this->getAcl()->checkACL('core.create', 'collection'))
    {
      ToolbarHelper::addNew('collection.add');
    }

    Sidebar::setAction('index.php?option=com_joomgallery&view=collections');
    $this->sidebar = Sidebar::render();
    parent::display($tpl);
  }
}
