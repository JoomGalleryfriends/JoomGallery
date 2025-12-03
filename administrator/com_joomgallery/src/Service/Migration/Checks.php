<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Language\Text;

/**
 * Migration Checks Class
 * Providing a structure for the results of migration checks
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Checks
{
  /**
   * List of assets available in the check-objects
   * array(categoryName.checkName => categoryKey.checkKey)
   *
   * @var  array
   *
   * @since  4.0.0
   */
  private $assets = [];

  /**
   * The array of check-objects
   *
   * @var  \stdClass[]
   *
   * @since  4.0.0
   */
  private $objects = [];

  /**
   * The overall success of all checks
   * True if all checks were successful, false otherwise
   *
   * @var  bool
   *
   * @since  4.0.0
   */
  private $success = true;

  /**
   * The overall error message
   * This message is displayed on top of the results display
   *
   * @var  string
   *
   * @since  4.0.0
   */
  private $message = '';

  /**
   * Register a new category or modify an existing one
   *
   * @param   string   $name    The name of the category
   * @param   string   $title   Optional: Title of the category
   * @param   string   $desc    Optional: Description of the category
   *
   * @return  void
   *
   * @since  4.0.0
   * @throws \Exception
   */
  public function addCategory(string $name, string $title = '', string $desc = '', string $colTitle = '')
  {
    // Make category name lowercase
    $name = strtolower(trim($name));

    if(!\in_array($name, array_keys($this->assets)))
    {
      // Category not yet existing, create a new one
      $cat = new \stdClass();
      $cat->name     = $name;
      $cat->title    = $title;
      $cat->desc     = $desc;
      $cat->colTitle = (empty($colTitle)) ? Text::_('COM_JOOMGALLERY_CHECK') : $colTitle;
      $cat->checks   = [];

      // Add category to check-objects array
      $key = $this->array_push($this->objects, $cat);

      // Add category to assets array
      $this->assets[$name] = $key;
    }
    else
    {
      // You try to add a category already existing
      $this->component->addLog('You try to add a category that already exists. If you want to modify it, use "modCategory()" instead.', 'error', 'jerror');
      throw new \Exception('You try to add a category that already exists. If you want to modify it, use "modCategory()" instead.', 1);
    }
  }

  /**
   * Modify an existing category
   *
   * @param   string   $name    The name of the category
   * @param   string   $title   Optional: Title of the category
   * @param   string   $desc    Optional: Description of the category
   *
   * @return  void
   *
   * @since  4.0.0
   * @throws \Exception
   */
  public function modCategory(string $name, $title = null, $desc = null)
  {
    // Make category name lowercase
    $name = strtolower(trim($name));

    if(!\in_array($name, array_keys($this->assets)))
    {
      // You try to modify a category which does not exist
      $this->component->addLog('You try to modify a category which does not exists. Please add the category first.', 'error', 'jerror');
      throw new \Exception('You try to modify a category which does not exists. Please add the category first.', 1);
    }


      $key = $this->assets[$name];

      // Modify title and/or description
      if(!\is_null($title))
      {
        $this->objects[$key]->title = (string) $title;
      }

      if(!\is_null($desc))
      {
        $this->objects[$key]->desc  = (string) $desc;
      }

  }

  /**
   * Add a new check being performed
   *
   * @param   string   $category   The category of the check
   * @param   string   $name       The name of the check
   * @param   bool     $result     True if the check was successful, false otherwise
   * @param   bool     $warning    True if the check should be displayed as a warning
   * @param   string   $title      Optional: Title of the check
   * @param   string   $desc       Optional: Description of the check
   * @param   string   $help       Optional: URL to a help-site or help-text
   * @param   bool     $isURL      Optional: True, if $help is an URL
   *
   * @return  void
   *
   * @since  4.0.0
   * @throws \Exception
   */
  public function addCheck(string $category, string $name, bool $result, bool $warning = false, string $title = '', string $desc = '', string $help = '', bool $isURL = true)
  {
    // Make category and check name lowercase
    $category = strtolower(trim($category));
    $name     = strtolower(trim($name));
    $asset    = $category.'.'.$name;

    // Check if category exists
    if(!\in_array($category, array_keys($this->assets)))
    {
      $this->component->addLog('You try to add a check to a category which is not existing. Please add the category first.', 'error', 'jerror');
      throw new \Exception('You try to add a check to a category which is not existing. Please add the category first.', 1);
    }

    // Check if asset exists
    if(!\in_array($asset, array_keys($this->assets)))
    {
      // Get category key
      $catKey = $this->assets[$category];

      // Asset not yet existing, create a new one
      $check = new \stdClass();
      $check->name    = $name;
      $check->result  = $result;
      $check->warning = $warning;
      $check->title   = $title;
      $check->desc    = $desc;
      $check->help    = $help;

      if($help && $isURL && strpos($help, 'joomgalleryfriends.net') === false)
      {
        // Remove leading slashes
        if(substr($help, 0, 1) === '/')
        {
          $help = substr($help, 1);
        }

        $check->help = Text::_('COM_JOOMGALLERY_WEBSITE_HELP_URL') . '/migration/' . $help;
      }

      // Add check to check-objects array
      $key = $this->array_push($this->objects[$catKey]->checks, $check);

      // Add check to assets array
      $this->assets[$asset] = $catKey.'.'.$key;

      // Modify the overall success if needed
      if($result === false)
      {
        $this->success = false;

        if($this->message === '')
        {
          $this->message = $title;
        }
      }
      else
      {
        if($warning && $this->message === '')
        {
          // Add message if there is a warning
          $this->message = $title;
        }
      }
    }
    else
    {
      // You try to add a check already existing
      $this->component->addLog('You try to add a check that already exists. If you want to modify it, use "modCheck()" instead.', 'error', 'jerror');
      throw new \Exception('You try to add a check that already exists. If you want to modify it, use "modCheck()" instead.', 2);
    }
  }

  /**
   * Modify an existing check
   *
   * @param   string   $category   The category of the check
   * @param   string   $name       The name of the check
   * @param   bool     $result     True if the check was successful, false otherwise
   * @param   bool     $warning    True if the check should be displayed as a warning
   * @param   string   $title      Optional: Title of the check
   * @param   string   $desc       Optional: Description of the check
   * @param   string   $help       Optional: URL to a help-site or help-text
   *
   * @return  void
   *
   * @since  4.0.0
   * @throws \Exception
   */
  public function modCheck(string $category, string $name, $result = null, $warning = null, $title = null, $desc = null, $help = null)
  {
    // Make category and check name lowercase
    $category = strtolower(trim($category));
    $name     = strtolower(trim($name));
    $asset    = $category.'.'.$name;

    // Check if category exists
    if(!\in_array($category, array_keys($this->assets)))
    {
      $this->component->addLog('You try to modify a check in a category which is not existing. Please add the category first.', 'error', 'jerror');
      throw new \Exception('You try to modify a check in a category which is not existing. Please add the category first.', 1);
    }

    // Check if asset exists
    if(!\in_array($asset, array_keys($this->assets)))
    {
      // You try to modify a check which does not exist
      $this->component->addLog('You try to modify a check which does not exists. Please add the check first.', 'error', 'jerror');
      throw new \Exception('You try to modify a check which does not exists. Please add the check first.', 2);
    }


      $key = $this->assets[$asset];
      list($catKey, $checkKey) = explode('.', $key, 2);

      // Modify the result
      if(!\is_null($result))
      {
        $this->objects[$catKey]->checks[$checkKey]->result = \boolval($result);

        // Modify the overall success if needed
        if(\boolval($result) === false)
        {
          $this->success = false;
        }
      }

      // Modify the warning status
      if(!\is_null($warning))
      {
        $this->objects[$catKey]->checks[$checkKey]->warning  = \boolval($warning);
      }

      // Modify the title
      if(!\is_null($title))
      {
        $this->objects[$catKey]->checks[$checkKey]->title  = (string) $title;
      }

      // Modify the description
      if(!\is_null($desc))
      {
        $this->objects[$catKey]->checks[$checkKey]->desc  = (string) $desc;
      }

      // Modify the description
      if(!\is_null($help))
      {
        $this->objects[$catKey]->checks[$checkKey]->help  = (string) $help;
      }

  }

  /**
   * Returns all registered checks
   *
   * @return  array  A list of checks
   *
   * @since  4.0.0
   */
  public function getChecks(): array
  {
    return $this->objects;
  }

  /**
   * Returns the overall success of the checks
   *
   * @return  bool  True if all checks were successful, false otherwise
   *
   * @since  4.0.0
   */
  public function getSuccess(): bool
  {
    return $this->success;
  }

  /**
   * Returns the registered checks and the overall success
   *
   * @return  array  array($this->success, $this->objects, $this->message)
   *
   * @since  4.0.0
   */
  public function getAll(): array
  {
    return [$this->success, $this->objects, $this->message];
  }

  /**
   * Wrapper for the php function 'array_push' with new created key as return value
   *
   * @return  int  Key of the new created array entry
   *
   * @since  4.0.0
   */
  protected function array_push(array &$array, $item): int
  {
    $next = \count($array);
    $array[$next] = $item;

    return $next;
  }
}
