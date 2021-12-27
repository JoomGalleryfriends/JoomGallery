<?php
/**
 * @version    CVS: 4.0.0
 * @package    Com_Joomgallery
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @copyright  2008 - 2022  JoomGallery::ProjectTeam
 * @license    GNU General Public License version 2 or later
 */
namespace Joomgallery\Component\Joomgallery\Api\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\ApiController;

/**
 * The Images controller
 *
 * @since  4.0.0
 */
class ImagesController extends ApiController 
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = 'images';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $default_view = 'images';
}