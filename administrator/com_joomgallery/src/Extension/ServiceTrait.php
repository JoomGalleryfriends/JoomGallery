<?php
/**
  * *********************************************************************************
  *    @package    com_joomgallery                                                 **
  *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
  *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
  *    @license    GNU General Public License version 3 or later                   **
  * *********************************************************************************
  */

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;

/**
  * Trait to implement basic methods
  * for JoomGallery services
  *
  * @since  4.0.0
  */
trait ServiceTrait
{
    /**
      * JoomGallery extension class
      *
      * @var JoomgalleryComponent|null
      */
    protected $component = null;

    /**
      * Current application object
      *
      * @var    CMSApplicationInterface|null
      */
    protected $app = null;

    /**
      * Internal store for dynamic properties.
      *
      * @var array<string, mixed>
      */
    protected array $__data = [];

    /**
      * Sets a default value if not already assigned
      *
      * @param   string  $property  The name of the property.
      * @param   mixed   $default   The default value.
      *
      * @return  mixed
      *
      * @since   4.0.0
      */
    public function def($property, $default = null)
    {
        $value = $this->get($property, $default);

        return $this->set($property, $value);
    }

    /**
      * Returns a property of the object or the default value if the property is not set.
      *
      * @param   string  $property  The name of the property.
      * @param   mixed   $default   The default value.
      *
      * @return  mixed    The value of the property.
      *
      * @since   4.0.0
      */
    public function get(string $property, $default = null)
    {
        // Get real property if exists
        if(property_exists($this, $property) && isset($this->$property))
        {
            return $this->$property;
        }

        // Get dynamic property if exists
        if(\array_key_exists($property, $this->__data) && isset($this->__data[$property]))
        {
            return $this->__data[$property];
        }

        // Return default value as fallback
        return $default;
    }

    /**
      * Modifies a property of the object, creating it if it does not already exist.
      *
      * @param   string  $property  The name of the property.
      * @param   mixed   $value     The value of the property to set.
      *
      * @return  mixed  Previous value of the property.
      *
      * @since   4.0.0
      */
    public function set(string $property, $value = null)
    {
        if(property_exists($this, $property))
        {
            // Set the real property if exists
            $previous        = $this->$property ?? null;
            $this->$property = $value;
        }
        else
        {
            // Set dynamic property
            $previous                = $this->__data[$property] ?? null;
            $this->__data[$property] = $value;
        }

        return $previous;
    }

    /**
      * Returns an associative array of object properties.
      *
      * @param   boolean  $public  If true, returns only the public properties.
      *
      * @return  array
      *
      * @since   4.0.0
      */
    public function getProperties(bool $public = true): array
    {
        $vars = array_merge(get_object_vars($this), $this->__data);

        if($public)
        {
            // Remove all properties starting with an underscore
            foreach($vars as $key => $value)
            {
                if(str_starts_with($key, '_'))
                {
                    unset($vars[$key]);
                }
            }

            // Now remove protected/private props declared in this class or parents
            $reflection = new \ReflectionObject($this);
            do
            {
                $nonPublicProps = $reflection->getProperties(
                    \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE
                );

                foreach($nonPublicProps as $prop)
                {
                    $propName = $prop->getName();

                    if(\array_key_exists($propName, $vars))
                    {
                        unset($vars[$propName]);
                    }
                }
            }
            while($reflection = $reflection->getParentClass());
        }

        return $vars;
    }

    /**
      * Set the object properties based on a named array/hash.
      *
      * @param   mixed  $properties  Either an associative array or another object.
      *
      * @return  boolean
      *
      * @since   4.0.0
      */
    public function setProperties($properties)
    {
        if(\is_array($properties) || \is_object($properties))
        {
            foreach((array) $properties as $k => $v)
            {
                // Use the set function which might be overridden.
                $this->set($k, $v);
            }

            return true;
        }

        return false;
    }

    /**
      * Gets the JoomGallery component object
      *
      * @return  void
      *
      * @since   4.0.0
      */
    public function getComponent()
    {
        $this->component = Factory::getApplication()->bootComponent('com_joomgallery');
    }

    /**
      * Gets the current application object
      *
      * @return  void
      *
      * @since   4.0.0
      */
    public function getApp()
    {
        $this->app = Factory::getApplication();
    }
}
