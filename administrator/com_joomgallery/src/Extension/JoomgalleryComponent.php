<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Filesystem\Path;
use \Joomla\CMS\Menu\AbstractMenu;
use \Psr\Container\ContainerInterface;
use \Joomla\CMS\Extension\MVCComponent;
use \Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use \Joomla\CMS\Fields\FieldsServiceInterface;
use \Joomla\CMS\Component\Router\RouterInterface;
use \Joomla\CMS\Application\CMSApplicationInterface;
use \Joomla\CMS\Association\AssociationServiceInterface;
use \Joomla\CMS\Association\AssociationServiceTrait;
use \Joomla\CMS\Component\Router\RouterServiceInterface;
use \Joomla\CMS\Component\Router\RouterServiceTrait;
use \Joomla\CMS\Extension\BootableExtensionInterface;
use \Joomgallery\Component\Joomgallery\Site\Service\JG3Router;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManagerServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerServiceTraitInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Metadata\MetadataServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Metadata\MetadataServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\RefresherServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\RefresherServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\TusServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\TusServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationServiceInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationServiceTrait;

/**
 * Component class for Joomgallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomgalleryComponent extends MVCComponent implements BootableExtensionInterface, RouterServiceInterface, FieldsServiceInterface
{
  use MessageTrait;
	use AssociationServiceTrait;
	use HTMLRegistryAwareTrait;
  // use RouterServiceTrait;
  use RouterServiceTrait {RouterServiceTrait::createRouter as traitCreateRouter;}

  /**
   * JoomGallery services
   *
   * How to:
   * --------
   * $component = $app->bootComponent(_JOOM_OPTION); // boot JoomGallery component
   * $component->create<SERVICENAME>('<VARIANT>');   // instantiate new class with specified variant of the service
   * $component->get<SERVICENAME>()-><METHOD>();     // execute method of service class
   *
   */
  use AccessServiceTrait;
  use ConfigServiceTrait;
  use FileManagerServiceTrait;
  use FilesystemServiceTrait;
  use IMGtoolsServiceTrait;
  use MessengerServiceTrait;
  use MetadataServiceTrait;
  use RefresherServiceTrait;
  use TusServiceTrait;
  use UploaderServiceTrait;
  use MigrationServiceTrait;

  /**
   * Storage for the component cache object
   *
   * @var MVCStorage
   */
  public $cache = false;

  /**
   * Storage for the xml of the current component
   *
   * @var \SimpleXMLElement
   */
  public $xml = null;

  /**
   * Storage for the current component version
   *
   * @var string
   */
  public $version = '';

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
    // JoomGallery definitions
    if(!\defined('_JOOM_OPTION'))
    {
      require_once JPATH_ADMINISTRATOR . '/components/com_joomgallery/includes/defines.php';
    }

    // Initialize JoomGallery cache
    if(!$this->cache)
    {
      $this->cache = new JoomCache();
    }

    // Load component manifest xml
    if(!$this->xml)
    {
      $this->xml  = \simplexml_load_file(Path::clean(JPATH_ADMINISTRATOR . '/components/com_joomgallery/joomgallery.xml'));
    }

    // Read out component version
    if(!$this->version)
    {
      $this->version = (string) $this->xml->version;
    }
  }

  /**
   * Returns the router.
   *
   * @param   CMSApplicationInterface  $application  The application object
   * @param   AbstractMenu             $menu         The menu object to work with
   *
   * @return  RouterInterface
   *
   * @since  4.0.0
   */
  public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
  {
    // Get router config value
    $this->createConfig();
    $routerName = $this->getConfig()->get('jg_router', 'DefaultRouter');

    // Get router
    $router = 'Joomgallery\\Component\\Joomgallery\\Site\\Service\\' . \ucfirst($routerName);
    
    if($router::$type == 'modern')
    {
      // Use a modern router (RouterView)
      return $this->traitCreateRouter($application, $menu);
    }
    else
    {
      // Use a legacy router
      return new $router($application, $menu);
    }
  }

  /**
   * Returns a valid section for the given section. If it is not valid then null is returned.
   *
   * @param   string       $section  The section to get the mapping for
   * @param   object|null  $item     The content item or null
   *
   * @return  string|null  The new section or null
   *
   * @since   4.0.0
   */
  public function validateSection($section, $item = null)
  {
    if(Factory::getApplication()->isClient('site'))
    {
      switch($section)
      {
        case 'image':
        case 'imageform':
            return 'image';

        case 'category':
        case 'categoryform':
          return 'category';
      }
    }

    if($section === 'image' || $section === 'category')
    {
      return $section;
    }

    // We don't know other sections.
    return null;
  }

  /**
   * Returns valid contexts.
   *
   * @return  array  Associative array with contexts as keys and translated strings as values
   *
   * @since   4.0.0
   */
  public function getContexts(): array
  {
    $language = Factory::getApplication()->getLanguage();
    $language->load('com_joomgallery', JPATH_ADMINISTRATOR);

    return [ 'com_joomgallery.image' => $language->_('COM_JOOMGALLERY_IMAGES'),
             'com_joomgallery.category' => $language->_('JCATEGORIES'),
           ];
  }
}
