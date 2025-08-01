<?php

/**
 * @package         Joomla.Site
 * @subpackage      com_contact
 *
 * @copyright   (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomgallery\Component\Joomgallery\Site\View\Usercategories;

//use Joomla\CMS\Factory;
//use Joomla\CMS\Helper\TagsHelper;
//use Joomla\CMS\Language\Multilanguage;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
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
  protected $items;

  protected $pagination;

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

    // Get model data
    $model = $this->getModel();

    $this->state  = $model->getState();
    $this->params = $model->getParams();

    $this->items         = $model->getItems();
    $this->pagination    = $model->getPagination();
    $this->filterForm    = $model->getFilterForm();
    $this->activeFilters = $model->getActiveFilters();

    $this->isDevelopSite = boolval($this->params['configs']->get('isDebugSite'))
      || $this->app->input->getBool('isDevelop');

    // Check for errors.
    if (\count($errors = $this->get('Errors')))
    {
      throw new GenericDataException(\implode("\n", $errors), 500);
    }

    //	user must be logged in and have one 'master/base' category
    $this->isUserLoggedIn = true;
    if ($user->guest)
    {
      $this->isUserLoggedIn = false;
    }

    // at least one category is needed for upload view
    $this->isUserHasCategory = $model->getUserHasACategory($user);

    $this->userId = $user->id;

    // Get access service
    $this->component->createAccess();
    $this->acl = $this->component->getAccess();
    // $acl       = $this->component->getAccess();

    // Needed for JgcategoryField
    // $this->isUserCoreManager = $acl->checkACL('core.manage', 'com_joomgallery');
    $this->isUserCoreManager = $this->acl->checkACL('core.manage', 'com_joomgallery');

    // Check if is userspace is enabled
    // Check access permission (ACL)
    if($this->params['configs']->get('jg_userspace', 1, 'int') == 0 || !$this->getAcl()->checkACL('manage', 'com_joomgallery'))
    {
      if($this->params['configs']->get('jg_userspace', 1, 'int') == 0)
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_CATEGORIES_VIEW_NO_ACCESS'), 'message');
      }

      // Redirect to category view
      $this->app->redirect(Route::_('index.php?option='._JOOM_OPTION.'&view=category&id=1'));

      return false;
    }

    // Preprocess the list of items to find ordering divisions.
    foreach($this->items as &$item)
    {
      $this->ordering[$item->parent_id][] = $item->id;
    }

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
      $breadcrumbTitle = Text::_('COM_JOOMGALLERY_USER_CATEGORIES');

      if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbTitle, '');
      }
    }
  }
}
