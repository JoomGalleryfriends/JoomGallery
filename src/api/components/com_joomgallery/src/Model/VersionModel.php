<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Model;

use Joomgallery\Component\Joomgallery\Api\Helper\ManifestHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Media\Administrator\Model\ApiModel;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since  4.4.0
 */
class VersionModel extends BaseDatabaseModel
{
    protected string $componentName = 'com_joomgallery';

    /**
     * @param $config
     *
     * @throws \Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

//        $this->versionApiModel = new ApiModel();
    }

    /**
     * Method to get a single result
     *
     * @return  \stdClass  A file or folder object.
     *
     * @throws  ResourceNotFound
     * @since   4.1.0
     */
    public function getItem()
    {
        // Dummy default
        $oVersion               = new \stdClass();
        $oVersion->version      = 'xx.xx.xx';
        $oVersion->creationDate = '2025.xx.xx';

        try
        {
            $oManifest = ManifestHelper::getDbManifest($this->componentName);

            if(!empty($oManifest))
            {
                $oVersion->version      = $oManifest['version'];
                $oVersion->creationDate = $oManifest['creationDate'];
            }
        }
        catch (\Exception $e)
        {
            throw new \RuntimeException($e->getMessage());
        }

        return $oVersion;
    }

    /**
     * Save manifest data
     * Transfers 'version' and 'creationDate' in data
     *
     * @param   mixed  $data
     *
     * @since  4.4.0
     */
    public function save(mixed $data = [], $isForce = false)
    {
        $isSaved = true;

        // accepting multiple parameter
        if(!empty($data))
        {
            $isChanged = false;
            $isSaved   = false;

            try
            {
                $oManifest = ManifestHelper::getDbManifest($this->componentName);

                if(!empty($oManifest))
                {
                    //--- version ------------------------------------

                    if(!empty($data['version']))
                    {
                        $version = $data['version'];

                        if($oManifest['version'] != $version)
                        {
                            $oManifest['version'] = $data['version'];
                            $isChanged            = true;
                        }
                    }

                    //--- creation date ------------------------------------

                    if(!empty($data['creationDate']))
                    {
                        $creationDate = $data['creationDate'];

                        if($oManifest['creationDate'] != $creationDate)
                        {
                            $oManifest['creationDate'] = $creationDate;
                            $isChanged                 = true;
                        }
                    }

                    //--- save changes ----------------------------------------

                    if($isChanged)
                    {
                        $isSaved = ManifestHelper::saveDbManifest($oManifest, $this->componentName);
                    }
                }
            }
            catch (\Exception $e)
            {
                throw new \RuntimeException($e->getMessage());
            }
        }

        return $isSaved;
    }
}
