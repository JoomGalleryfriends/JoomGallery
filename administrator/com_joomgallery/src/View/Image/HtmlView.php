<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Helper\MediaHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Image.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $item;
	protected $form;
  protected $config;
  protected $imagetypes;

  protected $uploadLimit;
  protected $postMaxSize;
  protected $memoryLimit;
  protected $maxSize;
  protected $mediaSize;

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
		/** @var ImageModel $model */
    $model = $this->getModel();

		$this->state      = $model->getState();
		$this->item       = $model->getItem();
		$this->form       = $model->getForm();
    $this->config     = JoomHelper::getService('config');
    $this->imagetypes = JoomHelper::getRecords('imagetypes');
    $rating           = JoomHelper::getRating($this->item->id);
    $this->form->setvalue('rating', '', $rating);
	$this->app->getLanguage()->load('com_joomgallery.exif', _JOOM_PATH_ADMIN);
	$this->app->getLanguage()->load('com_joomgallery.iptc', _JOOM_PATH_ADMIN);

    if($this->item->id == 0)
    {
      // create a new image record
      $this->form->setFieldAttribute('image', 'required', true);
    }

		// Check for errors.
		if(count($errors = $model->getErrors()))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

    if($this->_layout == 'upload')
    {
      $this->addToolbarUpload();

      // Add variables to JavaScript
      $js_vars               = new \stdClass();
      $js_vars->maxFileSize  = (100 * 1073741824); // 100GB
      $js_vars->TUSlocation  = $this->item->tus_location;
      $js_vars->allowedTypes = $this->getAllowedTypes();

      $js_vars->uppyTarget   = '#drag-drop-area';          // Id of the DOM element to apply the uppy form
      $js_vars->uppyLimit    = 5;                          // Number of concurrent tus upploads (only file upload)
      $js_vars->uppyDelays   = array(0, 1000, 3000, 5000); // Delay in ms between upload retrys

      $js_vars->semaCalls    = $this->config->get('jg_parallelprocesses', 1); // Number of concurrent async calls to save the record to DB (including image processing)
      $js_vars->semaTokens   = 100;                                           // Prealloc space for 100 tokens

      $this->js_vars = $js_vars;

      //--- Limits php.ini, config ----------------------------------------------------------------

      $this->limitsPhpConfig($this->config);
    }
    elseif($this->_layout == 'replace')
    {
      if($this->item->id == 0)
      {
        $this->component->addLog('View needs an image ID to be loaded.', 'error', 'jerror');
        throw new \Exception("View needs an image ID to be loaded.", 1);
      }
      $this->addToolbarReplace();
      $this->modifyFieldsReplace();
    }
    else
    {
      $this->addToolbarEdit();
    }
		
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar for the image edit form.
	 *
	 * @return void
	 *
	 * @throws Exception
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

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES').' :: '.Text::_('COM_JOOMGALLERY_IMAGE_EDIT'), "image");

		// If not checked out, can save the item.
		if(!$checkedOut && ($this->getAcl()->checkACL('core.edit') || ($this->getAcl()->checkACL('core.create'))))
		{
			ToolbarHelper::apply('image.apply', 'JTOOLBAR_APPLY');
		}

		if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
		{
			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure
            (
				function (Toolbar $childBar) use ($checkedOut, $isNew)
				{
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

		if (!empty($this->item->id))
		{
			ToolbarHelper::custom('image.savemetadata', 'save', '', 'Save metadata to file', false);
		}
	}

  /**
	 * Add the page title and toolbar for the upload form.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbarUpload()
	{
    Factory::getApplication()->input->set('hidemainmenu', true);

    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES').' :: '.Text::_('COM_JOOMGALLERY_IMAGES_UPLOAD'), "image");
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
	 *
	 */
  protected function getAllowedTypes()
  {
    $types = \explode(',', $this->config->get('jg_imagetypes'));

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

  /**
	 * Add the page title and toolbar for the imagetype replace form.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbarReplace()
	{
    Factory::getApplication()->input->set('hidemainmenu', true);

    ToolbarHelper::title(Text::_('COM_JOOMGALLERY_IMAGES').' :: '.Text::_('COM_JOOMGALLERY_REPLACE'), "image");

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
	 *
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
   * @since version 4.1
   */
  public function limitsPhpConfig(): void
  {
    $mediaHelper = new MediaHelper;

    // Maximum allowed size in MB
    $this->uploadLimit = round($mediaHelper->toBytes(ini_get('upload_max_filesize')) / (1024 * 1024));
    $this->postMaxSize = round($mediaHelper->toBytes(ini_get('post_max_size')) / (1024 * 1024));
    $this->memoryLimit = round($mediaHelper->toBytes(ini_get('memory_limit')) / (1024 * 1024));

    $mediaParams        = ComponentHelper::getParams('com_media');
    $mediaUploadMaxsize = $mediaParams->get('upload_maxsize', 0);
    $this->mediaSize    = $mediaUploadMaxsize;

    //--- Max size to be used (previously defined by joomla function but ...) -------------------------

    // $uploadMaxSize=0 for no limit
    if (empty($mediaUploadMaxsize)) {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize);
    } else {
      $this->maxSize = min($this->uploadLimit, $this->postMaxSize, $mediaUploadMaxsize);
    }
  }

}
