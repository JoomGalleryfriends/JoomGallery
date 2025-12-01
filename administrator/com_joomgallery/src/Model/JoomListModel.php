<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\User\CurrentUserInterface;
use Joomla\Registry\Registry;

/**
 * Base model class for JoomGallery list of items
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class JoomListModel extends ListModel
{
    /**
     * Joomla application class
     *
     * @access  protected
     * @var     Joomla\CMS\Application\AdministratorApplication
     */
    protected $app;

    /**
     * Joomla user object
     *
     * @access  protected
     * @var     Joomla\CMS\User\User
     */
    protected $user;

    /**
     * JoomGallery extension class
     *
     * @access  protected
     * @var     Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
     */
    protected $component;

    /**
     * JoomGallery access service
     *
     * @access  protected
     * @var     Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface
     */
    protected $acl = null;

    /**
     * Item type
     *
     * @access  protected
     * @var     string
     */
    protected $type = 'image';

    /**
     * An internal cache for the last count query used.
     *
     * @access  protected
     * @var    QueryInterface|string
     */
    protected $countQuery = [];

    /**
     * The cache ID used when last populating $this->countQuery
     *
     * @access  protected
     * @var   null|string
     */
    protected $lastCountQueryStoreId = null;

    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @return  void
     * @since   4.0.0
     */
    function __construct($config = [])
    {
        parent::__construct($config);

        $this->app       = Factory::getApplication('administrator');
        $this->component = $this->app->bootComponent(_JOOM_OPTION);
        $this->user      = $this->component->getMVCFactory()->getIdentity();
    }

        /**
         * Method to get parameters from model state.
         *
         * @return  Registry[]   List of parameters
         * @since   4.0.0
         */
        public function getParams(): array
        {
                $params = [
                  'component' => $this->getState('parameters.component'),
                  'menu'      => $this->getState('parameters.menu'),
                  'configs'   => $this->getState('parameters.configs'),
                ];

                return $params;
        }

        /**
         * Method to get the access service class.
         *
         * @return  AccessInterface   Object on success, false on failure.
         * @since   4.0.0
         */
        public function getAcl(): AccessInterface
        {
        // Create access service
        if(\is_null($this->acl))
        {
            $this->component->createAccess();
            $this->acl = $this->component->getAccess();
        }

                return $this->acl;
        }

    /**
     * Method to load component specific parameters into model state.
     *
     * @return  void
     * @since   4.0.0
     */
    protected function loadComponentParams()
    {
        // Load the componen parameters.
                $params       = Factory::getApplication('com_joomgallery')->getParams();
                $params_array = $params->toArray();

                if(isset($params_array['item_id']))
                {
                        $this->setState($this->type . '.id', $params_array['item_id']);
                }

                $this->setState('parameters.component', $params);

                // Load the configs from config service
                $this->component->createConfig('com_joomgallery');
                $configArray = $this->component->getConfig()->getProperties();
                $configs     = new Registry($configArray);

                $this->setState('parameters.configs', $configs);
    }

    /**
     * Method to get the total number of items for the data set.
     *
     * @return  integer  The total number of items available in the data set.
     *
     * @since   4.1.0
     */
    public function getTotal()
    {
        // Get a storage key.
        $store = $this->getStoreId('getTotal');

        // Try to load the data from internal storage.
        if(isset($this->cache[$store]))
        {
            return $this->cache[$store];
        }

        // For record types including a group, merge, querySet or having statement in the list query
        // Add a _getCountListQuery method without them to speed up the record counting
        $getListQuery = '_getListQuery';

        if(method_exists($this, 'getCountListQuery'))
        {
            $getListQuery = '_getCountListQuery';
        }

        try
        {
            // Load the total and add the total to the internal cache.
            $this->cache[$store] = (int) $this->_getListCount($this->{$getListQuery}());
        }
        catch(\RuntimeException $e)
        {
            $this->setError($e->getMessage());

            return false;
        }

        return $this->cache[$store];
    }

    /**
     * Method to get all available item ids.
     *
     * @return  mixed  Array of item ids on success, false on failure.
     *
     * @since   4.2.0
     */
    public function getIDs()
    {
        // Get a storage key.
        $store = $this->getStoreId('getIDs');

        // Try to load the data from internal storage.
        if(isset($this->cache[$store]))
        {
            return $this->cache[$store];
        }

        try
        {
            // Load the list items and add the items to the internal cache.
            $this->cache[$store] = $this->_getList($this->getIDListQuery());
        }
        catch(\RuntimeException $e)
        {
            $this->setError($e->getMessage());

            return false;
        }

        return $this->cache[$store];
    }

    /**
     * Method to cache the last count query constructed.
     *
     * @return  QueryInterface  An object implementing the QueryInterface interface
     *
     * @since   4.1.0
     */
    protected function _getCountListQuery()
    {
        // Compute the current store id.
        $currentStoreId = $this->getStoreId('count');

        // If the last store id is different from the current, refresh the query.
        if($this->lastCountQueryStoreId !== $currentStoreId || empty($this->countQuery))
        {
            $this->lastCountQueryStoreId = $currentStoreId;
            $this->countQuery            = $this->getCountListQuery();
        }

        return $this->countQuery;
    }

    /**
     * Build an SQL query to load the full list of ids.
     *
     * @return  DatabaseQuery
     *
     * @since   4.2.0
     */
    protected function getIDListQuery($ordering = '')
    {
        // Create a new query object.
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        // Get the corresponding table
        $table = $this->getTable($this->type);

        // Select the required fields from the table.
        $query->select($db->quoteName($table->getKeyName()));
        $query->from($db->quoteName($table->getTableName()));

        // Apply ordering
        if(!$ordering) $ordering = $table->getKeyName() . ' ASC';
        $query->order($db->escape($ordering));

        return $query;
    }

    /**
     * Method to load and return a table object.
     *
     * @param   string  $name    The name of the view
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration settings to pass to Table::getInstance
     *
     * @return  Table|boolean  Table object or boolean false if failed
     *
     * @since   4.0.0
     */
    protected function _createTable($name, $prefix = 'Table', $config = [])
    {
        $table = parent::_createTable($name, $prefix, $config);

        if($table instanceof CurrentUserInterface)
        {
            $table->setCurrentUser($this->component->getMVCFactory()->getIdentity());
        }

        return $table;
    }
}
