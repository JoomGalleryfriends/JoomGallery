<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\View\Userimage;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomgallery\Component\Joomgallery\Site\Model\UserimageModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of Joomgallery.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{
  /**
   * The category object
   *
   * @var  \stdClass
   * @since   4.2.0
   */
  protected \stdClass $item;

  /**
   * The form object
   *
   * @var  \Joomla\CMS\Form\Form;
   * @since   4.2.0
   */
  protected \Joomla\CMS\Form\Form $form;

  /**
   * The page parameters
   *
   * @var    array
   *
   * @since   4.2.0
   */
  protected array $params = [];

  /**
   * The page to return to after the article is submitted
   *
   * @var  string
   *
   * @since   4.2.0
   */
  protected string $return_page = '';

  protected $state;
  /**
   * @var  array
   *
   * @since   4.2.0
   */
  protected array $imagetypes;

  protected $uploadLimit;
  protected $postMaxSize;
  protected $memoryLimit;
  protected $maxSize;
  protected $mediaSize;

  protected $config;

  /**
   * @var string title of the category
   * @since 4.2
   */
  protected string $categoryTitle = '';
  /**
   * javascript variables for upload
   * @var \stdClass
   * @since version
   */
  private \stdClass $js_vars;

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
    // Get model data
    /** @var UserImageModel $model */
    $model = $this->getModel();

    $this->state  = $model->getState();
    $this->params = $model->getParams();
    $this->item   = $model->getItem();
    $this->form   = $model->getForm();

    $config = $this->params['configs'];

    $this->config = JoomHelper::getService('config');

    $rating = JoomHelper::getRating($this->item->id);
    $this->form->setvalue('rating', '', $rating);

    $this->imagetypes = JoomHelper::getRecords('imagetypes');


    // Get return page
    $this->return_page = $model->getReturnPage();

    // Check for errors.
    if(\count($errors = $model->getErrors()))
    {
      throw new GenericDataException(implode("\n", $errors), 500);
    }

    // Check access view level
    if(!\in_array($this->item->access, $this->getCurrentUser()->getAuthorisedViewLevels()))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error');
    }

    if($this->_layout == 'upload')
    {
      $this->addToolbarUpload();

      // Add variables to JavaScript
      $js_vars               = new \stdClass();
      $js_vars->maxFileSize  = (100 * 1073741824); // 100GB
      $js_vars->TUSlocation  = $this->item->tus_location;
      $js_vars->allowedTypes = $this->getAllowedTypes();

      $js_vars->uppyTarget = '#drag-drop-area';          // Id of the DOM element to apply the uppy form
      $js_vars->uppyLimit  = 5;                          // Number of concurrent tus upploads (only file upload)
      $js_vars->uppyDelays = [0, 1000, 3000, 5000]; // Delay in ms between upload retrys

      $js_vars->semaCalls  = $this->config->get('jg_parallelprocesses', 1); // Number of concurrent async calls to save the record to DB (including image processing)
      $js_vars->semaTokens = 100;                                           // Prealloc space for 100 tokens

      $this->js_vars = $js_vars;

      //--- Limits php.ini, config -----
      $this->limitsPhpConfig();
    }
    elseif($this->_layout == 'replace')
    {
      if($this->item->id == 0)
      {
        $this->component->addLog('View needs an image ID to be loaded.', 'error', 'jerror');
        throw new \Exception('View needs an image ID to be loaded.', 1);
      }

      $this->categoryTitle = $model->categoryTitle($this->item->catid);

      $this->addToolbarReplace();
      $this->modifyFieldsReplace();
    }
    else
    {
      $this->addToolbarEdit();
    }

    // $this->_prepareDocument();

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
      $pathway        = $this->app->getPathway();
      $breadcrumbList = Text::_('COM_JOOMGALLERY_IMAGES');

      if(!\in_array($breadcrumbList, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbList, JoomHelper::getViewRoute('images'));
      }

      $breadcrumbTitle = isset($this->item->id) ? Text::_('JGLOBAL_EDIT') : Text::_('JGLOBAL_FIELD_ADD');

      if(!\in_array($breadcrumbTitle, $pathway->getPathwayNames()))
      {
        $pathway->addItem($breadcrumbTitle, '');
      }
    }
  }


  /**
   * Add the page title and toolbar for the image edit form.
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function addToolbarEdit()
  {
    Factory::getApplication()->input->set('hidemainmenu', true);

    /** @var Toolbar $model */
    $toolbar = $this->getToolbar();

    $user  = Factory::getApplication()->getIdentity();
    $isNew = ($this->item->id == 0);

    if(isset($this->item->checked_out))
    {
      $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->id);
    }
    else
    {
      $checkedOut = false;
    }

    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES') . ' :: ' . Text::_('COM_JOOMGALLERY_IMAGE_EDIT'), 'image');

    // If not checked out, can save the item.
    if(!$checkedOut && ($this->getAcl()->checkACL('core.edit') || ($this->getAcl()->checkACL('core.create'))))
    {
      ToolbarHelper::apply('image.apply', 'JTOOLBAR_APPLY');
    }

    if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
    {
      $saveGroup = $toolbar->dropdownButton('save-group');

    $saveGroup->configure(
        function (Toolbar $childBar) use ($checkedOut, $isNew) {
          $childBar->save('image.save', 'JTOOLBAR_SAVE');

          if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
          {
            $childBar->save2new('image.save2new');
          }

          // If an existing item, can save to a copy.
          if(!$isNew && $this->getAcl()->checkACL('core.create'))
          {
            $childBar->save2copy('image.save2copy');
          }
        }
    );
    }

    if(empty($this->item->id))
    {
      ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CANCEL');
    }
    else
    {
      ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CLOSE');
    }

    if(!empty($this->item->id))
    {
      ToolbarHelper::custom('image.savemetadata', 'save', '', 'Save metadata to file', false);
    }
  }

  /**
   * Add the page title and toolbar for the upload form.
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function addToolbarUpload()
  {
    Factory::getApplication()->input->set('hidemainmenu', true);

    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES') . ' :: ' . Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD'), 'image');
    ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CLOSE');

    // Create tus server
    $this->component->createTusServer();
    $server = $this->component->getTusServer();

    $this->item->tus_location = $server->getLocation();
  }

  /**
   * Get array of all allowed filetypes based on the config parameter jg_imagetypes.
   *
   * @return  array  List with all allowed filetypes
   */
  protected function getAllowedTypes()
  {
    $types = explode(',', $this->config->get('jg_imagetypes'));

    // add different types of jpg files
    $jpg_array = ['jpg', 'jpeg', 'jpe', 'jfif'];

    if(\in_array('jpg', $types) || \in_array('jpeg', $types) || \in_array('jpe', $types) || \in_array('jfif', $types))
    {
      foreach($jpg_array as $jpg)
      {
        if(!\in_array($jpg, $types))
        {
          array_push($types, $jpg);
        }
      }
    }

    // add point to types
    foreach($types as $key => $type)
    {
      if(substr($type, 0, 1) !== '.')
      {
        $types[$key] = '.' . strtolower($type);
      }
      else
      {
        $types[$key] = strtolower($type);
      }
    }

    return $types;
  }

  /**
   * Add the page title and toolbar for the imagetype replace form.
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function addToolbarReplace()
  {
    Factory::getApplication()->input->set('hidemainmenu', true);

    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES') . ' :: ' . Text::_('COM_JOOMGALLERY_REPLACE'), 'image');

    // Add replace button
    if($this->getAcl()->checkACL('core.edit'))
    {
      ToolbarHelper::save('image.replace', 'COM_JOOMGALLERY_REPLACE');
    }

    // Add cancel button
    ToolbarHelper::cancel('image.cancel', 'JTOOLBAR_CANCEL');
  }

  /**
   * Modify form fields according to view needs.
   *
   * @return void
   */
  protected function modifyFieldsReplace()
  {
    $this->form->setFieldAttribute('title', 'required', false);
    $this->form->setFieldAttribute('replacetype', 'required', true);
    $this->form->setFieldAttribute('image', 'required', true);
    $this->form->setFieldAttribute('catid', 'required', false);

    $this->form->setFieldAttribute('id', 'type', 'hidden');

    $this->form->setFieldAttribute('title', 'readonly', true);
    $this->form->setFieldAttribute('alias', 'readonly', true);
    $this->form->setFieldAttribute('catid', 'readonly', true);

    if($this->app->input->get('type', '', 'string') !== '')
    {
      $this->form->setFieldAttribute('replacetype', 'readonly', true);
    }

    $this->form->setFieldAttribute('replacetype', 'default', $this->app->input->get('type', 'original', 'string'));
  }

  /**
   * Reads php.ini values to determine the minimum size for upload
   * The memory_limit for the php script was not reliable (0 on some systems)
   * so it is just shown
   *
   * On UploadMaxsize = 0 (from com_media) the php.ini limits are used
   *
   * @since 4.2
   */
  public function limitsPhpConfig(): void
  {
    $mediaHelper = new MediaHelper();

    // Maximum allowed size in MB
    $this->uploadLimit = round($mediaHelper->toBytes(\ini_get('upload_max_filesize')) / (1024 * 1024));
    $this->postMaxSize = round($mediaHelper->toBytes(\ini_get('post_max_size')) / (1024 * 1024));
    $this->memoryLimit = round($mediaHelper->toBytes(\ini_get('memory_limit')) / (1024 * 1024));

    $mediaParams        = ComponentHelper::getParams('com_media');
    $mediaUploadMaxsize = $mediaParams->get('upload_maxsize', 0);
    $this->mediaSize    = $mediaUploadMaxsize;

    //--- Max size to be used (previously defined by joomla function but ...) -------------------------

    // $uploadMaxSize=0 for no limit
    if(empty($mediaUploadMaxsize))
    {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize);
    }
    else
    {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize, $mediaUploadMaxsize);
    }
  }
}
