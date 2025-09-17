<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Api\Serializer;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Serializer\JoomlaSerializer;
use Joomla\CMS\Tag\TagApiSerializerTrait;
use Joomla\CMS\Uri\Uri;
use Tobscure\JsonApi\Collection;
use Tobscure\JsonApi\Relationship;
use Tobscure\JsonApi\Resource;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Temporary serializer
 *
 * @since  4.0.0
 */
class JoomgallerySerializer extends JoomlaSerializer
{
//    use TagApiSerializerTrait;
//
//    /**
//     * Build content relationships by associations
//     *
//     * @param   \stdClass  $model  Item model
//     *
//     * @return  Relationship
//     *
//     * @since 4.0.0
//     */
//    public function languageAssociations($model)
//    {
//        $resources = [];
//
//        // @todo: This can't be hardcoded in the future?
//        $serializer = new JoomlaSerializer($this->type);
//
//        foreach ($model->associations as $association) {
//            $resources[] = (new Resource($association, $serializer))
//                ->addLink(
//                    'self',
//                    Route::link('administrator', Uri::root() . 'api/index.php/v1/joomgallery/images/' . $association->id)
//                );
//        }
//
//        $collection = new Collection($resources, $serializer);
//
//        return new Relationship($collection);
//    }
//
//    /**
//     * Build category relationship
//     *
//     * @param   \stdClass  $model  Item model
//     *
//     * @return  Relationship
//     *
//     * @since 4.0.0
//     */
//    public function category($model)
//    {
//        $serializer = new JoomlaSerializer('categories');
//
//        $resource = (new Resource($model->catid, $serializer))
//            ->addLink(
//                'self',
//                Route::link('siadministratorte', Uri::root() . 'api/index.php/v1/joomgallery/images/' . $model->catid)
//            );
//
//        return new Relationship($resource);
//    }
//
//    /**
//     * Build category relationship
//     *
//     * @param   \stdClass  $model  Item model
//     *
//     * @return  Relationship
//     *
//     * @since 4.0.0
//     */
//    public function createdBy($model)
//    {
//        $serializer = new JoomlaSerializer('users');
//
//        $resource = (new Resource($model->created_by, $serializer))
//            ->addLink('self', Route::link('administrator', Uri::root() . 'api/index.php/v1/users/' . $model->created_by));
//
//        return new Relationship($resource);
//    }
//
//    /**
//     * Build editor relationship
//     *
//     * @param   \stdClass  $model  Item model
//     *
//     * @return  Relationship
//     *
//     * @since 4.0.0
//     */
//    public function modifiedBy($model)
//    {
//        $serializer = new JoomlaSerializer('users');
//
//        $resource = (new Resource($model->modified_by, $serializer))
//            ->addLink('self', Route::link('administrator', Uri::root() . 'api/index.php/v1/users/' . $model->modified_by));
//
//        return new Relationship($resource);
//    }
}
