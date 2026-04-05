<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JSHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

class AibuttonField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  4.4.0
	 */
	protected $type = 'aibutton';

	/**
	 * Hide the label when rendering the form field.
	 *
	 * @var    boolean
	 * @since  4.4.0
	 */
	protected $hiddenLabel = false;

	/**
	 * Hide the description when rendering the form field.
	 *
	 * @var    boolean
	 * @since  4.4.0
	 */
	protected $hiddenDescription = false;

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since  4.4.0
	 */
	protected function getLabel()
	{
    $fn = $this->getAttribute('function');

		$html = '<button id="jgai-test-connection-btn" class="btn btn-primary">' . Text::_($this->element['label']) . '</button>';

		return $html;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   4.4.0
	 */
	protected function getInput()
	{
    // JoomGallery Configuration
    $jg = JoomHelper::getComponent();
    $jg->createConfig();
    $config = $jg->getConfig();

    // Initialize AIinterface
    $opts = [ 'prefix' => 'jgai',
              'host' => $config->get('jg_aiint_host', 'http://localhost/api/v1'),
              'token' => $config->get('jg_aiint_key', ''),
              'client_name' => $this->getAttribute('clientname', 'JG-General'),
              'autoload' => false,
              'configs' => [
                'forceTrailingSlash' => $config->get('jg_aiint_force_slash', 0),
                'version' => $jg->version,
                'def_lang' => Factory::getLanguage()->getTag(),
                'session' => Session::getFormToken(),
                'base_url' => Uri::base(),
              ]
            ];
    Factory::getApplication()->getDocument()->addScriptOptions('com_joomgallery.aiinterface', $opts);
    JSHelper::registerText('com_joomgallery.aiinterface', 'COM_JOOMGALLERY_JS_AIINT_');

    /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->useScript('com_joomgallery.aiinterface');

    return '';
	}
}
