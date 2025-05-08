<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

\defined('_JEXEC') or die;

use \Joomla\Database\DatabaseInterface;

/**
* Trait to bridge the transition between getDbo and getDatabse
*
* @since  4.1.0
*/
trait LegacyDatabaseTrait
{
  /**
   * Get the database.
   *
   * @return  DatabaseInterface
   *
   * @throws  DatabaseNotFoundException May be thrown if the database has not been set.
   * @throws  LogicException May be thrown if neighter getDatabase nor getDbo is available.
   * @note    This method will be removed in 7.0 and DatabaseAwareTrait will be used instead.
   */
  protected function getDatabase(): DatabaseInterface
  {
    $currentClass = \get_class($this);

    // Traverse up the class hierarchy and look for the method getDatabase()
    while($parent = \get_parent_class($currentClass))
    {
      if(\method_exists($parent, 'getDatabase'))
      {
        $method = new \ReflectionMethod($parent, 'getDatabase');

        // Avoid infinite recursion by ensuring we're not calling the trait version again
        $avoid = [__TRAIT__, \get_class($this), \get_parent_class($this)];
        if(!\in_array($method->getDeclaringClass()->name, $avoid))
        {
          if($method->isPublic() || $method->isProtected())
          {
            return $method->invoke($this);
          }
        }
      }

      $currentClass = $parent;
    }

    // If we havent found getDatabase(), we use getDbo()
    if(\method_exists($this, 'getDbo'))
    {
      return $this->getDbo();
    }

    throw new \LogicException('Neither getDatabase nor getDbo is available.');
  }

  /**
   * Set the database.
   *
   * @param   DatabaseInterface  $db  The database.
   *
   * @return  void
   * @note    This method will be removed in 7.0 and DatabaseAwareTrait will be used instead.
   */
  public function setDatabase(DatabaseInterface $db): void
  {
    $this->_db                        = $db;
    $this->databaseAwareTraitDatabase = $db;
  }
}
