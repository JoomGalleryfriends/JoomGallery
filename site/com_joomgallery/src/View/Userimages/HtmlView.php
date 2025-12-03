<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\View\Userimages;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use \Joomgallery\Component\Joomgallery\Site\Model\UserimagesModel;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomla\CMS\Pagination\Pagination;
use \Joomla\CMS\Router\Route;

/**
 * View class for a list of Joomgallery.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{
  /**
   * @var array
   * @since   4.2.0
   */
  protected array $items;

  /**
   * @var Pagination
   * @since   4.2.0
   */
  protected Pagination $pagination;

  /**
   * @var    array
   * @since   4.2.0
   */
  protected array $params;

  /**
   * @var    \Joomla\Registry\Registry
   * @since   4.2.0
   */
  protected $state;

  /**
   * @var    bool
   * @since   4.2.0
   */
  protected bool $isUserLoggedIn = false;

  /**
   * @var    bool
   * @since   4.2.0
   */
  //protected bool $isUserHasCategory = false;

  /**
   * @var bool
   * @since 4.2
   */
  protected bool $isUserCoreManager = false;

  /**
   * @var bool
   * @since 4.2
   */
  protected bool $isDebugSite = false;

  /**
   * @var int
   * @since 4.2
   */
  protected int $userId = 0;

  /**
   * Display the view
   *
   * @param   string   $tpl  Template name
   *
   * @return void
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function display($tpl = null): void
  {
    $user = $this->getCurrentUser();

    // Get model data
    /** @var UserimagesModel $model */
    $model = $this->getModel();

    $this->state  = $model->getState();
    $this->params = $model->getParams();

    $this->items         = $model->getItems();
    $this->pagination    = $model->getPagination();
    $this->filterForm    = $model->getFilterForm();
    $this->activeFilters = $model->getActiveFilters();

    if (empty($this->params['configs']))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Attention: $this->params[\'configs\'] is null'), 'error');
    }

    $this->isDebugSite = (bool) ($this->params['configs']?->get('isDebugSite'))
      || $this->app->input->getBool('isDebug');

    // Check for errors.
    if(\count($errors = $model->getErrors()))
    {
      throw new GenericDataException(implode("\n", $errors), 500);
    }

    //  user must be logged in and have one 'master/base' category
    $this->isUserLoggedIn = true;

    if($user->guest)
    {
      $this->isUserLoggedIn = false;
    }

//    // at least one category is needed for upload view
//    $this->isUserHasCategory = $model->getUserHasACategory($user->id);

    $this->userId = $user->id;

    // Get access service
    $this->component->createAccess();
    $this->acl = $this->component->getAccess();

    // Needed for JgcategoryField
    $this->isUserCoreManager = $this->acl->checkACL('core.manage', 'com_joomgallery');

    // Check access permission (ACL)
    if($this->params['configs']?->get('jg_userspace', 1, 'int') == 0 || !$this->getAcl()->checkACL('manage', 'com_joomgallery'))
    {
      if($this->params['configs']?->get('jg_userspace', 1, 'int') == 0)
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_IMAGES_VIEW_NO_ACCESS'), 'message');
      }

      // Redirect to user panel view
      $this->app->redirect(Route::_('index.php?option='._JOOM_OPTION.'&view=userpanel'));

      return;
    }

    // Prepares the document breadcrumbs
    $this->_prepareDocument();

    parent::display($tpl);
  }

  /**
   * Prepares the document breadcrumbs
   *
   * @return void
   *
   * @throws \Exception
   * @since   4.2.0
   */
  protected function _prepareDocument(): void
  {
    $menus = $this->app->getMenu();

    // Because the application sets a default page title,
    // we need to get it from the menu item itself
    $menu = $menus->getActive();

    if($menu)
    {
      $this->params['menu']->def('page_heading', $this->params['menu']->get('page_title', $menu->title));
    }
    else
    {
      $this->params['menu']->def('page_heading', Text::_('JoomGallery'));
    }

    $title = $this->params['menu']->get('page_title', '');

    if(empty($title))
    {
      $title = $this->app->get('sitename');
    }
    elseif($this->app->get('sitename_pagetitles', 0) == 1)
    {
      $title = Text::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);
    }
    elseif($this->app->get('sitename_pagetitles', 0) == 2)
    {
      $title = Text::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
    }

    $this->document->setTitle($title);

    if($this->params['menu']->get('menu-meta_description'))
    {
      $this->document->setDescription($this->params['menu']->get('menu-meta_description'));
    }

    if($this->params['menu']->get('menu-meta_keywords'))
    {
      $this->document->setMetadata('keywords', $this->params['menu']->get('menu-meta_keywords'));
    }

    if($this->params['menu']->get('robots'))
    {
      $this->document->setMetadata('robots', $this->params['menu']->get('robots'));
    }

    if(!$this->isMenuCurrentView($menu))
    {
      // Add Breadcrumbs
      $pathway         = $this->app->getPathway();
      $breadcrumbTitle = Text::_('COM_JOOMGALLERY_USER_IMAGES');

      if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbTitle, '');
      }
    }
  }

}
