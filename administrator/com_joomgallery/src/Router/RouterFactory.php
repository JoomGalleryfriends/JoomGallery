<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Router;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Menu\AbstractMenu;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Component\Router\RouterInterface;
use \Joomla\CMS\Categories\CategoryFactoryInterface;
use \Joomla\CMS\Application\CMSApplicationInterface;
use \Joomla\CMS\Component\Router\RouterFactoryInterface;
use \Joomla\CMS\Component\Router\RouterFactory as RouterFactoryBase;

/**
 * Default router factory.
 *
 * @since  4.0.0
 */
class RouterFactory extends RouterFactoryBase implements RouterFactoryInterface
{
    /**
     * The namespace to create the categories from.
     *
     * @var    string
     * @since  4.0.0
     */
    private $namespace;

    /**
     * The category factory
     *
     * @var CategoryFactoryInterface
     *
     * @since  4.0.0
     */
    private $categoryFactory;

    /**
     * The db
     *
     * @var DatabaseInterface
     *
     * @since  4.0.0
     */
    private $db;

    /**
     * The namespace must be like:
     * Joomla\Component\Content
     *
     * @param   string                    $namespace        The namespace
     * @param   CategoryFactoryInterface  $categoryFactory  The category object
     * @param   DatabaseInterface         $db               The database object
     *
     * @since   4.0.0
     */
    public function __construct($namespace, ?CategoryFactoryInterface $categoryFactory = null, ?DatabaseInterface $db = null)
    {
        $this->namespace       = $namespace;
        $this->categoryFactory = $categoryFactory;
        $this->db              = $db;
    }

    /**
     * Creates a router.
     *
     * @param   CMSApplicationInterface  $application  The application
     * @param   AbstractMenu             $menu         The menu object to work with
     *
     * @return  RouterInterface
     *
     * @since   4.0.0
     */
    public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
    {
        $routerName = $application->bootComponent('com_joomgallery')->getConfig()->get('jg_router', 'DefaultRouter');
        $className  = 'Joomgallery\\Component\\Joomgallery\\Site\\Service\\' . \ucfirst($routerName);

        if(!class_exists($className))
        {
            throw new \RuntimeException('This router is not available for JoomGallery.');
        }

        return new $className($application, $menu, $this->categoryFactory, $this->db);
    }
}
