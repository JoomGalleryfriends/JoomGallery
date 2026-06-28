<?php
/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\View\Configinj;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\Serializer\JoomlaSerializer;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The config joomla part view
 *
 * @since  4.4.0
 */
class JsonapiView extends BaseApiView
{
  /**
   * The fields to render item in the documents
   *
   * @var  array
   * @since  4.4.0
   */
  protected $fieldsToRenderItem = [];

  /**
   * The fields to render items in the documents
   *
   * @var  array
   * @since  4.4.0
   */
  protected $fieldsToRenderList = [];

  /**
   * Constructor.
   *
   * @param   array   $config  A named configuration array for object construction.
   *                           contentType: the name (optional) of the content type to use for the serialization
   *
   * @since  4.4.0
  */
  public function __construct($config = [])
  {
    if(\array_key_exists('contentType', $config))
    {
      $this->serializer = new JoomlaSerializer($config['contentType']);
    }

    $this->fieldsToRenderItem = $this->getConfigParameterNames();

    parent::__construct($config);
  }

  /**
   * Prepare item before render.
   *
   * @param   object   $item  The model item
   *
   * @return  object
   *
   * @since  4.4.0
  */
  protected function prepareItem($item)
  {
    // Media resources have no id.
    $item->id = '0';

    return $item;
  }


  /**
   * Method to get all configuration names
   *
   * @return  \stdClass  A file or folder object.
   *
   * @throws  ResourceNotFound
   * @since  4.4.0
   */
  public function getConfigParameterNames()
  {

    $componentName = 'com_joomgallery';
    $params   = [];

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);

      $query = $db->getQuery(true)
        ->select($db->quoteName('params'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('element').' = '.$db->quote($componentName));
      $db->setQuery($query);

      $jsonStr = $db->loadResult();

      if(!empty($jsonStr))
      {
        $params = json_decode($jsonStr, true);
      }

      foreach($params as $name => $value)
      {
        $params[] = $name;
      }
    }
    catch(\Exception $e)
    {
      throw new \RuntimeException($e->getMessage());
    }

    return $params;
  }
}
