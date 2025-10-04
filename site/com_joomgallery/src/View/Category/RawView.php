<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Category;

// No direct access
defined('_JEXEC') or die;

use \Joomla\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\View\Category\RawView as AdminRawView;

/**
 * Raw view class for a single Category-Image.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class RawView extends AdminRawView
{
  /**
	 * Display the category image
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
    parent::display($tpl);

    // Check published state
		if($this->item->id && $this->item->published !== 1) 
		{
			$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_UNAVAILABLE_VIEW'), 'error');
			return;
		}

    // Check access view level
		if(!\in_array($this->item->access, $this->user->getAuthorisedViewLevels()))
    {
      $this->output(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'));
      return;
    }
  }
}
