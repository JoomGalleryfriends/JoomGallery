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

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Date\Date;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\FormField;
use \Joomla\CMS\Language\Text;

/**
 * Supports an HTML select list of categories
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class TimecreatedField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $type = 'timecreated';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string    The field input markup.
	 *
	 * @since   4.0.0
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = [];

		$time_created = $this->value;

		if(!strtotime($time_created))
		{
			$time_created = Factory::getDate('now', Factory::getApplication()->getConfig()->get('offset'))->toSql(true);
			$html[]       = '<input type="hidden" name="' . $this->name . '" value="' . $time_created . '" />';
		}

		$hidden = (boolean) $this->element['hidden'];

		if($hidden == null || !$hidden)
		{
			$jdate       = new Date($time_created);
			$pretty_date = $jdate->format(Text::_('DATE_FORMAT_LC2'));
			$html[]      = '<div>' . $pretty_date . '</div>';
		}

		return implode($html);
	}
}
