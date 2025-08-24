<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Userupload;

use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\Registry;
use \Joomla\CMS\Helper\MediaHelper;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Server;

/**
 * HTML Contact View class for the Contact component
 *
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{
  /**
   * @var    Form
   * @since   4.2.0
   */
  protected $form;

  /**
   * @var    string
   * @since   4.2.0
   */
  protected string $return_page;

  /**
   * @var    Registry
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
   * @var int
   * @since   4.2.0
   */
  protected int $userId = 0;

  /**
   * @var int
   * @since   4.2.0
   */
  protected int $uploadLimit;
  /**
   * @var int
   * @since   4.2.0
   */
  protected int $postMaxSize;
  /**
   * @var int
   * @since   4.2.0
   */
  protected int $memoryLimit;
  /**
   * @var int
   * @since   4.2.0
   */
  protected int $maxSize;
  /**
   * @var int
   * @since   4.2.0
   */
  protected int $mediaSize;
  /**
   * @var int
   * @since   4.2.0
   */
  protected int $configSize;

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

    // Get model data
    $model        = $this->getModel();
    $this->state  = $model->getState();
    $this->form   = $model->getForm();
    $this->params = $model->getParams();

//     // Get return page
//    $this->return_page = $model->getReturnPage();

    $config = $this->params['configs'];

    //	user must be logged in and have one 'master/base' category
    $this->isUserLoggedIn = true;
    if($user->guest)
    {
      $this->isUserLoggedIn = false;
    }

    // at least one category is needed for upload view
    $this->isUserHasCategory = $model->getUserHasACategory($user);

    $this->userId = $user->id;

    // Get access service
    $this->component->createAccess();
    $this->acl = $this->component->getAccess();
    $acl       = $this->component->getAccess();

    // Needed for JgcategoryField
    // $this->isUserCoreManager = $acl->checkACL('core.manage', 'com_joomgallery');
    $this->isUserCoreManager = $acl->checkACL('core.manage', 'com_joomgallery');

    // Add variables to JavaScript
    $js_vars              = new \stdClass();
    $js_vars->maxFileSize = (100 * 1073741824); // 100GB
    $js_vars->TUSlocation = $this->getTusLocation(); // $this->item->tus_location;

    $js_vars->allowedTypes = $this->getAllowedTypes();

    $js_vars->uppyTarget = '#drag-drop-area';          // Id of the DOM element to apply the uppy form
    $js_vars->uppyLimit  = 5;                          // Number of concurrent tus uploads (only file upload)
    $js_vars->uppyDelays = array(0, 1000, 3000, 5000); // Delay in ms between upload retries

    $js_vars->semaCalls  = $config->get('jg_parallelprocesses', 1); // Number of concurrent async calls to save the record to DB (including image processing)
    $js_vars->semaTokens = 100;                                           // Pre alloc space for 100 tokens

    $this->js_vars = $js_vars;

    //--- Limits php.ini, config ----------------------------------------------------------------

    $this->limitsPhpConfig($config);

    // Prepares the document breadcrumbs
    $this->_prepareDocument();

    parent::display($tpl);
  }

  /**
   * Prepares the document
   *
   * @return  void
   *
   * @throws \Exception
   *
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
      $breadcrumbTitle = Text::_('COM_JOOMGALLERY_USER_UPLOAD');

      if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbTitle, '');
      }
    }
  }

  /**
   * Get array of all allowed filetypes based on the config parameter jg_imagetypes.
   *
   * @return  array  List with all allowed filetypes
   * @since   4.2.0
   *
   */
  protected function getAllowedTypes(): array
  {
    $config = $this->params['configs'];

    /** @var array $types */
    $types = \explode(',', $config->get('jg_imagetypes'));

    // add different types of jpg files
    $jpg_array = array('jpg', 'jpeg', 'jpe', 'jfif');
    if(\in_array('jpg', $types) || \in_array('jpeg', $types) || \in_array('jpe', $types) || \in_array('jfif', $types))
    {
      foreach($jpg_array as $jpg)
      {
        if(!\in_array($jpg, $types))
        {
          \array_push($types, $jpg);
        }
      }
    }

    // add point to types
    foreach($types as $key => $type)
    {
      if(\substr($type, 0, 1) !== '.')
      {
        $types[$key] = '.'.\strtolower($type);
      }
      else
      {
        $types[$key] = \strtolower($type);
      }
    }

    return $types;
  }

  /**
   * Create the tus server and return the (uri) location of the TUS server
   * @return string
   *
   * @since   4.2.0
   */
  private function getTusLocation(): string
  {

    // Create tus server
    $this->component->createTusServer();

    /** @var Server $server */
    $server = $this->component->getTusServer();

    $tus_location = $server->getLocation();

    return $tus_location;
  }

  /**
   * Reads php.ini values to determine the minimum size for upload
   * The memory_limit for the php script was not reliable (0 on some sytems)
   * so it is just shown
   *
   * @param   mixed   $joomGalleryConfig  config of joom gallery
   *
   *
   * @since   4.2.0
   */
  public function limitsPhpConfig(mixed $joomGalleryConfig): void
  {
    $mediaHelper = new MediaHelper;

    // Maximum allowed size in MB
    $this->uploadLimit = round($mediaHelper->toBytes(ini_get('upload_max_filesize')) / (1024 * 1024));
    $this->postMaxSize = round($mediaHelper->toBytes(ini_get('post_max_size')) / (1024 * 1024));
    $this->memoryLimit = round($mediaHelper->toBytes(ini_get('memory_limit')) / (1024 * 1024));

    $mediaParams        = ComponentHelper::getParams('com_media');
    $mediaUploadMaxsize = $mediaParams->get('upload_maxsize', 0);
    $this->mediaSize    = $mediaUploadMaxsize;

    $this->configSize = round($joomGalleryConfig->get('jg_maxfilesize') / (1024 * 1024));

    //--- Max size to be used (previously defined by joomla function but ...) -------------------------

    // $uploadMaxSize=0 for no limit
    if(empty($mediaUploadMaxsize))
    {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize, $this->configSize);
    }
    else
    {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize, $this->configSize, $mediaUploadMaxsize);
    }
  }

} // class
