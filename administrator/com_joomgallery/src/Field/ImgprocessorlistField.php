<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Form\Field\ListField;


/**
 * Form Field class for the Joomla Platform.
 * Supports a generic list of options.
 *
 * @since  4.0.0
 */
class ImgProcessorListField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $type = 'imgprocessorlist';

  /**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   4.0.0
	 */
	protected function getOptions()
	{
		$options = parent::getOptions();

    // Load plugins in order to search for additional ImageProcessor plugins
    PluginHelper::importPlugin('joomgallery');
    $plugins = Factory::getApplication()->triggerEvent('onJoomImgProcessorGetName');

    foreach($plugins as $plugin)
    {
      if(\is_array($plugin) && \key_exists('value', $plugin) && \key_exists('text', $plugin))
      {
        \array_push($options, (object) $plugin);
      }
      else
      {
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_PLUGIN_ERROR_RETURN_VALUE', 'onJoomImgProcessorGetName',  'array',  'value, text, desc'), 'error', 'jerror');
        throw new \Exception(Text::sprintf('COM_JOOMGALLERY_PLUGIN_ERROR_RETURN_VALUE', 'onJoomImgProcessorGetName',  'array',  'value, text, desc'));
      }
    }

		return $options;
	}

  /**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   4.0.0
	 */
	protected function getInput()
	{
    $component = Factory::getApplication()->bootComponent('com_joomgallery');
    $descs = array();

    // Check GD version
    $component->createIMGtools('gd');
    if($version = $component->getIMGtools()->version())
    {
      // GD up and running
      $descs['gd'] = Text::sprintf('COM_JOOMGALLERY_CONFIG_GDLIB_VERSION', $version);
    }
    else
    {
      // GD missing
      $descs['gd'] = Text::_('COM_JOOMGALLERY_CONFIG_GDLIB_MISSING');
    }
    $component->delIMGtools();

    // Check GD version
    $component->createIMGtools('im');
    if($version = $component->getIMGtools()->version())
    {
      // GD up and running
      $descs['im'] = Text::sprintf('COM_JOOMGALLERY_CONFIG_IMAGEMAGICK_VERSION', $version);
    }
    else
    {
      // GD missing
      $descs['im'] = Text::_('COM_JOOMGALLERY_CONFIG_IMAGEMAGICK_MISSING');
    }
    $component->delIMGtools();

		
    // Load plugins in order to search for additional ImageProcessor plugins
    PluginHelper::importPlugin('joomgallery');
    $plugins = Factory::getApplication()->triggerEvent('onJoomImgProcessorGetName');

    foreach($plugins as $plugin)
    {
      if(\is_array($plugin) && \key_exists('value', $plugin) && \key_exists('desc', $plugin))
      {
        $descs[$plugin['value']] = $plugin['desc'];
      }
      elseif(\is_array($plugin) && \key_exists('value', $plugin) && \key_exists('description', $plugin))
      {
        $descs[$plugin['value']] = $plugin['description'];
      }
      else
      {
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_PLUGIN_ERROR_RETURN_VALUE', 'onJoomImgProcessorGetName',  'array',  'value, text, desc'), 'error', 'jerror');
        throw new \Exception(Text::sprintf('COM_JOOMGALLERY_PLUGIN_ERROR_RETURN_VALUE', 'onJoomImgProcessorGetName',  'array',  'value, text, desc'));
      }
    }

    $html_desc = '';
    foreach($descs as $key => $desc)
    {
      $class = '';
      if($key != $this->value)
      {
        $class = 'hidden';
      }

      $html_desc .= '<span id=\"jg_imgprocessor_supplement_'.$key.'\" class=\"'.$class.'\">';
      $html_desc .= $desc;
      $html_desc .= '</span>';
    }
    
    $js  = 'var changeImgProcessorDesc = function() {';
    $js .=      'let select_val = document.getElementById("jform_jg_imgprocessor").value;';
    $js .=      'let elem = document.getElementById("jg_imgprocessor_supplement_"+select_val);';
    $js .=      'let elems = Array.from(document.getElementById("jg_imgprocessor_supplement").children);';
    $js .=      'elems.forEach(child => {child.classList.add("hidden")});';
    $js .=      'elem.classList.remove("hidden")';
    $js .= '};';

    $js .= 'var callback = function(){';
    $js .=      'document.getElementById("jg_imgprocessor_supplement").innerHTML = "'.$html_desc.'";';
    $js .= '};';
    $js .= 'if ( document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)){callback();} else {document.addEventListener("DOMContentLoaded", callback);}';

    $input = parent::getInput();

		return $input.'<script>'.$js.'</script>';
	}
}
