<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Access;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomla\CMS\Access\Access as AccessBase;
use \Joomgallery\Component\Joomgallery\Administrator\User\User;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\Base\AccessOwn;

/**
 * Access Class
 *
 * Provides methods to handle access, permission and visibility rules of the gallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Access implements AccessInterface
{
  use ServiceTrait;

  /**
   * The option which component to check the ACL.
   *
   * @var string
   */
  protected $option = 'com_joomgallery';

  /**
   * Available content types of current component (this->option)
   *
   * @var array
   */
  protected $types = array('category', 'collection', 'comment', 'config', 'image', 'imagetype', 'tag', 'user', 'vote');

  /**
   * List of parent content types
   *
   * @var array
   */
  protected $parents = array('image' => 'category', 'category' => 'category');

  /**
   * List of content types with appended media (categorised, containing upload rules)
   *
   * @var array
   */
  protected $media_types = array('image');

  /**
   * List of content types which do not have their own assets but uses assets
   * of its parent content types.
   *
   * @var array
   */
  protected $parent_dependent_types = array('image');

  /**
   * List of content types which have only one global asset
   *
   * @var array
   */
  protected $asset_global_types = array('tag', 'comment', 'imagetype', 'vote');

  /**
   * Component specific prefix for its rules.
   *
   * @var string
   */
  protected $prefix = 'joom';

  /**
   * List of all the base acl rules mapped with actions.
   *
   * @var array
   */
  protected $aclMap = array();

  /**
   * The user for which to check access
   *
   * @var  \Joomgallery\Component\Joomgallery\Administrator\User\User
   */
  protected $user;

  /**
   * Storage containing all applied acl checks.
   *
   * @var array
   */
  public $allowed = array('default' => null, 'own' => null, 'upload' => null, 'upload-own' => null);

  /**
   * Containing all acl checks with a mark if we are going to check for that
   *
   * @var array
   */
  public $tocheck = array('default' => true, 'own' => false, 'upload' => false, 'upload-own' => false);

  /**
   * Initialize class for specific option
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct(string $option='')
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Set option
    if($option)
    {
      $this->option = $option;
    }

    // Set current user
    $this->user = $this->component->getMVCFactory()->getIdentity();

    // Set acl map for components with advanced rules
    $mapPath = _JOOM_PATH_ADMIN.'/includes/rules.php';
    if(\file_exists($mapPath))
    {
      require $mapPath;
      $this->aclMap = $rules_map_array;
    }

    // Fill AccessOwn properties
    AccessOwn::$parent_dependent_types = $this->parent_dependent_types;
    AccessOwn::$global_types           = $this->asset_global_types;
  }

  /**
   * Check the ACL permission for an asset on which to perform an action.
   *
   * @param   string   $action     The name of the action to check for permission.
   * @param   string   $asset      The name of the asset on which to perform the action.
   * @param   integer  $pk         The primary key of the item.
   * @param   integer  $parent_pk  The primary key of the parent item.
   * @param   bool     $use_parent True to show that the given primary key is its parent key.
   *
   * @return  bool     True if user has the permission, false if denied
   *
   * @since   4.0.0
   */
  public function checkACL(string $action, string $asset='', int $pk=0, int $parent_pk=0, bool $use_parent=false): bool
  {
    // Prepare action
    if(!empty($this->aclMap))
    {
      $action = $this->prepareAction($action);
    }

    // Prepare asset & pk's
    list($asset, $asset_array, $asset_type, $parent_pk) = $this->prepareAsset($asset, $pk, $parent_pk, $use_parent);
    $asset_length = \count($asset_array);

    if($asset_length >= 3 && $pk == 0)
    {
      $pk = \intval($asset_array[2]);
    }

    if(!empty($this->aclMap))
    {
      // Check if asset is available for this action
      if( ($asset_length == 1 && !\in_array('.', $this->aclMap[$action]['assets'])) ||
          (!\in_array('.'.$asset_type, $this->aclMap[$action]['assets']))
        )
      {
        // Action not available for this asset.
        $this->component->addLog('Action not available for this asset. Access can not be checked. Please provide reasonable inputs.', 'error', 'jerror');
        throw new \Exception("Action not available for this asset. Access can not be checked. Please provide reasonable inputs.", 1);
      }

      // Get the acl rule for this action
      $acl_rule = $this->aclMap[$action]['rule'];
    }
    else
    {
      $acl_rule = $action;
    }

    // Check that use_parent flag is set to yes if adding into a nested asset
    if($action == 'add' && \in_array($asset_type, \array_keys($this->parents)) && !$use_parent)
    {
      // Flag parent_pk has to be set to yes
      $this->component->addLog("Error in your input command: parent_pk (4th argument) has to be set to check permission for the action 'add' on an item within a nested group of assets. Please set parent_pk to 'true' and make sure that the specified primary key corresponds to the category you want to add to.", 'error', 'jerror');
      throw new \Exception("Error in your input command: parent_pk (4th argument) has to be set to check permission for the action 'add' on an item within a nested group of assets. Please set parent_pk to 'true' and make sure that the specified primary key corresponds to the category you want to add to.", 1);
    }

    // Apply the acl check
    //---------------------

    // Reset allowed array
    foreach($this->allowed as $key => $value)
    {
      $this->allowed[$key] = null;
    }

    // Adjust asset for further checks when only parent given
    if($action == 'add' && $use_parent)
    {
      if(\in_array($asset_type, $this->media_types) && $action == 'add')
      {
        // Special acl rule for media upload
        $acl_rule       = $this->prefix.'.upload';
      }

      // Get asset for parent checks
      if(!\in_array($asset_type, $this->parent_dependent_types))
      {
        $parent_type  = $asset_type ? $this->parents[$asset_type] : 'category';
        $asset        = $asset_array[0].'.'.$parent_type.'.'.$parent_pk;
        $asset_length = \count(\explode('.', $asset));
      }
    }

    // More preparations
    $acl_rule_array = \explode('.', $acl_rule);
    $appuser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->user->id);

    // Special case: super user
    if($appuser->get('isRoot') === true)
    {
      // If it is the super user
      return true;
    }

    // 1. Default permission checks based on asset table
    // (Global Configuration -> Recursive assets)
    // (Recursive assets for image: global -> component -> grand-parent -> parent -> type)
    $this->allowed['default'] = $appuser->authorise($acl_rule, $asset);

    // 2. Permission checks based on asset table and ownership
    // Adjust acl rule for the own check
    if($acl_rule_array[1] === 'edit')
    {
      $acl_rule = 'core.'.$acl_rule_array[1].'.'.$this->aclMap[$action]['own'];
    }
    else
    {
      $acl_rule = $this->prefix.'.'.$acl_rule_array[1].'.'.$this->aclMap[$action]['own'];
    }

    if($asset_length >= 3)
    {
      // We are checking for a specific item, based on pk or parent pk
      if(!empty($this->aclMap) && $this->aclMap[$action]['own'] !== false && \in_array('.'.$asset_type, $this->aclMap[$action]['own-assets']) && ($pk > 0 || $use_parent))
      {
        $this->tocheck['own'] = true;

        // Switch pk based on use_parent variable
        $own_pk = $pk;
        // if($use_parent)
        // {
        //   $own_pk = $parent_pk;
        // }

        // Only do the check, if it the pk is known
        $this->allowed['own'] = AccessOwn::checkOwn($this->user->id, $acl_rule, $asset, true, $own_pk);
      }

      // 3. Permission check if adding assets with media items (uploads)
      if(\in_array($asset_type, $this->media_types) && $action == 'add')
      {
        // Get parent/category info
        $parent_id     = $use_parent ? $parent_pk : JoomHelper::getParent($asset_array[1], $pk);
        $parent_type   = $asset_type ? $this->parents[$asset_type] : 'category';
        $parent_asset  = $this->option.'.'.$parent_type.'.'.$parent_id;
        $parent_action = $this->prefix.'.upload';

        // Check for the category in general
        $this->tocheck['upload']     = true;
        $this->allowed['upload']     = AccessBase::check($this->user->id, $parent_action, $parent_asset);

        // Check also against parent ownership
        $this->tocheck['upload-own'] = true;
        $this->allowed['upload-own'] = AccessOwn::checkOwn($this->user->id, $parent_action.'.'.$this->aclMap[$action]['own'], $parent_asset, true, $parent_pk);
      }
    }
    else
    {
      // We are checking for the own asset in general
      if(!empty($this->aclMap) && $this->aclMap[$action]['own'] !== false && \in_array('.'.$asset_type, $this->aclMap[$action]['own-assets']))
      {
        $this->tocheck['own'] = true;
        $this->allowed['own'] = AccessBase::check($this->user->id, $acl_rule, $asset);
      }
    }

    // Apply the results
    //--------

    // Basic: Apply the core result
    $allowedRes = $this->allowed['default'];

    // Advanced: Apply owner result
    if($this->tocheck['own'] === true)
    {
      $allowedRes = $this->allowed['default'] || $this->allowed['own'];
    }

    // Advanced: Apply media items result
    if($this->tocheck['upload'] === true)
    {
      if($this->allowed['upload'] !== null)
      {
        // Override the result from core
        $allowedRes = $this->allowed['upload'];
      }

      if($this->tocheck['upload-own'] === true)
      {
        // Action requires an owner check
        $allowedRes = $allowedRes || $this->allowed['upload-own'];
      }
    }

    return $allowedRes;
  }

  /**
   * Check the permission to view an item based on the users allowed view levels
   *
   * @param   mixed   $level   The view level of which the access is allowed for this item
   *
   * @return  bool    True if user has the permission, false if denied
   *
   * @since   4.0.0
   */
  public function checkViewLevel($level): bool
  {
    if(!\is_int($level))
    {
      if(\is_string($level) && \is_numeric($level))
      {
        $level = (int) $level;
      }
      elseif(\is_string($level))
      {
        foreach(AccessOwn::getViewLevels() as $viewLevel)
        {
          if($viewLevel['title'] === $level)
          {
            $level = $viewLevel['id'];
            break;
          }
        }
      }

      if(!\is_int($level))
      {
        $this->component->addLog('Invalid access view level provided for access view level check', 'error', 'jerror');
        throw new \Exception('Invalid access view level provided for access view level check', 1);
      }
    }

    return \in_array($level, $this->user->getAuthorisedViewLevels());
  }

  /**
   * Change the component related properties of the class.
   * Needed if you want to use this service for another component.
   *
   * @param   string   $option    The new option.
   * @param   array    $types     The new list of available content types.
   * @param   array    $aclMap    The new mapping of acl actions with rules.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function changeOption(string $option, array $types, array $aclMap)
  {
    $this->option = $option;
    $this->types  = $types;
    $this->aclMap = $aclMap;
  }

  /**
   * Set the user for which to check the access.
   *
   * @param   int|User   $user    The user id or a user object.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setUser($user)
  {
    if($user instanceof User)
    {
      // user object given
      $this->user = $user;
    }
    elseif(!\is_object($user))
    {
      if(\is_numeric($user))
      {
        // user id given
        $appuser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user);
      }
      elseif(\is_string($user))
      {
        // username given
        $appuser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($user);
      }

      if(isset($appuser->id))
      {
        $this->user = new User($appuser->id);
      }
    }
  }

  /**
   * Prepare the entered asset to make it conform with $user->authorize method.
   *
   * @param   string   $asset      The given asset.
   * @param   int      $pk         Primary key of the asset.
   * @param   bool     $parent_pk  True if given pk is key of parent asset.
   *
   * @return  array    The prepared asset list.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function prepareAsset(string $asset, int $pk=0, int $parent_pk=0, bool $use_parent=false): array
  {
    // Do we have a global asset?
    $global = false;
    if(!$asset || $asset === \str_replace('com_', '', $this->option))
    {
      $asset  = $this->option;
      $global = true;
    }

    // Option in asset partially given?
    if(\strpos($asset, \str_replace('com_', '', $this->option)) === 0)
    {
      $asset = 'com_' . $asset;
    }

    // First entry has to be the option
    if(\strpos($asset, $this->option) !== 0)
    {
      $asset = $this->option . '.' . $asset;
    }

    // Get type from asset
    $asset_array  = \explode('.', $asset);
    if(\count($asset_array) > 1)
    {
      $asset_type = $asset_array[1];
    }
    else
    {
      $asset_type = false;
    }

    // Get parent pk if needed but not provided
    if($use_parent && !$parent_pk && $pk)
    {
      $parent_pk = JoomHelper::getParent($asset_array[1], $pk);
    }

    // Check for parent_pk to be given
    if($asset_type && \count($asset_array) > 1 && $use_parent && \in_array($asset_type, $this->parent_dependent_types) && !$parent_pk)
    {
      throw new \Exception('For parent-dependent content types, the parent_pk must be given!', 1);
    }

    // Last position has to be the primary key
    if(!$global && $use_parent && $parent_pk > 0 && \in_array($asset_type, $this->parent_dependent_types))
    {
      // We have an asset which is permissioned by its parent itemtype
      if(\count($asset_array) > 2)
      {
        // parent_pk already given, exchange it
        $asset = $asset_array[0] . '.' . $asset_array[1] .'.' . \strval($parent_pk);
      }
      else
      {
        $asset = $asset . '.' . \strval($parent_pk);
      }
    }
    elseif(!$global && !$use_parent && \in_array($asset_type, $this->asset_global_types))
    {
      // We have a global only asset
      $asset = $asset_array[0] . '.' . $asset_array[1] .'.1';
    }
    elseif(!$global && !$use_parent && $pk > 0 && \substr($asset, -\strlen($pk)) !== $pk)
    {
      // We have a standard asset
      $asset = $asset . '.' . \strval($pk);
    }

    // Update type from asset
    $asset_array  = \explode('.', $asset);
    if(\count($asset_array) > 1)
    {
      $asset_type = $asset_array[1];
    }
    else
    {
      $asset_type = false;
    }

    // Check asset
    if($asset_array[0] != $this->option || (\count($asset_array) > 1 && !\in_array($asset_array[1], $this->types)))
    {
      $this->component->addLog('Invalid asset provided for ACL access check', 'error', 'jerror');
      throw new \Exception('Invalid asset provided for ACL access check', 1);
    }

    return [$asset, $asset_array, $asset_type, $parent_pk];
  }

  /**
   * Prepare the entered action to catch similar words.
   *
   * @param   string   $action     The given aaction.
   *
   * @return  string   The prepared action.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function prepareAction(string $action): string
  {
    // Clean action if it is dot separated (core.delete)
    $act_array = \explode('.', $action, 2);
    if(\count($act_array) >= 2)
    {
      $action = $act_array[1];
    }

    // Take away own and inown in action statement
    $action = \str_replace(array('.own', '.inown'), '', $action);

    // Synonyms for add
    $addSyn    = array('add', 'create', 'new', 'upload');
    // Synonyms for delete
    $delSyn    = array('delete', 'remove', 'drop', 'clear', 'erase');
    // Synonyms for edit
    $editSyn   = array('edit', 'change', 'modify', 'alter');
    // Synonyms for editstate
    $stateSyn  = array('editstate', 'edit.state', 'feature', 'unfeature', 'publish', 'unpublish', 'approve', 'unapprove');
    // Synonyms for admin
    $adminSyn  = array('admin', 'acl');
    // Synonyms for manage
    $manageSyn = array('manage', 'options');

    // Compose array
    $composition = array($addSyn, $delSyn, $editSyn, $editSyn, $stateSyn, $adminSyn, $manageSyn);

    // Get the correct action from composition array
    if(!$res = $this->arrayRecursiveSearch($action, $composition))
    {
      $this->component->addLog('Invalid asset provided for ACL access check', 'error', 'jerror');
      throw new \Exception('Invalid action provided for ACL access check', 1);
    }

    return $res;
  }

  /**
   * Search for a value in a nested array and return first value of
   * current array level.
   *
   * @param   string   $needle    The searched value.
   * @param   array    $array     The array.
   *
   * @return  string   First value in the array where needle was found.
   *
   * @since   4.0.0
   */
  protected function arrayRecursiveSearch(string $needle, array $array): string
  {
    foreach($array as $key => $value)
    {
      if($needle === $value)
      {
        // value found in this level
        return $array[0];
      }
      elseif(is_array($value))
      {
        // perform recursive search
        $callback = $this->arrayRecursiveSearch($needle, $value);

        if($callback)
        {
          return $callback;
        }
      }
    }

    return false;
  }
}
