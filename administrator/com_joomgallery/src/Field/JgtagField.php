<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * List of Tags field.
 *
 * @since  4.0.0
 */
class JgtagField extends ListField
{
    /**
     * A flexible tag list that respects access controls
     *
     * @var    string
     * @since  4.0.0
     */
    public $type = 'jgtag';

    /**
     * Name of the layout being used to render the field
     *
     * @var    string
     * @since  4.0.0
     */
    protected $layout = 'joomla.form.field.jgtag';

    /**
     * Method to get the field input for a tag field.
     *
     * @return  string  The field input.
     *
     * @since   4.0.0
     */
    protected function getInput()
    {
      $data = $this->getLayoutData();

      if(!\is_array($this->value) && !empty($this->value))
      {
        if(\is_object($this->value))
        {
          if(empty($this->value))
          {
            $this->value = [];
          }
          else
          {
            $tags        = $this->value;
            $this->value = [];

            foreach($tags as $tag)
            {
              array_push($this->value, $tag->id);
            }
          }
        }

        // String in format 2,5,4
        if(\is_string($this->value))
        {
          $this->value = explode(',', $this->value);
        }

        // Integer is given
        if(\is_int($this->value))
        {
          $this->value = [$this->value];
        }

        $data['value'] = $this->value;
      }

      $data['remoteSearch']  = $this->isRemoteSearch();
      $data['options']       = $this->getOptions();
      $data['isNested']      = false;
      $data['allowCustom']   = $this->allowCustom();
      $data['minTermLength'] = 3;

      // Make sure the component is correctly set
      $renderer = $this->getRenderer($this->layout);
      $renderer->setComponent('com_joomgallery');

      return $renderer->render($data);
    }

    /**
     * Method to get a list of tags
     *
     * @return  array  The field option objects.
     *
     * @since   4.0.0
     */
    protected function getOptions()
    {
      $published = (string) $this->element['published'] ?: [0, 1];
      $app       = Factory::getApplication();
      $language  = null;
      $options   = [];

      // This limit is only used with isRemoteSearch
      $prefillLimit   = 30;
      $isRemoteSearch = $this->isRemoteSearch();

      $db    = $this->getDatabase();
      $query = $db->getQuery(true)
        ->select(
            [
              $db->quoteName('a.id', 'value'),
              $db->quoteName('a.title', 'text'),
              $db->quoteName('a.published'),
              $db->quoteName('a.ordering'),
            ]
        )
          ->from($db->quoteName(_JOOM_TABLE_TAGS, 'a'));

      // Limit Options in multilanguage
      if($app->isClient('site') && Multilanguage::isEnabled())
      {
        $language = [$app->getLanguage()->getTag(), '*'];
      }
      elseif(!empty($this->element['language']))
      {
        // Filter language
        if(strpos($this->element['language'], ',') !== false)
        {
          $language = explode(',', $this->element['language']);
        }
        else
        {
          $language = [$this->element['language']];
        }
      }

      if($language)
      {
        $query->whereIn($db->quoteName('a.language'), $language, ParameterType::STRING);
      }

      // Filter on the published state
      if(is_numeric($published))
      {
        $published = (int) $published;
        $query->where($db->quoteName('a.published') . ' = :published')
              ->bind(':published', $published, ParameterType::INTEGER);
      }
      elseif(\is_array($published))
      {
        $published = ArrayHelper::toInteger($published);
        $query->whereIn($db->quoteName('a.published'), $published);
      }

      $query->order($db->quoteName('a.ordering') . ' ASC');

      // Preload only active values and 30 most used tags or fill up
      if($isRemoteSearch)
      {
          // Load the most $prefillLimit used tags
          $topQuery = $db->getQuery(true)
              ->select($db->quoteName('tagid'))
              ->from($db->quoteName(_JOOM_TABLE_TAGS_REF))
              ->group($db->quoteName('tagid'))
              ->order('count(*)')
              ->setLimit($prefillLimit);

          $db->setQuery($topQuery);
          $topIds = $db->loadColumn();

          // Merge the used values into the most used tags
          if(!empty($this->value) && \is_array($this->value))
          {
              $topIds = array_merge($topIds, $this->value);
              $topIds = array_keys(array_flip($topIds));
          }

          // Set the default limit for the main query
          $query->setLimit($prefillLimit);

          if(!empty($topIds))
          {
            // Filter the ids to the most used tags and the selected tags
            $preQuery = clone $query;
            $preQuery->whereIn($db->quoteName('a.id'), $topIds);

            $db->setQuery($preQuery);

            try
            {
              $options = $db->loadObjectList();
            }
            catch(\RuntimeException $e)
            {
              return [];
            }

            // Limit the main query to the missing amount of tags
            $count        = \count($options);
            $prefillLimit = $prefillLimit - $count;
            $query->setLimit($prefillLimit);

            // Exclude the already loaded tags from the main query
            if($count > 0)
            {
              $query->whereNotIn($db->quoteName('a.id'), ArrayHelper::getColumn($options, 'value'));
            }
          }
      }

      // Only execute the query if we need more tags not already loaded by the $preQuery query
      if(!$isRemoteSearch || $prefillLimit > 0)
      {
        // Get the options.
        $db->setQuery($query);

        try
        {
          $options = array_merge($options, $db->loadObjectList());
        }
        catch (\RuntimeException $e)
        {
          return [];
        }
      }

      // Merge any additional options in the XML definition.
      $options = array_merge(parent::getOptions(), $options);

      return $options;
    }

    /**
     * Add "-" before nested tags, depending on level
     *
     * @param   array  &$options  Array of tags
     *
     * @return  array  The field option objects.
     *
     * @since   3.1
     */
    protected function prepareOptionsNested(&$options)
    {
      if($options)
      {
        foreach($options as &$option)
        {
          $repeat       = (isset($option->level) && $option->level - 1 >= 0) ? $option->level - 1 : 0;
          $option->text = str_repeat('- ', $repeat) . $option->text;
        }
      }

      return $options;
    }

    /**
     * Determine if the field has to be tagnested
     *
     * @return  boolean
     *
     * @since   3.1
     */
    public function isNested()
    {
      if($this->isNested === null)
      {
        // If mode="nested" || ( mode not set & config = nested )
        if(
            isset($this->element['mode']) && (string) $this->element['mode'] === 'nested' ||
            !isset($this->element['mode']) && $this->comParams->get('tag_field_ajax_mode', 1) == 0
          )
        {
          $this->isNested = true;
        }
      }

      return $this->isNested;
    }

    /**
     * Determines if the field allows or denies custom values
     *
     * @return  boolean
     */
    public function allowCustom()
    {
      if($this->element['custom'] && \in_array((string) $this->element['custom'], ['0', 'false', 'deny']))
      {
          return false;
      }

        // Get access service
          $comp = Factory::getApplication()->bootComponent('com_joomgallery');
          $comp->createAccess();
        $acl = $comp->getAccess();

      return $acl->checkACL('core.create', 'com_joomgallery.tag');
    }

    /**
     * Check whether need to enable AJAX search
     *
     * @return  boolean
     *
     * @since   4.0.0
     */
    public function isRemoteSearch()
    {
        if($this->element['remote-search'])
        {
            return !\in_array((string) $this->element['remote-search'], ['0', 'false', '']);
        }

        //return $this->comParams->get('tag_field_ajax_mode', 1) == 1;
        return true;
    }
}
