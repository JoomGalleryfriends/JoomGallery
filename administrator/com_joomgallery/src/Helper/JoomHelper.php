<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\Registry\Registry;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Filesystem\Path;
use \Joomla\CMS\Router\Route;


/**
 * JoomGallery Helper for the Backend
 *
 * @static
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomHelper
{
  /**
	 * Gets the JoomGallery component object
	 *
	 * @return  Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
	 *
	 * @since   4.0.0
	 */
  public static function getComponent()
  {
    return Factory::getApplication()->bootComponent('com_joomgallery');
  }

  /**
	 * Gets a JoomGallery service
   *
   * @param   string   $name      The name of the service
   * @param   array    $arg       Arguments passed to the cunstructor of the service (optional)
   * @param   Object   $com_obj   JoomgalleryComponent object if available
	 *
	 * @return  JoomService
	 *
	 * @since   4.0.0
	 */
  public static function getService($name, $arg=array(), $com_obj=null)
  {
    // get the JoomgalleryComponent object if needed
    if(!isset($com_obj) || !\strpos('JoomgalleryComponent', \get_class($com_obj)) === false)
    {
      $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
    }

    // create the service
    try
    {
      $createService = 'create'.\ucfirst($name);
      switch (\count($arg))
      {
        case 5:
          $com_obj->{$createService}($arg[0], $arg[1], $arg[2], $arg[3], $arg[4]);
          break;
        case 4:
          $com_obj->{$createService}($arg[0], $arg[1], $arg[2], $arg[3]);
          break;
        case 3:
          $com_obj->{$createService}($arg[0], $arg[1], $arg[2]);
          break;
        case 2:
          $com_obj->{$createService}($arg[0], $arg[1]);
          break;
        case 1:
          $com_obj->{$createService}($arg[0]);
          break;
        case 0:
          $com_obj->{$createService}();
          break;
        default:
          throw new Exception('Too many arguments passed to getService()');
          break;
      }
    }
    catch (Exception $e)
    {
      echo 'Creation of the service failed. Error: ',  $e->getMessage(), "\n";
    }

    // get the service
    $getService = 'get'.\ucfirst($name);

    return $com_obj->{$getService}();
  }

  /**
	 * Returns a database record
   *
   * @param   string          $name      The name of the record (available: category,image,tag)
   * @param   int             $id        The id of the primary key
   * @param   Object          $com_obj   JoomgalleryComponent object if available
	 *
	 * @return  CMSObject|bool  Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getRecord($name, $id, $com_obj=null)
  {
    $availables = array('category', 'image', 'tag', 'imagetype');

    if(!\in_array($name, $availables))
    {
      throw new \Exception('Please provide an available name of the record type.');

      return false;
    }

    if($name != 'imagetype' || !\is_array($id))
    {
      $id = intval($id);
    }

    if(!empty($id) && ($id > 0 || \is_array($id)))
    {
      // get the JoomgalleryComponent object if needed
      if(!isset($com_obj) || !\strpos('JoomgalleryComponent', \get_class($com_obj)) === false)
      {
        $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
      }

      $model = $com_obj->getMVCFactory()->createModel($name);

      if(\is_null($model))
      {
        throw new \Exception('Record-Type '.$name.' does not exist.');
      }

      // Attempt to load the record.
      $return = $model->getItem($id);

      return $return;
    }
    else
    {
      throw new \Exception('Please provide an ID.');

      return false;
    }
  }

  /**
	 * Returns a list of database records
   *
   * @param   string      $name      The name of the record (available: categories,images,tags,imagetypes)
   * @param   Object      $com_obj   JoomgalleryComponent object if available
	 *
	 * @return  array|bool  Array on success, false on failure.
	 *
	 * @since   4.0.0
	 */
  public static function getRecords($name, $com_obj=null)
  {
    $availables = array('categories', 'images', 'tags', 'imagetypes');

    if(!\in_array($name, $availables))
    {
      throw new \Exception('Please provide an available name of the record type.');

      return false;
    }

    // get the JoomgalleryComponent object if needed
    if(!isset($com_obj) || !\strpos('JoomgalleryComponent', \get_class($com_obj)) === false)
    {
      $com_obj = Factory::getApplication()->bootComponent('com_joomgallery');
    }

    $model = $com_obj->getMVCFactory()->createModel($name);

    if(\is_null($model))
    {
      throw new \Exception('Record-Type '.$name.' does not exist.');
    }

    // Attempt to load the record.
    $return = $model->getItems();

    return $return;
  }

	/**
	 * Gets the files attached to an item
	 *
	 * @param   int     $pk     The item's id
	 * @param   string  $table  The table's name
	 * @param   string  $field  The field's name
	 *
	 * @return  array  The files
	 */
	public static function getFiles($pk, $table, $field)
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		$query
			->select($field)
			->from($table)
			->where('id = ' . (int) $pk);

		$db->setQuery($query);

		return explode(',', $db->loadResult());
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return  CMSObject
	 *
	 * @since   4.0.0
	 */
	public static function getActions()
	{
		$user   = Factory::getUser();
		$result = new CMSObject;

		$assetName = 'com_joomgallery';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

  /**
   * Returns the URL or the path to an image
   *
   * @param   string/object/int $img    Filename, database object or ID of the image
   * @param   string            $type   The image type
   * @param   bool              $url    True: image url, false: image path (default: true)
   *
   * @return  mixed             URL or path to the image on success, false otherwise
   *
   * @since   1.5.5
   */
  public static function getImg($img, $type, $url=true)
  {
    // get imagetypes
    $imagetype = self::getRecord('imagetype', array('typename' => $type));

    if($imagetype === false)
    {
      Factory::getApplication()->enqueueMessage('Imagetype not found!', 'error');

      return false;
    }

    if(!is_object($img))
    {
      if(is_numeric($img))
      {
        // get image based on ID
        $img = self::getRecord('image', $img);
      }
      else
      {
        // get image id based on filename
        $img = self::getRecord('image', array('filename' => $img));
      }
    }

    if(!is_object($img))
    {
      // image object not found
      Factory::getApplication()->enqueueMessage('Image not available!', 'error');

      return false;
    }

    // Check whether the image shall be output through the PHP script or with its real path
    if($url)
    {
      return  Route::_('index.php?option='._JOOM_OPTION.'&controller=images&view=image&format=raw&type='.$type.'&id='.$img->id);
    }
    else
    {
      // get corresponding category
      $cat  = JoomHelper::getRecord('category', $img->catid);

      // Create the complete path
      $path = $imagetype->path.\DIRECTORY_SEPARATOR.$cat->path.\DIRECTORY_SEPARATOR.$img->filename;

      return Path::clean($path);
    }
  }
}
