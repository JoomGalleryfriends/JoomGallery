<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\Registry\Registry;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomla\CMS\Application\CMSApplicationInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;

/**
 * Model to get a list of category records.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class UseruploadModel extends JoomAdminModel
{
  /**
   * Joomla application class
   *
   * @access  protected
   * @var     CMSApplicationInterface
   * @since   4.2.0
   */
  protected $app;

  /**
   * JoomGallery extension class
   *
   * @access  protected
   * @var     JoomgalleryComponent
   * @since   4.2.0
   */
  protected $component;


  /**
   * Item type
   *
   * @access  protected
   * @var     string
   * @since   4.2.0
   */
  public $typeAlias = 'com_joomgallery.userupload';

  /**
   * Constructor
   *
   * @param   array                 $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
   * @param   MVCFactoryInterface   $factory  The factory.
   *
   * @throws  \Exception
   * @since   4.2.0
   */
  public function __construct($config = [], $factory = null)
  {
    parent::__construct($config, $factory);

    $this->app       = Factory::getApplication();
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
  }


  /**
   * Method to autopopulate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @return  void
   *
   * @throws  \Exception
   *
   * @since   4.2.0
   */
  protected function populateState():void
  {
    // List state information.
    parent::populateState();

    $this->loadComponentParams();
  }

  /**
   * Method to get the record form.
   *
   * @param   array     $data      An optional array of data for the form to interogate.
   * @param   bool   $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  Form|bool  A \JForm object on success, false on failure
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function getForm($data = array(), $loadData = true): Form|bool
  {
    // Get the form.
    $form = $this->loadForm($this->typeAlias, 'userupload',
      array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form))
    {
      return false;
    }

    return $form;
  }

  /**
   * Method to load component specific parameters into model state.
   *
   * @param   int   $id  ID of the content if needed (default: 0)
   *
   * @return  void
   * @throws \Exception
   * @since   4.2.0
   */
  protected function loadComponentParams(int $id = 0): void
  {
    // Load the parameters.
    $params       = Factory::getApplication()->getParams();
    $params_array = $params->toArray();

    if(isset($params_array['item_id']))
    {
      $this->setState($this->type.'.id', $params_array['item_id']);
    }

    $this->setState('parameters.component', $params);

    // Load the configs from config service
    $id = ($id === 0) ? null : $id;

    // $this->component->createConfig(_JOOM_OPTION.'.'.$this->type, $id, true);
    $this->component->createConfig(_JOOM_OPTION, $id, true);
    $configArray = $this->component->getConfig()->getProperties();
    $configs     = new Registry($configArray);

    $this->setState('parameters.configs', $configs);
  }

  /**
   * Method to get parameters from model state.
   *
   * @return  array   List of parameters
   * @since   4.2.0
   */
  public function getParams(): array
  {
    $params = array('component' => $this->getState('parameters.component'),
                    'menu'      => $this->getState('parameters.menu'),
                    'configs'   => $this->getState('parameters.configs')
    );

    return $params;
  }

  /**
   * Method to override a parameter in the model state
   *
   * @param   string   $property  The parameter name.
   * @param   string   $value     The parameter value.
   * @param   string   $type      The parameter type. Optional. Default='configs'
   *
   * @return  void
   * @since   4.2.0
   */
  public function setParam(string $property, string $value, string $type = 'configs'): void
  {
    // Get params
    $params = $this->getState('parameters.'.$type);

    // Set new value
    $params->set($property, $value);

    // Set params to state
    $this->setState('parameters.'.$type, $params);
  }

  /**
   * Method to get the access service class.
   *
   * @return  AccessInterface   Object on success, false on failure.
   * @since   4.2.0
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
   * Method to check if user owns at least one category. Without
   * only a matching request message will be displayed
   *
   * @param   \Joomla\CMS\User\User   $user  ToDO: Id would suffice
   *
   * @return  bool true when user owns at least one category
   *
   * @throws  \Exception
   *
   * @since   4.2.0
   */
  public function getUserHasACategory(\Joomla\CMS\User\User $user): bool
  {
    $isUserHasACategory = true;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);    // ToDo: Count categories of user

      // Check number of records in tables
      $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('created_by').' = '.(int) $user->id);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(empty ($count))
      {
        $isUserHasACategory = false;
      }

    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('getUserHasACategory-Error: '.$e->getMessage(), 'error');

      return false;
    }

    return $isUserHasACategory;
  }

}
