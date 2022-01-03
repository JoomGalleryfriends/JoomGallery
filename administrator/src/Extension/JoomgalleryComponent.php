<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
//use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
//use Joomla\CMS\Tag\TagServiceTrait;
use Psr\Container\ContainerInterface;
use Joomgallery\Component\Joomgallery\Administrator\Helper\Upload;

/**
 * Component class for Joomgallery
 *
 * @since  4.0.0
 */
class JoomgalleryComponent extends MVCComponent implements BootableExtensionInterface, RouterServiceInterface
{
	use AssociationServiceTrait;
	use RouterServiceTrait;
	use HTMLRegistryAwareTrait;
	// use CategoryServiceTrait, TagServiceTrait
  // {
	// 	CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
	// 	CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
	// }

  /**
	 * Booting the extension. This is the function to set up the environment of the extension like
	 * registering new class loaders, etc.
	 *
	 * If required, some initial set up can be done from services of the container, eg.
	 * registering HTML services.
	 *
	 * @param   ContainerInterface  $container  The container
	 *
	 * @return  void
   *
	 * @since   4.0.0
	 */
  public function boot(ContainerInterface $container)
 	{
    $this->upload = new Upload;
  }
}
