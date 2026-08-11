<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Access;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Service\Access\Base\AccessOwn;
use Joomgallery\Component\Joomgallery\Administrator\Service\Traits\CacheAwareTrait;
use Joomgallery\Component\Joomgallery\Administrator\User\User;
use Joomla\CMS\Access\Access as AccessBase;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;

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
  use CacheAwareTrait;

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
  protected $types = ['category', 'collection', 'comment', 'config', 'image', 'imagetype', 'tag', 'task', 'user', 'vote'];

  /**
   * List of parent content types
   *
   * @var array
   */
  protected $parents = ['image' => 'category', 'category' => 'category'];

  /**
   * List of content types with appended media (categorised, containing upload rules)
   *
   * @var array
   */
  protected $media_types = ['image'];

  /**
   * List of content types which do not have their own assets but uses assets
   * of its parent content types.
   *
   * @var array
   */
  protected $parent_dependent_types = ['image'];

  /**
   * List of content types which have only one global asset
   *
   * @var array
   */
  protected $asset_global_types = ['tag', 'comment', 'imagetype', 'vote', 'task'];

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
  protected $aclMap = [];

  /**
   * The user for which to check access
   *
   * @var  \Joomgallery\Component\Joomgallery\Administrator\User\User
   */
  protected $user;

  /**
   * The Joomla user matching the user for which access is checked.
   *
   * @var  \Joomla\CMS\User\User
   */
  protected $appUser;

  /**
   * Results of access checks already performed during the current request.
   *
   * @var array
   */
  protected $checks = [];

  /**
   * Namespace of the bounded session hot cache.
   *
   * @var string
   */
  protected $cacheNamespace = '';

  /**
   * Maximum number of ACL results retained in the session.
   *
   * @var int
   */
  protected $hotCacheLimit = 64;

  /**
   * Lifetime of an ACL result in the session hot cache, in seconds.
   *
   * @var int
   */
  protected $hotCacheLifetime = 900;

  /**
   * Storage containing all applied acl checks.
   *
   * @var array
   */
  public $allowed = ['default' => null, 'own' => null, 'upload' => null, 'upload-own' => null];

  /**
   * Containing all acl checks with a mark if we are going to check for that
   *
   * @var array
   */
  public $tocheck = ['default' => true, 'own' => false, 'upload' => false, 'upload-own' => false];

  /**
   * Initialises the access service for a component, resolves the current
   * identity, prepares the cache namespace and warms common permissions.
   *
   * @param   string  $option  Component option for which permissions are checked.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct(string $option = '')
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
    $identity   = $this->app->getIdentity();

    if($identity && (int) $identity->id === (int) $this->user->id)
    {
      $this->appUser = $identity;
    }

    $this->loadCacheConfig();
    $this->refreshCacheNamespace();

    // Set acl map for components with advanced rules
    $mapPath = _JOOM_PATH_ADMIN . '/includes/rules.php';

    if(file_exists($mapPath))
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
  public function checkACL(string $action, string $asset = '', int $pk = 0, int $parent_pk = 0, bool $use_parent = false): bool
  {
    $checkKey = implode(':', [$this->user->id, $action, $asset, $pk, $parent_pk, (int) $use_parent]);

    if(\array_key_exists($checkKey, $this->checks))
    {
      return $this->checks[$checkKey];
    }

    if($this->isHotCacheCandidate($asset, $pk) && $this->hasCacheEntry($this->cacheNamespace, $checkKey))
    {
      $hot = $this->getCacheEntry($this->cacheNamespace, $checkKey);

      if(\is_array($hot) && isset($hot['expires']) && (int) $hot['expires'] >= time())
      {
        return $this->checks[$checkKey] = (bool) $hot['value'];
      }
    }

    // Prepare action
    if(!empty($this->aclMap))
    {
      $action = $this->prepareAction($action);
    }

    // Prepare asset & pk's
    list($asset, $asset_array, $asset_type, $parent_pk) = $this->prepareAsset($asset, $pk, $parent_pk, $use_parent);
    $asset_length                                       = \count($asset_array);

    if($asset_length >= 3 && $pk == 0)
    {
      $pk = \intval($asset_array[2]);
    }

    if(!empty($this->aclMap))
    {
      // Check if asset is available for this action
      if( ($asset_length == 1 && !\in_array('.', $this->aclMap[$action]['assets'])) ||
          (!\in_array('.' . $asset_type, $this->aclMap[$action]['assets']))
        )
      {
        // Action not available for this asset.
        $this->component->addLog('Action not available for this asset. Access can not be checked. Please provide reasonable inputs.', 'error', 'jerror');
        throw new \Exception('Action not available for this asset. Access can not be checked. Please provide reasonable inputs.', 1);
      }

      // Get the acl rule for this action
      $acl_rule = $this->aclMap[$action]['rule'];
    }
    else
    {
      $acl_rule = $action;
    }

    // Check that use_parent flag is set to yes if adding into a nested asset
    if($action == 'add' && \in_array($asset_type, array_keys($this->parents)) && !$use_parent)
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

    $this->tocheck = ['default' => true, 'own' => false, 'upload' => false, 'upload-own' => false];

    // Adjust asset for further checks when only parent given
    if($action == 'add' && $use_parent)
    {
      if(\in_array($asset_type, $this->media_types) && $action == 'add')
      {
        // Special acl rule for media upload
        $acl_rule = $this->prefix . '.upload';
      }

      // Get asset for parent checks
      if(!\in_array($asset_type, $this->parent_dependent_types))
      {
        $parent_type  = $asset_type ? $this->parents[$asset_type] : 'category';
        $asset        = $asset_array[0] . '.' . $parent_type . '.' . $parent_pk;
        $asset_length = \count(explode('.', $asset));
      }
    }

    // More preparations
    $acl_rule_array = explode('.', $acl_rule);

    if(!$this->appUser)
    {
      $this->appUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->user->id);
    }

    $appuser = $this->appUser;

    // Special case: super user
    if($appuser->get('isRoot') === true)
    {
      // If it is the super user
      return $this->cacheCheckResult($checkKey, true, $asset, $pk);
    }

    // 1. Default permission checks based on asset table
    // (Global Configuration -> Recursive assets)
    // (Recursive assets for image: global -> component -> grand-parent -> parent -> type)
    $this->allowed['default'] = $appuser->authorise($acl_rule, $asset);

    // 2. Permission checks based on asset table and ownership
    // Adjust acl rule for the own check
    if($acl_rule_array[1] === 'edit')
    {
      $acl_rule = 'core.' . $acl_rule_array[1] . '.' . $this->aclMap[$action]['own'];
    }
    else
    {
      $acl_rule = $this->prefix . '.' . $acl_rule_array[1] . '.' . $this->aclMap[$action]['own'];
    }

    if($asset_length >= 3)
    {
      // We are checking for a specific item, based on pk or parent pk
      if(!empty($this->aclMap) && $this->aclMap[$action]['own'] !== false && \in_array('.' . $asset_type, $this->aclMap[$action]['own-assets']) && ($pk > 0 || $use_parent))
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
        $parent_asset  = $this->option . '.' . $parent_type . '.' . $parent_id;
        $parent_action = $this->prefix . '.upload';

        // Check for the category in general
        $this->tocheck['upload'] = true;
        $this->allowed['upload'] = AccessBase::check($this->user->id, $parent_action, $parent_asset);

        // Check also against parent ownership
        $this->tocheck['upload-own'] = true;
        $this->allowed['upload-own'] = AccessOwn::checkOwn($this->user->id, $parent_action . '.' . $this->aclMap[$action]['own'], $parent_asset, true, $parent_pk);
      }
    }
    else
    {
      // We are checking for the own asset in general
      if(!empty($this->aclMap) && $this->aclMap[$action]['own'] !== false && \in_array('.' . $asset_type, $this->aclMap[$action]['own-assets']))
      {
        $this->tocheck['own'] = true;
        $this->allowed['own'] = AccessBase::check($this->user->id, $acl_rule, $asset);
      }
    }

    // Apply the results
    //--------

    // Basic: Apply the core result
    $allowedRes = $this->allowed['default'];

    // Advanced: An explicit own result takes precedence over the core result.
    // An inherited own permission (null) falls back to the core result.
    if($this->tocheck['own'] === true && $this->allowed['own'] !== null)
    {
      $allowedRes = $this->allowed['own'];
    }

    // Advanced: Apply media items result
    if($this->tocheck['upload'] === true)
    {
      if($this->allowed['upload'] !== null)
      {
        // Override the result from core
        $allowedRes = $this->allowed['upload'];
      }

      if($this->tocheck['upload-own'] === true && $this->allowed['upload-own'] !== null)
      {
        // An explicit parent-owner result takes precedence over upload access.
        $allowedRes = $this->allowed['upload-own'];
      }
    }

    return $this->cacheCheckResult($checkKey, $allowedRes, $asset, $pk);
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
      if(\is_string($level) && is_numeric($level))
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
   * @param   int|string|User  $user  User ID, username or JoomGallery user object.
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
      $this->user    = $user;
      $this->appUser = null;
    }
    elseif(!\is_object($user))
    {
      if(is_numeric($user))
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
        $this->user    = new User($appuser->id);
        $this->appUser = $appuser;
      }
    }

    $this->checks = [];
    $this->refreshCacheNamespace();
  }

  /**
   * Selects and initialises a session-cache namespace bound to the component,
   * current user ID and the user's authorised-group fingerprint.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function refreshCacheNamespace(): void
  {
    $groups = $this->appUser ? array_map('intval', (array) $this->appUser->getAuthorisedGroups()) : [];
    sort($groups);

    $this->cacheNamespace = 'com_joomgallery.accesscache.' . sha1($this->option) . '.' . (int) $this->user->id . '.' . sha1(implode(',', $groups));
    $this->initialiseCache($this->cacheNamespace, $this->hotCacheLifetime);
  }

  /**
   * Loads the ACL cache entry limit and lifetime from the JoomGallery
   * configuration while retaining safe defaults if loading fails.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function loadCacheConfig(): void
  {
    try
    {
      $this->component->createConfig('com_joomgallery');
      $config = $this->component->getConfig();

      $this->hotCacheLimit    = max(0, (int) $config->get('jg_acl_cache_entries', 64));
      $this->hotCacheLifetime = max(0, (int) $config->get('jg_acl_cache_lifetime', 15)) * 60;
    }
    catch(\Throwable $e)
    {
      $this->hotCacheLimit    = 64;
      $this->hotCacheLifetime = 900;
    }
  }

  /**
   * Precalculates component permissions commonly used by administrator
   * toolbars. In the site application it precalculates the category-management
   * permissions used by the user panel for categories owned by the current
   * user.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function warmHotCache(): void
  {
    if($this->hotCacheLimit === 0 || $this->hotCacheLifetime === 0)
    {
      return;
    }

    if($this->app->isClient('administrator'))
    {
      // Warmup admin application
      foreach(['admin', 'manage', 'add', 'edit', 'editstate', 'delete'] as $action)
      {
        try
        {
          $this->checkACL($action, $this->option);
        }
        catch(\Throwable $e)
        {
          // A custom access map may not expose every standard action.
        }
      }

      return;
    }

    if(!$this->app->isClient('site') || (int) $this->user->id === 0)
    {
      // The public user and applications not site and not admin gets no warmup
      return;
    }

    // Warmup frontend (site) application
    $warmKey = '__owned_categories_warmed__';
    $warm    = $this->getCacheEntry($this->cacheNamespace, $warmKey);

    if(\is_array($warm) && isset($warm['expires'], $warm['count']) && (int) $warm['expires'] >= time())
    {
      return;
    }

    $categoryLimit = (int) floor(($this->hotCacheLimit - 1) / 5);

    if($categoryLimit < 1)
    {
      return;
    }

    $db     = Factory::getContainer()->get(DatabaseInterface::class);
    $userId = (int) $this->user->id;
    $query  = $db->getQuery(true)
      ->select($db->quoteName(['id', 'created_by']))
      ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
      ->where($db->quoteName('created_by') . ' = :userId')
      ->order($db->quoteName('modified_time') . ' DESC')
      ->bind(':userId', $userId);

    $categories = $db->setQuery($query, 0, $categoryLimit)->loadObjectList();

    foreach($categories as $category)
    {
      $categoryId = (int) $category->id;
      JoomHelper::registerCreator('category', $categoryId, (int) $category->created_by);

      foreach(['edit', 'delete', 'editstate'] as $action)
      {
        $this->checkACL($action, $this->option . '.category', $categoryId);
      }

      // The category is used as parent for a new child category or image.
      $this->checkACL('add', $this->option . '.category', 0, $categoryId, true);
      $this->checkACL('add', $this->option . '.image', 0, $categoryId, true);
    }

    $this->putCacheEntry(
        $this->cacheNamespace,
        $warmKey,
        ['count' => \count($categories), 'expires' => time() + $this->hotCacheLifetime],
        $this->hotCacheLimit
    );
  }

  /**
   * Stores an ACL result in the request cache and, when the asset qualifies,
   * in the bounded and short-lived session hot cache.
   *
   * @param   string  $key     Unique key representing the complete ACL check.
   * @param   bool    $result  Permission result to cache.
   * @param   string  $asset   Asset name used by the permission check.
   * @param   int     $pk      Primary key of the checked item.
   *
   * @return  bool  The cached permission result.
   *
   * @since   4.4.0
   */
  protected function cacheCheckResult(string $key, bool $result, string $asset, int $pk): bool
  {
    $this->checks[$key] = $result;

    if($this->isHotCacheCandidate($asset, $pk))
    {
    $this->putCacheEntry(
        $this->cacheNamespace,
        $key,
        ['value' => $result, 'expires' => time() + $this->hotCacheLifetime],
        $this->hotCacheLimit
    );
    }

    return $result;
  }

  /**
   * Determines whether an ACL result is sufficiently reusable to be stored
   * in the session hot cache. Image-specific decisions remain request-local.
   *
   * @param   string  $asset  Asset name used by the permission check.
   * @param   int     $pk     Primary key of the checked item.
   *
   * @return  bool  True when the result may be stored in the hot cache.
   *
   * @since   4.4.0
   */
  protected function isHotCacheCandidate(string $asset, int $pk): bool
  {
    if($this->hotCacheLimit === 0 || $this->hotCacheLifetime === 0)
    {
      return false;
    }

    $asset = trim($asset, '.');

    if($pk > 0 && strpos($asset, '.image') !== false)
    {
      return false;
    }

    if($asset === '' || $asset === 'joomgallery' || $asset === $this->option)
    {
      return true;
    }

    $parts = explode('.', strpos($asset, 'com_') === 0 ? $asset : $this->option . '.' . $asset);

    return \count($parts) <= 2
      || (isset($parts[1]) && $parts[1] === 'category')
      || (isset($parts[1]) && $parts[1] === 'image' && $pk === 0);
  }

  /**
   * Persists all dirty Access cache namespaces to the current session at the
   * end of the request.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function storeCacheToSession(): void
  {
    $this->persistCachesToSession();
  }

  /**
   * Clears request-local ACL results and removes the current identity's hot
   * cache entries from the session.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function clearCache(): void
  {
    $this->checks = [];

    // Clear every identity namespace for this component which was loaded in
    // the current session/request, not just the identity currently selected.
    $prefix     = 'com_joomgallery.accesscache.' . sha1($this->option) . '.';
    $namespaces = array_keys(self::$loadedCaches);

    foreach($namespaces as $namespace)
    {
      if(strpos($namespace, $prefix) === 0)
      {
        $this->removeCacheEntries($namespace);
      }
    }

    // The current namespace may not have been included if initialisation failed.
    if(!\in_array($this->cacheNamespace, $namespaces, true))
    {
      $this->removeCacheEntries($this->cacheNamespace);
    }
  }

  /**
   * Prepare the entered asset to make it conform with $user->authorize method.
   *
   * @param   string   $asset      The given asset.
   * @param   int      $pk         Primary key of the asset.
   * @param   int      $parent_pk  Primary key of the parent asset.
   * @param   bool     $use_parent True when the supplied parent key should be used for the check.
   *
   * @return  array    The prepared asset list.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function prepareAsset(string $asset, int $pk = 0, int $parent_pk = 0, bool $use_parent = false): array
  {
    // Do we have a global asset?
    $global = false;

    if(!$asset || $asset === str_replace('com_', '', $this->option))
    {
      $asset  = $this->option;
      $global = true;
    }

    // Option in asset partially given?
    if(strpos($asset, str_replace('com_', '', $this->option)) === 0)
    {
      $asset = 'com_' . $asset;
    }

    // First entry has to be the option
    if(strpos($asset, $this->option) !== 0)
    {
      $asset = $this->option . '.' . $asset;
    }

    // Get type from asset
    $asset_array = explode('.', $asset);

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
        $asset = $asset_array[0] . '.' . $asset_array[1] . '.' . \strval($parent_pk);
      }
      else
      {
        $asset = $asset . '.' . \strval($parent_pk);
      }
    }
    elseif(!$global && !$use_parent && \in_array($asset_type, $this->asset_global_types))
    {
      // We have a global only asset
      $asset = $asset_array[0] . '.' . $asset_array[1] . '.1';
    }
    elseif(!$global && !$use_parent && $pk > 0 && substr($asset, -\strlen($pk)) !== $pk)
    {
      // We have a standard asset
      $asset = $asset . '.' . \strval($pk);
    }

    // Update type from asset
    $asset_array = explode('.', $asset);

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
   * Normalises an entered action and maps supported synonyms to the canonical
   * JoomGallery action name.
   *
   * @param   string  $action  Action name or supported synonym to prepare.
   *
   * @return  string  Canonical action name.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function prepareAction(string $action): string
  {
    // Clean action if it is dot separated (core.delete)
    $act_array = explode('.', $action, 2);

    if(\count($act_array) >= 2)
    {
      $action = $act_array[1];
    }

    // Take away own and inown in action statement
    // ".own" and ".inown" are permission-rule qualifiers, not ownership-only
    // request modes. checkACL() always combines the normal and applicable
    // ownership-aware rules for the requested base action.
    $action = str_replace(['.own', '.inown'], '', $action);

    // Synonyms for add
    $addSyn = ['add', 'create', 'new', 'upload'];
    // Synonyms for delete
    $delSyn = ['delete', 'remove', 'drop', 'clear', 'erase'];
    // Synonyms for edit
    $editSyn = ['edit', 'change', 'modify', 'alter'];
    // Synonyms for editstate
    $stateSyn = ['editstate', 'edit.state', 'feature', 'unfeature', 'publish', 'unpublish', 'approve', 'unapprove'];
    // Synonyms for admin
    $adminSyn = ['admin', 'acl'];
    // Synonyms for manage
    $manageSyn = ['manage', 'options'];

    // Compose array
    $composition = [$addSyn, $delSyn, $editSyn, $editSyn, $stateSyn, $adminSyn, $manageSyn];

    // Get the correct action from composition array
    if(!$res = $this->arrayRecursiveSearch($action, $composition))
    {
      $this->component->addLog('Invalid asset provided for ACL access check', 'error', 'jerror');
      throw new \Exception('Invalid action provided for ACL access check', 1);
    }

    return $res;
  }

  /**
   * Searches recursively for a value and returns the first value from the
   * array level in which the requested value was found.
   *
   * @param   string  $needle  Value to search for.
   * @param   array   $array   Nested array to search.
   *
   * @return  string  First value from the matching array level.
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
      elseif(\is_array($value))
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
