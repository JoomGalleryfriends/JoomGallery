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

use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;
use Tobscure\JsonApi\Exception\InvalidParameterException;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects


class RecreatesizesController extends ApiController
{
    use ProviderManagerHelperTrait;

    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.1.0
     */
    protected $contentType = 'recreatesizes';


    /**
     * The default view for the display method.
     *
     * @var    string
     *
     * @since  4.1.0
     */
    protected $default_view = 'recreatesizes';

    public function recreate_sizes()
    {

        $image_id  = $this->input->json->get('image_id', '', 'INTEGER');
        //$category_id = $this->input->json->get('category_id', '', 'INTEGER');

        $missingParameters = [];

        if (empty($image_id))
        {
            $missingParameters[] = 'image_id';
        }

//        if (empty($category_id))
//        {
//            $missingParameters[] = 'category_id';
//        }

        if (\count($missingParameters))
        {
//      throw new InvalidParameterException(Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters)));
            throw new InvalidParameterException(Text::sprintf('Missing required parameter(s): %s', implode(' & ', $missingParameters)));
        }

        //----------------------------------------------------
        // Create details, thumbs and ?watermarked? images
        //----------------------------------------------------

        try
        {
            /* @var ImageModel $modelFile */
            $modelFile = $this->getModel('image');

            $isCreated = $modelFile->recreate($image_id, 'original');
        }
        catch (\RuntimeException $e)
        {
            $OutTxt = '';
            $OutTxt .= 'recreate for image id: "' . $image_id . '" did fail with following message ' . '"<br>';
            $OutTxt .= 'Error: "' . $e->getMessage() . '"' . '<br>';

            $app = Factory::getApplication();
            $app->enqueueMessage($OutTxt, 'error');

        }

        if (!$isCreated)
        {
            // ToDo: remove $imageId fom image database

            //...

            // ToDO: Message ?

        }

        return;
    }

}

