<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Api\View\Joomgallery;

use Joomgallery\Component\Joomgallery\Api\Helper\JoomgalleryHelper;
use Joomgallery\Component\Joomgallery\Api\Serializer\JoomgallerySerializer;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The joomgallery view
 *
 * @since  4.0.0
 */
class JsonapiView extends BaseApiView
{

  public function displayJGVersion()
  {
    $versionText = "Version=xxxx";

    // Serializing the output
    //$result = json_encode($this->_output);
    $result = json_encode($versionText);

    // Pushing output to the document
    $this->getDocument()->setBuffer($result);

    return $this->getDocument()->render();
  }

 /**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function displayItem($item = null)
	{
//		$testJoomgalleryText = "testJoomgalleryText";
//
//		// Serializing the output
//		//$result = json_encode($this->_output);
//		$result = json_encode($testJoomgalleryText);
//
//		// Pushing output to the document
//		$this->getDocument()->setBuffer($result);
//
//		return $this->getDocument()->render();
	}

	public function display($tpl = null)
	{
		$testJoomgalleryText = "testJoomgalleryText";

//		zzzz();

		// Serializing the output
		//$result = json_encode($this->_output);
		$result = json_encode($testJoomgalleryText);

		// Pushing output to the document
		$this->getDocument()->setBuffer($result);

		return $this->getDocument()->render();
	}

// ToDo: Later The hidden gem of the API view is another string array property, $relationship. In that view you list all the field names returned by your model which refer to related data.


}
