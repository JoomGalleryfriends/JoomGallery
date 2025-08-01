<?php

/**
 * @package         Joomla.Site
 * @subpackage      com_contact
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomgallery\Component\Joomgallery\Site\View\Userpanel;

//use Joomla\CMS\Factory;
//use Joomla\CMS\Helper\TagsHelper;
//use Joomla\CMS\Language\Multilanguage;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;

//use Joomla\Component\Contact\Administrator\Helper\ContactHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML Contact View class for the Contact component
 *
 * @since  4.0.0
 */
class HtmlView extends JoomGalleryView
{
  /**
   * @var    \Joomla\CMS\Form\Form
   * @since  4.0.0
   */
  protected $form;

  /**
   * @var    object
   * @since  4.0.0
   */
  protected $items;

  protected $pagination;

  /**
   * @var    string
   * @since  4.0.0
   */
  protected $return_page;

  /**
   * @var    string
   * @since  4.0.0
   */
  protected $pageclass_sfx;

  /**
   * @var    \Joomla\Registry\Registry
   * @since  4.0.0
   */
  protected $state;

  /**
   * @var    \Joomla\Registry\Registry
   * @since  4.0.0
   */
  protected $params;

  /**
   * @var    bool
   * @since  4.0.0
   */
  protected $isUserLoggedIn = false;
  /**
   * @var    bool
   * @since  4.0.0
   */
  protected $isUserHasCategory = false;

  protected $isUserCoreManager = false;
  protected $userId = 0;
  protected $isDevelopSite = false;

  /**
   * Execute and display a template script.
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  void|boolean
   *
   * @throws \Exception
   * @since  4.0.0
   */
  public function display($tpl = null)
  {
    $user = $this->getCurrentUser();
    $app  = Factory::getApplication();

    //--- include both views -----------------------------

    // https://joomla.stackexchange.com/questions/33248/how-to-load-and-render-a-view-of-a-component-from-anothers-component-template-f
    // below

    // Get modImages data
    $modImages = $this->getModel('Userpanel');
    // $modCategories = $this->getModel('Usercategories');

    $this->state = $modImages->getState();
    $this->params = $modImages->getParams();

    $this->items         = $modImages->getItems();
    $this->pagination    = $modImages->getPagination();
    $this->filterForm    = $modImages->getFilterForm();
    $this->activeFilters = $modImages->getActiveFilters();

    $this->isDevelopSite = boolval($this->params['configs']->get('isDebugSite'))
      || $this->app->input->getBool('isDevelop');

    // Check for errors.
    if(\count($errors = $this->get('Errors')))
    {
      throw new GenericDataException(\implode("\n", $errors), 500);
    }

    // $config = $this->params['configs'];

    //	user must be logged in and have one 'master/base' category
    $this->isUserLoggedIn = true;
    if ($user->guest)
    {
      $this->isUserLoggedIn = false;
    }

    // at least one category is needed for upload view
    $this->isUserHasCategory = $modImages->getUserHasACategory($user);

    $this->userId = $user->id;

    // Get access service
    $this->component->createAccess();
    $this->acl = $this->component->getAccess();
    // $acl       = $this->component->getAccess();

    // Needed for JgcategoryField
    // $this->isUserCoreManager = $acl->checkACL('core.manage', 'com_joomgallery');
    $this->isUserCoreManager = $this->acl->checkACL('core.manage', 'com_joomgallery');

//    // Check if is userspace is enabled
//    // Check access permission (ACL)
//    if($this->params['configs']->get('jg_userspace', 1, 'int') == 0 || !$this->getAcl()->checkACL('manage', 'com_joomgallery'))
//    {
//      if($this->params['configs']->get('jg_userspace', 1, 'int') == 0)
//      {
//        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_IMAGES_VIEW_NO_ACCESS'), 'message');
//      }
//
//      // Redirect to gallery view
//      $this->app->redirect(Route::_(JoomHelper::getViewRoute('gallery')));
//
//      return false;
//    }

    $this->_prepareDocument();

    parent::display($tpl);
  }

  /**
   * Prepares the document
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function _prepareDocument()
  {
    $menus = $this->app->getMenu();
    $title = null;

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
      $pathway = $this->app->getPathway();
      $breadcrumbTitle = Text::_('COM_JOOMGALLERY_USER_PANEL');

      if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbTitle, '');
      }
    }
  }

}
