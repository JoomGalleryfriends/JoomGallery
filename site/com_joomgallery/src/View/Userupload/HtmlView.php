<?php

/********************************************************************************************
 * **   @package    com_joomgallery                                                        **
 * **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 * **   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
 * **   @license    GNU General Public License version 3 or later                          **
 *******************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Userupload;

//use Joomla\CMS\Factory;
//use Joomla\CMS\Helper\TagsHelper;
//use Joomla\CMS\Language\Multilanguage;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\Database\DatabaseInterface;

// use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
//use Joomla\Component\Contact\Administrator\Helper\ContactHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * HTML Contact View class for the Contact component
 *
 * @since  4.0.0
 */
class HtmlView extends JoomGalleryView // BaseHtmlView
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
    protected $item;

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

//    /**
//     * @var    \Joomla\Registry\Registry
//     * @since  4.0.0
//     */
//    protected $config;

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

  protected $uploadLimit;
  protected $postMaxSize;
  protected $memoryLimit;
  protected $maxSize;
  protected $mediaSize;
  protected $configSize;

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

        // Get model data
	    $model             = $this->getModel();
	    $this->state       = $model->getState();
        $this->form        = $model->getForm();
        $this->params      = $model->getParams();
//      $this->return_page = $this->getReturnPage();

	    $config     = $this->params['configs'];

		//	user must be logged in and have one 'master/base' category
	    $this->isUserLoggedIn = true;
		if ($user->guest) {
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
	    $js_vars               = new \stdClass();
	    $js_vars->maxFileSize  = (100 * 1073741824); // 100GB
	    $js_vars->TUSlocation  = $this->getTusLocation (); // $this->item->tus_location;

	    $js_vars->allowedTypes = $this->getAllowedTypes();

	    $js_vars->uppyTarget   = '#drag-drop-area';          // Id of the DOM element to apply the uppy form
	    $js_vars->uppyLimit    = 5;                          // Number of concurrent tus uploads (only file upload)
	    $js_vars->uppyDelays   = array(0, 1000, 3000, 5000); // Delay in ms between upload retries

	    $js_vars->semaCalls    = $config->get('jg_parallelprocesses', 1); // Number of concurrent async calls to save the record to DB (including image processing)
	    $js_vars->semaTokens   = 100;                                           // Pre alloc space for 100 tokens

	    $this->js_vars = $js_vars;

      //--- Limits php.ini, config ----------------------------------------------------------------

      $this->limitsPhpConfig($config);


//
//        if (empty($this->item->id)) {
//            $authorised = $user->authorise('core.create', 'com_contact') || count($user->getAuthorisedCategories('com_contact', 'core.create'));
//        } else {
//            // Since we don't track these assets at the item level, use the category id.
//            $canDo      = ContactHelper::getActions('com_contact', 'category', $this->item->catid);
//            $authorised = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by === $user->id);
//        }
//
//        if ($authorised !== true) {
//            $app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
//            $app->setHeader('status', 403, true);
//
//            return false;
//        }
//
//        $this->item->tags = new TagsHelper();
//
//        if (!empty($this->item->id)) {
//            $this->item->tags->getItemTags('com_contact.contact', $this->item->id);
//        }
//
//        // Check for errors.
//        if (count($errors = $this->get('Errors'))) {
//            $app->enqueueMessage(implode("\n", $errors), 'error');
//
//            return false;
//        }
//

//        // Escape strings for HTML output
//        $this->pageclass_sfx = htmlspecialchars($this->params->get('pageclass_sfx', ''));
//
//        // Override global params with contact specific params
//        $this->params->merge($this->item->params);
//
//        // Propose current language as default when creating new contact
//        if (empty($this->item->id) && Multilanguage::isEnabled()) {
//            $lang = $this->getLanguage()->getTag();
//            $this->form->setFieldAttribute('language', 'default', $lang);
//        }
//
//        $this->_prepareDocument();

        parent::display($tpl);
    }

//    /**
//     * Prepares the document
//     *
//     * @return  void
//     *
//     * @throws \Exception
//     *
//     * @since  4.0.0
//     */
//    protected function _prepareDocument()
//    {
//        $app = Factory::getApplication();
//
//        // Because the application sets a default page title,
//        // we need to get it from the menu item itself
//        $menu = $app->getMenu()->getActive();
//
//        if ($menu) {
//            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
//        } else {
//            $this->params->def('page_heading', Text::_('COM_CONTACT_FORM_EDIT_CONTACT'));
//        }
//
//        $title = $this->params->def('page_title', Text::_('COM_CONTACT_FORM_EDIT_CONTACT'));
//
//        $this->setDocumentTitle($title);
//
//        $pathway = $app->getPathWay();
//        $pathway->addItem($title, '');
//
//        if ($this->params->get('menu-meta_description')) {
//            $this->getDocument()->setDescription($this->params->get('menu-meta_description'));
//        }
//
//        if ($this->params->get('menu-meta_keywords')) {
//            $this->getDocument()->setMetaData('keywords', $this->params->get('menu-meta_keywords'));
//        }
//
//        if ($this->params->get('robots')) {
//            $this->getDocument()->setMetaData('robots', $this->params->get('robots'));
//        }
//    }

	/**
	 * Get array of all allowed filetypes based on the config parameter jg_imagetypes.
	 *
	 * @return  array  List with all allowed filetypes
	 *
	 */
	protected function getAllowedTypes()
	{
		$config     = $this->params['configs'];
		$types = \explode(',', $config->get('jg_imagetypes'));

		// add different types of jpg files
		$jpg_array = array('jpg', 'jpeg', 'jpe', 'jfif');
		if (\in_array('jpg', $types) || \in_array('jpeg', $types) || \in_array('jpe', $types) || \in_array('jfif', $types))
		{
			foreach ($jpg_array as $jpg)
			{
				if(!\in_array($jpg, $types))
				{
					\array_push($types, $jpg);
				}
			}
		}

		// add point to types
		foreach ($types as $key => $type)
		{
			if(\substr($type, 0, 1) !== '.')
			{
				$types[$key] = '.'. \strtolower($type);
			}
			else
			{
				$types[$key] = \strtolower($type);
			}
		}

		return $types;
	}

	private function getTusLocation()
	{

		// Create tus server
		$this->component->createTusServer();
		$server = $this->component->getTusServer();

		$tus_location = $server->getLocation();

		return $tus_location;
	}

  /**
   * Reads php.ini values to determine the minimum size for upload
   * The memory_limit for the php script was not reliable (0 on some sytems)
   * so it is just shown
   *
   * @param   mixed  $joomGalleryConfig config of joom gallery
   *
   *
   * @since version 4.1
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
    if (empty($mediaUploadMaxsize)) {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize, $this->configSize);
    } else {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize, $this->configSize, $mediaUploadMaxsize);
    }
  }

} // class
