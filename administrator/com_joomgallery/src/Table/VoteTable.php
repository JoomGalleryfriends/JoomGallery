<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Table\Asset\GlobalAssetTableTrait;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Vote table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class VoteTable extends Table
{
    use JoomTableTrait;
    use GlobalAssetTableTrait;
    use MigrationTableTrait;

    /**
     * Constructor
     *
     * @param   JDatabase  &$db               A database connector object
     * @param   bool       $component_exists  True if the component object class exists
     */
    public function __construct(DatabaseDriver $db, bool $component_exists = true)
    {
        $this->component_exists = $component_exists;
        $this->typeAlias        = _JOOM_OPTION . '.vote';

        parent::__construct(_JOOM_TABLE_VOTES, 'id', $db);
    }

    /**
     * Overloaded bind function to pre-process the params.
     *
     * @param   array  $array   Named array
     * @param   mixed  $ignore  Optional array or list of parameters to ignore
     *
     * @return  boolean  True on success.
     *
     * @see     Table:bind
     * @since   4.0.0
     * @throws  \InvalidArgumentException
     */
    public function bind($array, $ignore = '')
    {
        $date = Factory::getDate();

        if($array['id'] == 0)
        {
            $array['created_time'] = $date->toSql();
        }

        if($array['id'] == 0 && (!key_exists('created_by', $array) || empty($array['created_by'])))
        {
            $array['created_by'] = Factory::getApplication()->getIdentity()->id;
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Delete a record by id
     *
     * @param   mixed  $pk  Primary key value to delete. Optional
     *
     * @return bool
     */
    public function delete($pk = null)
    {
        $this->_trackAssets = false;

        return parent::delete($pk);
    }
}
