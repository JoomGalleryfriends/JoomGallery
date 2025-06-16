<?php
/**
 ******************************************************************************************
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later                          **
 *****************************************************************************************/

// created by example of https://www.dionysopoulos.me/book/com-cli.html
// code of commands (classes) live in /administrator/components/com_joomgallery/src/CliCommand

namespace JoomGallery\Plugin\Console\Joomconsole\Extension;

\defined('_JEXEC') or die;

use Joomgallery\Component\Joomgallery\Administrator\CliCommand\Category;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\CategoryAdd;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\CategoryList;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\CategoryParams;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\Config;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ConfigDynprocessing;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ConfigGet;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ConfigList;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ConfigSet;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\Image;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ImageList;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ImageMetadata;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ImageParams;
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

/**
 * System plugin integrating JoomGallery commands to use from the console
 *
 * @package JoomGallery
 * @since   4.1.0
 */
class JoomgalleryConsole extends CMSPlugin implements SubscriberInterface
{
  use MVCFactoryAwareTrait;

  /**
   * Global application object
   *
   * @var array of comman d class definition
   *
   * @since   4.1.0
   */
  private static $commands = [
    Category::class,
//    CategoryAdd::class, // test ???
    CategoryList::class,
    CategoryParams::class,
    Config::class,
    ConfigDynprocessing::class,
    ConfigGet::class,
    ConfigList::class,
    ConfigSet::class,
    Image::class,
    ImageList::class,
    ImageMetadata::class,
    ImageParams::class,
    // CategoryAdd::class,
  ];

  /**
   * Load the language file on instantiation.
   *
   * @var    boolean
   *
   * @since 4.1.0
   */
  protected $autoloadLanguage = true;

  /**
   * load language on init
   *
   * @var    boolean
   *
   * @since  4.1.0
   */
  public function init(): void
  {
    $this->loadLanguage();
  }

  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return array
   *
   * @since   4.0.0
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
    ];
  }

  /**
   * load command classes and add valid ones
   *
   * @return void
   *
   * @since   4.0.0
   */
  public function registerCLICommands(ApplicationEvent $event): void
  {
    // all commands are class definitions
    foreach (self::$commands as $commandFQN)
    {
      try
      {
        if (!class_exists($commandFQN))
        {
          continue;
        }

        // create command (class)
        $command = new $commandFQN();

        if (method_exists($command, 'setMVCFactory'))
        {
          $command->setMVCFactory($this->getMVCFactory());
        }

        // tell the command
        $this->getApplication()->addCommand($command);
      }
      catch (\Throwable $e)
      {
        print ($commandFQN . ': error ' . $e->getMessage());
        continue;
      }
    }
  }

}

