<?php
/** 
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
defined('_JEXEC') or die;

use \Joomla\Uri\Uri;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Language\Text;
use \Joomla\Registry\Registry;
use \Joomla\Filesystem\Folder;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Helper for the configuration manager
 *
 * @static
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigHelper
{
  /**
   * Get a list of options for the jg_replaceinfo->source form field
   * based on its attributes
   *
   * @param   Form    $form    Form object containing jg_replaceinfo->source form field
	 *
	 * @return  array   List of options
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public static function getReplaceinfoOptions($form)
  {
    // Check if we got a valid form
    if(\is_object($form) && $form instanceof Form)
    {
      if($form->getName() == 'com_joomgallery.config')
      {
        // We got the complete config form
        $formtype     = 'form';
        $exif_options = $form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->getAttribute('EXIF');
        $iptc_options = $form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->getAttribute('IPTC');
      }
      elseif(\strpos($form->getName(), 'subform.jg_replaceinfo') !== false)
      {
        // We got a jg_replaceinfo subform
        $formtype     = 'subform';
        $exif_options = $form->getField('source')->getAttribute('EXIF');
        $iptc_options = $form->getField('source')->getAttribute('IPTC');
      }
      else
      {
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'), 'error', 'jerror');
        throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
      }

      require JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/iptcarray.php';
      require JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/exifarray.php';
      
      $lang = Factory::getApplication()->getLanguage();
      $lang->load(_JOOM_OPTION.'.exif', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);
      $lang->load(_JOOM_OPTION.'.iptc', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);

      // create dropdown list of metadata sources
      $exif_options = \json_decode(\str_replace('\'', '"', $exif_options));
      $iptc_options = \json_decode(\str_replace('\'', '"', $iptc_options));

      // initialise options array
      $options = array();

      foreach ($exif_options as $key => $exif_option)
      {
        // add all defined exif options
        $text  = Text::_($exif_config_array[$exif_option[0]][$exif_option[1]]['Name']).' (exif)';
        $value = $exif_option[0] . '-' . $exif_option[1];

        \array_push($options, array('text' => $text, 'value'=>$value));
      }

      foreach ($iptc_options as $key => $iptc_option)
      {
        // add all defined iptc options
        $text  = Text::_($iptc_config_array[$iptc_option[0]][$iptc_option[1]]['Name']).' (iptc)';
        $value = $iptc_option[0] . '-' . $iptc_option[1];

        \array_push($options, array('text' => $text, 'value'=>$value));
      }

      return $options;
    }
    else
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
    }
  }

  /**
	 * Get a list of options for the jg_filesystem form field
   *
   * @param   Form    $form    Form object containing jg_filesystem form field
   * @param   bool    True to return a list of array, false for a list of objects
   * 
   * @return  array   List of options
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public static function getFilesystemOptions($form, $array=true)
  {
    // Check if we got a valid form object
    if(\is_object($form) && $form instanceof Form && $form->getName() == 'com_joomgallery.config')
    {
      $filesystem = JoomHelper::getService('filesystem');
      $providers  = $filesystem->getProviders();

      $options = array();
      foreach($providers as $provider)
      {
        foreach ($provider->adapterNames as $adapter)
        {
          $val    = $provider->name . '-' . $adapter;
          $text   = $provider->displayName . ' (' . $adapter . ')';
          $option = array('text' => $text, 'value'=>$val);

          // Convert to object if needed
          if(!$array)
          {
            $option = (object) $option;
          }

          \array_push($options, $option);
        }
      }

      return $options;
    }
    else
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
    }
  }

  /**
   * Get a list of options for the jg_replaceinfo->source form field
   * based on its attributes
   *
   * @param   Form    $form    Form object containing jg_router form field
	 *
	 * @return  array   List of options
	 *
	 * @since   4.0.0
   * @throws  \Exception
	 */
  public static function getRouterOptions($form)
  {
    // Check if we got a valid form
    if(\is_object($form) && $form instanceof Form)
    {
      $options = array();

      // Get a list of all available routers (folder: /site/src/Service)
      $files = Folder::files(JPATH_SITE.'/components/'._JOOM_OPTION.'/src/Service', '.php$', false, true);

      // Gather info from routers
      foreach($files as $path)
      {
        $name = \ucfirst(\basename($path, '.php'));

        // Only look at files containing "Router"        
        if(\strpos($name, 'Router') !== false)
        {
          $router = 'Joomgallery\\Component\\Joomgallery\\Site\\Service\\' . \ucfirst($name);

          array_push($options, array('text' => Text::_($router::$displayName), 'value'=>$name));
        }
      }
    }
    else
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_INVALID_FORM_OBJECT'));
    }

    return $options;
  }

  /**
	 * - Checks if we are visiting a joomgallery form
   * - If yes, guess context and item id
   * 
   * @param   Registry     Form data
	 *
	 * @return  array|bool   array(context, id) on success, false otherwise
	 *
	 * @since   4.0.0
	 */
  public static function getFormContext($formdata)
  {
    $option = Factory::getApplication()->getInput()->getCmd('option');
    $layout = Factory::getApplication()->getInput()->getCmd('layout', 'default');

    if($option == 'com_joomgallery' && $layout == 'edit')
    {
      // We are in a joomgallery item form view
      $context   = 'com_joomgallery.'.Factory::getApplication()->getInput()->getCmd('view', '');
      $contextID = $formdata->get('id', null);

      if($contextID == 0)
      {
        $contextID = null;
      }

      return array($context, $contextID);
    }
    elseif($option == 'com_menus' && $layout == 'edit')
    {
      // We are in a menu item form view      
      if($formdata->get('type', '') == 'component')
      {
        $uri = new Uri($formdata->get('link'));

        if($uri->getVar('option', 'com_menus') == 'com_joomgallery')
        {
          if($view = $uri->getVar('view', false))
          {
            $context   = 'com_joomgallery.'.$view;
            $contextID = $uri->getVar('id', null);
          }
          else
          {
            $context   = 'com_joomgallery';
            $contextID = null;
          }

          if($contextID == 0)
          {
            $contextID = null;
          }

          return array($context, $contextID);
        }
      }
    }

    return false;
  }
}
