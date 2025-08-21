<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Uri\Uri;

/**
* Trait implementing methods for URL manipulation.
*
* @since  4.0.0
*/
trait RoutingTrait
{
  /**
   * True, if we are redirecting to the return page
   *
   * @access  protected
   * @var     bool
   */
  protected $useReturnPage = false;

  /**
	 * Get the return URL.	 *
	 * If a "return" variable has been passed in the request
	 * 
	 * @param   string  Optional: A default view to return
	 *
	 * @return  string  The return URL.
	 *
	 * @since   4.0.0
	 */
	protected function getReturnPage(string $default=''): string
	{
		$return = $this->input->get('return', null, 'base64');

		if(empty($return) || !Uri::isInternal(base64_decode($return)))
		{
			if(!empty($default))
			{
				return 'index.php?option='._JOOM_OPTION.'&view='.$default;
			}
			else
			{
				return 'index.php?option='._JOOM_OPTION.'&view='.$this->default_view;
			}
		}
		else
		{
      $this->useReturnPage = true;

			return \base64_decode($return);
		}
	}

	/**
	 * Gets the URL arguments to append to an item redirect.
	 *
	 * @param   integer  $recordId  The primary key id for the item.
	 * @param   integer  $parentId  The primary key id of the parent item.
	 * @param   string   $urlVar    The name of the URL variable for the id.
	 *
	 * @return  string  The arguments to append to the redirect URL.
	 *
	 * @since   4.0.0
	 */
	protected function getItemAppend($recordId = null, $parentId = null, $urlVar = 'id')
	{
		$append = '';

		// Setup redirect info.
		if($tmpl = $this->input->get('tmpl', '', 'string'))
		{
			$append .= '&tmpl=' . $tmpl;
		}

		$layout = $this->input->get('layout', '', 'string');
		if($layout && $layout != 'default')
		{
			$append .= '&layout=' . $layout;
		}

		$forcedLanguage = $this->input->get('forcedLanguage', '', 'cmd');
		if($forcedLanguage && $forcedLanguage != '*')
		{
			$append .= '&forcedLanguage=' . $forcedLanguage;
		}

		if(!\is_null($recordId) && !$this->useReturnPage)
		{
			$append .= '&' . $urlVar . '=' . $recordId;
		}

		if(!\is_null($parentId) && !$this->useReturnPage)
		{
			$append .= '&parentId=' . $parentId;
		}

		$return = $this->input->get('return', null, 'base64');
		if($return && !$this->useReturnPage)
		{
			$append .= '&return=' . $return;
		}

		return $append;
	}

  /**
	 * Remove return from input.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  protected function removeReturn()
  {
    $this->input->set('return', null);

    return;
  }
}
