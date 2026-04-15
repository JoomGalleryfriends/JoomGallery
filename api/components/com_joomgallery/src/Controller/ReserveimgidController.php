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

// phpcs:disable PSR1.Files.SideEffects
use Joomla\CMS\Language\Text;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Tobscure\JsonApi\Exception\InvalidParameterException;

\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The images controller
 *
 * @since  4.4.0
 */
class ReserveimgidController extends ImagesController // ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $contentType = 'images';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  3.0
     */
    protected $default_view = 'images';

    /**
     * Adds some parameters for file name
     * then uses parent:add to save
     *
     * @since version
     */
    public function db_reserve_image_id()
    {

//        $data = json_decode($this->input->json->getRaw(), true);
//        $title = $this->input->json->getString ('title');

        $srcFilename  = $this->input->json->getString('filename');
        $srcExtension = $this->input->json->getString('file_extension');
//        $srcFilename = $title . $this->input->json->getPath ('filename');
        $catId = $this->input->json->getString('catid');

        $missingParameters = [];

        if (empty($srcFilename)) {
            $missingParameters[] = 'filename';
        }

        if (empty($srcExtension)) {
            $missingParameters[] = 'file_extension';
        }

        if (empty($catId)) {
            $missingParameters[] = 'catid';
        }

        if (\count($missingParameters)) {
            // throw new InvalidParameterException(Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters)));
            throw new InvalidParameterException(Text::sprintf('Missing required parameter(s): %s', implode(' & ', $missingParameters)));
        }

//        $filename = $this->genFilename($srcFilename, $srcExtension, $catId);
//        $this->input->json->set('filename', $filename);


        // Add/save ....
        parent::add();
    }


    /**
     * Method to allow extended classes to manipulate the data to be saved for an extension.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    protected function preprocessSaveData(array $data): array
    {
//        foreach (FieldsHelper::getFields('com_contact.contact') as $field) {
//            if (isset($data[$field->name])) {
//                !isset($data['com_fields']) && $data['com_fields'] = [];
//
//                $data['com_fields'][$field->name] = $data[$field->name];
//                unset($data[$field->name]);
//            }
//        }

        return $data;
    }




}
