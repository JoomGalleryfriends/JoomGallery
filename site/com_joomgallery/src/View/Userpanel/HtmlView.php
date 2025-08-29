<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Userpanel;

use \Joomla\Registry\Registry;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Pagination\Pagination;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * HTML Contact View class for the Contact component
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{

  /**
   * @var    array
   * @since   4.2.0
   */
  protected array $userData;

  /**
   * @var Registry
   * @since version
   */
  protected Registry $config;

  /**
   * @var    array
   * @since   4.2.0
   */
  protected array $items;

  /**
   * @var Pagination
   * @since   4.2.0
   */
  protected Pagination $pagination;

  /**
   * @var    string
   * @since   4.2.0
   */
  protected string $return_page;

  /**
   * @var    \Joomla\Registry\Registry
   * @since   4.2.0
   */
  protected $state;

  /**
   * @var    array
   * @since   4.2.0
   */
  protected array $params;

  /**
   * @var    bool
   * @since   4.2.0
   */
  protected bool $isUserLoggedIn = false;

  /**
   * @var    bool
   * @since   4.2.0
   */
  protected bool $isUserHasCategory = false;

  /**
   * @var    bool
   * @since   4.2.0
   */
  protected bool $isUserCoreManager = false;

  /**
   * @var    bool
   * @since   4.2.0
   */
  protected bool $isDevelopSite = false;

  /**
   * @var int
   * @since   4.2.0
   */
  protected int $userId = 0;

  /**
   * Execute and display a template script.
   *
   * @param   string   $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  void
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function display($tpl = null): void
  {
    $user = $this->getCurrentUser();
//    $app  = Factory::getApplication();

//    // ToDo: in next version include both image and category views
//    //--- include both image and category views -----------------------------
//
//    //  https://joomla.stackexchange.com/questions/33248/how-to-load-and-render-a-view-of-a-component-from-anothers-component-template-f
//    // $modCategories = $this->getModel('Usercategories');


    // Get modUserPanel data
    $modUserPanel = $this->getModel('Userpanel');

    $this->state  = $modUserPanel->getState();
    $this->params = $modUserPanel->getParams();

    $this->items         = $modUserPanel->getItems();
    $this->pagination    = $modUserPanel->getPagination();
    $this->filterForm    = $modUserPanel->getFilterForm();
    $this->activeFilters = $modUserPanel->getActiveFilters();

    $this->isDevelopSite = (bool) ($this->params['configs']->get('isDebugSite'))
      || $this->app->input->getBool('isDevelop');

    $this->config = $this->params['configs'];


    // Check for errors.
    if(\count($errors = $modUserPanel->getErrors()))
    {
      throw new GenericDataException(\implode("\n", $errors), 500);
    }

    //	user must be logged in and have one 'master/base' category
    $this->isUserLoggedIn = true;
    if($user->guest)
    {
      $this->isUserLoggedIn = false;
    }

    // at least one category is needed for upload view
    $this->isUserHasCategory = $modUserPanel->getUserHasACategory($user->id);

    $this->userId = $user->id;

    // Get access service
    $this->component->createAccess();
    $this->acl = $this->component->getAccess();

    // Needed for JgcategoryField
    $this->isUserCoreManager = $this->acl->checkACL('core.manage', 'com_joomgallery');


    $this->userData = [];
    $modUserPanel->assignUserData ($this->userData, $this->userId);





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
      $breadcrumbTitle = Text::_('COM_JOOMGALLERY_USER_PANEL');

      if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbTitle, '');
      }
    }
  }

}
