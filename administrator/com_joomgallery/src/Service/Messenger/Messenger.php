<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MessengerInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Uri\Uri;

/**
 * Template Messenger Base Class
 *
 * Provides methods to send template based messages.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class Messenger implements MessengerInterface
{
  use ServiceTrait;

  /**
   * Number of messanges successfully sent
   *
   * @var integer
   */
  protected $sent = 0;

  /**
   * Language the message is written
   *
   * @var Language
   */
  protected $language = null;

  /**
   * Template id to use
   *
   * @var Language
   */
  protected $template_id = 'com_joomgallery.newimage';

  /**
   * List with variables available in the template
   *
   * @var array
   */
  public $data = [];

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    $this->language = $this->app->getLanguage();
    $this->addTemplateData(['sitename' => $this->app->get('sitename'), 'siteurl' => Uri::root()]);
  }

  /**
   * Method to select the template to be used for the message
   *
   * @param   string   $id   The id of the template to be used
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function selectTemplate(string $id)
  {
    if(\is_string($id))
    {
      $this->template_id = $id;
    }
  }

  /**
   * Method to select the language of the message
   *
   * @param   string   $tag   The id of the template to be used
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function selectLanguage(string $tag)
  {
    $this->language = Factory::getContainer()->get(LanguageFactoryInterface::class)->createLanguage($tag);
  }

  /**
   * Method to add one ore more variables to be used in the template
   *
   * @param   mixed   $data   An array of key value pairs with variables to be used in the template
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function addTemplateData($data)
  {
    if(\is_array($data))
    {
      $this->data = array_merge($this->data, $data);
    }
    else
    {
      array_push($this->data, $data);
    }
  }

    /**
     * Replace tags with their values recursively
     *
     * @param   string  $text  The template to process
     * @param   array   $tags  An associative array to replace in the template
     *
     * @return  string  Rendered mail template
     *
     * @since   4.0.0
     */
    protected function replaceTags($text, $tags)
    {
      foreach($tags as $key => $value)
      {
        if(\is_array($value))
        {
          $matches = [];

          if(preg_match_all('/{' . strtoupper($key) . '}(.*?){\/' . strtoupper($key) . '}/s', $text, $matches))
          {
            foreach($matches[0] as $i => $match)
            {
              $replacement = '';

              foreach($value as $subvalue)
              {
                if(\is_array($subvalue))
                {
                  $replacement .= $this->replaceTags($matches[1][$i], $subvalue);
                }
              }

              $text = str_replace($match, $replacement, $text);
            }
          }
        }
        else {
            $text = str_replace('{' . strtoupper($key) . '}', $value, $text);
        }
      }

      return $text;
    }
}
