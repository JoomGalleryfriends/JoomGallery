<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @subpackage plg_privacyjoomgalleryimages                                           **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/
namespace Joomgallery\Plugin\Privacy\Joomgallery\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Table\RequestTable;

/**
 * Privacy plugin managing Joomla user image data
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
final class JoomgalleryPrivacy extends PrivacyPlugin
{
	/**
	 * Processes an export request for image data
	 *
	 * This event will collect data for the image table
	 *
	 * @param   RequestTable  $request  The request record being processed
	 * @param   User          $user     The user account associated with this request if available
	 *
	 * @return  \Joomla\Component\Privacy\Administrator\Export\Domain[]
	 *
	 * @since   4.0.0
	 */
	public function onPrivacyExportRequest(RequestTable $request, ?User $user = null)
	{
		if (!$user)
		{
			return array();
		}

		$domains   = array();
		$domain    = $this->createDomain('user_image', 'joomla_user_image_data');
		$domains[] = $domain;

		$db = $this->getDatabase();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->quoteName('#__joomgallery'))
			->where($db->quoteName('created_by') . ' = ' . (int) $user->id);

		$items = $db->setQuery($query)->loadObjectList();

		foreach ($items as $item)
		{
			$domain->addItem($this->createItemFromArray((array) $item));
		}

		$domains[] = $this->createCustomFieldsDomain('com_joomgallery.image', $items);

		return $domains;
	}

	/**
	 * Removes the data associated with a remove information request
	 *
	 * This event will pseudoanonymise the image
	 *
	 * @param   RequestTable  $request  The request record being processed
	 * @param   User          $user     The user account associated with this request if available
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onPrivacyRemoveData(RequestTable $request, ?User $user = null)
	{
		// This plugin only processes data for registered user accounts
		if (!$user)
		{
			return;
		}

		$db = $this->getDatabase();

		$query = $db->getQuery(true);

		$query->clear()
			->delete($db->quoteName('#__joomgallery'))
			->where($db->quoteName('created_by') . ' = ' . (int) $user->id);

		$db->setQuery($query)
			 ->execute();
	}
}
