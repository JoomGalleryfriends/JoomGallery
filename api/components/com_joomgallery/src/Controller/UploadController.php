<?php

/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Controller;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\ApiController;
use Tobscure\JsonApi\Exception\InvalidParameterException;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;
use Joomla\Component\Media\Api\Model\MediumModel;
use Joomla\Filesystem\File;
use Joomla\String\Inflector;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects


class UploadFileController extends ApiController
{
  use ProviderManagerHelperTrait;

  /**
   * The content type of the item.
   *
   * @var    string
   * @since  4.1.0
   */
  protected $contentType = 'upload_file';

  /**
   * The default view for the display method.
   *
   * @var    string
   *
   * @since  4.1.0
   */
  protected $default_view = 'upload_file';


  public function upload_image_file(): void
  {
    // $image_name = $this->input->json->get('image_name', '', 'PATH');
    $image_name = $this->input->json->get('image_name', '', 'STRING');
    $gallery_id = $this->input->json->get('category_id', '', 'INTEGER');
    $content    = $this->input->json->get('content', '', 'RAW');

    $missingParameters = [];

    if (empty($image_name)) {
      $missingParameters[] = 'image_name';
    }

    if (empty($gallery_id)) {
      $missingParameters[] = 'category_id';
    }

    // Content is required as we expect a file
    if (empty($content)) {
      $missingParameters[] = 'content';
    }

    if (\count($missingParameters)) {
      throw new InvalidParameterException(
        Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters)),
      );
    }

    //--- secure path and image name ----------------------------

    // secure image name
    $safeFileName = File::makeSafe($image_name);

    $this->modelState->set('image_name', $safeFileName);
    $this->modelState->set('gallery_id', $gallery_id);


    // Check if an existing file may be overwritten. Defaults to false.
    $this->modelState->set('override', $this->input->json->get('override', false));

    // calls $this->save
    parent::add();
  }

  /**
   * Method to create or modify a file or folder.
   *
   * @param   integer  $recordKey  The primary key of the item (if exists)
   *
   * @return  string   The path
   *
   * @since   4.1.0
   */
  protected function save($recordKey = null)
  {
    // Explicitly get the single item model name.
    $modelName = $this->input->get('model', Inflector::singularize($this->contentType));

    /** @var MediumModel $model */
    $model = $this->getModel($modelName, '', ['ignore_request' => true, 'state' => $this->modelState]);

    $json = $this->input->json;

    // Decode content, if any
    if ($content = base64_decode($json->get('content', '', 'raw'))) {
      $this->checkContent();
    }

    // If there is no content, com_media assumes the path refers to a folder.
    $this->modelState->set('content', $content);

    return $model->save();
  }



}