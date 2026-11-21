<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;

/**
 * Model for persistence of one JoomGallery user profile.
 *
 * @package  JoomGallery
 * @since    DEPLOY_VERSION
 */
class UserModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'user';

  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  mixed  The data for the form.
   *
   * @since   DEPLOY_VERSION
   */
  protected function loadFormData()
  {
    return $this->getItem();
  }

  /**
   * Profiles are deleted only by the Joomla account lifecycle.
   *
   * @param   array  &$pks  An array of record primary keys.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   DEPLOY_VERSION
   */
  public function delete(&$pks)
  {
    $this->setError('JoomGallery user profiles cannot be deleted independently of their Joomla users.');

    return false;
  }

  /**
   * Load a JoomGallery user profile by its linked Joomla user ID.
   *
   * @param   int           $cmsuser   ID of the CMS user
   *
   * @return  object|false  Profile data on success, false when no matching profile exists.
   *
   * @since   DEPLOY_VERSION
   */
  public function getItemByCmsUser(int $cmsuser)
  {
    if($cmsuser <= 0)
    {
      return false;
    }

    $table = $this->getTable();

    return $table->load(['cmsuser' => $cmsuser]) ? (object) get_object_vars($table) : false;
  }

  /**
   * Create a JoomGallery profile for a Joomla user if it does not already exist.
   *
   * @param   int   $cmsuser   ID of the CMS user
   *
   * @return  int  ID of the existing or newly created JoomGallery profile.
   *
   * @since   DEPLOY_VERSION
   */
  public function createProfile(int $cmsuser): int
  {
    if($cmsuser <= 0)
    {
      throw new \InvalidArgumentException('A valid Joomla user ID is required.');
    }

    $existing = $this->getItemByCmsUser($cmsuser);

    if($existing)
    {
      return (int) $existing->id;
    }

    $table = $this->getTable();
    $data  = [
      'id'          => 0,
      'cmsuser'     => $cmsuser,
      'description' => '',
      'files'       => '{}',
      'params'      => '{}',
    ];

    try
    {
      if(!$table->bind($data) || !$table->check() || !$table->store())
      {
        throw new \RuntimeException($table->getError() ?: 'Could not create the JoomGallery user profile.');
      }
    }
    catch(\Throwable $e)
    {
      // A concurrent request may have inserted the profile after our initial lookup.
      $existing = $this->getItemByCmsUser($cmsuser);

      if($existing)
      {
        return (int) $existing->id;
      }

      throw $e;
    }

    return (int) $table->id;
  }

  /**
   * Delete a JoomGallery user profile by its JoomGallery profile ID.
   *
   * @param   int  $id  JoomGallery profile ID.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function deleteProfile(int $id): void
  {
    if($id <= 0)
    {
      return;
    }

    $table = $this->getTable();

    if(!$table->delete($id))
    {
      throw new \RuntimeException($table->getError() ?: 'Could not delete the JoomGallery user profile.');
    }
  }

  /**
   * Delete every cached collection archive owned by a JoomGallery profile.
   *
   * The complete profile directory is removed so unregistered temporary ZIPs
   * and the profile lock cannot survive a user lifecycle deletion.
   *
   * @param   int  $id  JoomGallery profile ID.
   *
   * @return  void
   *
   * @throws  \RuntimeException  When the cache directory cannot be deleted.
   *
   * @since   __DEPLOY_VERSION__
   */
  public function deleteCollectionDownloadFiles(int $id): void
  {
    if($id <= 0)
    {
      return;
    }

    // Recreate the same profile-specific boundary used by collection ZIP generation.
    $temporaryPath = trim((string) Factory::getApplication()->get('tmp_path'));

    if($temporaryPath === '')
    {
      throw new \RuntimeException('The Joomla temporary directory is not configured.');
    }

    $root      = Path::clean(
        rtrim($temporaryPath, '/\\')
        . \DIRECTORY_SEPARATOR . 'joomgallery'
        . \DIRECTORY_SEPARATOR . 'collection-downloads'
    );
    $directory = Path::clean($root . \DIRECTORY_SEPARATOR . $id);
    $prefix    = rtrim($root, '/\\') . \DIRECTORY_SEPARATOR;

    // Refuse to remove anything outside the dedicated collection archive root.
    if(!str_starts_with(strtolower($directory), strtolower($prefix)))
    {
      throw new \RuntimeException('Invalid collection download cache directory.');
    }

    if(is_dir($directory) && !Folder::delete($directory))
    {
      throw new \RuntimeException('Could not delete the collection download cache directory.');
    }
  }
}
