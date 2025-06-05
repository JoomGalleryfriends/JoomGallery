<?php
/**
 * @package     Sven.Bluege
 * @subpackage  com_eventgallery
 *
 * @copyright   Copyright (C) 2005 - 2019 Sven Bluege All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JoomGallery\Plugin\Console\Joomconsole\Extension;

\defined('_JEXEC') or die;
// \defined('JPATH_PLATFORM') or die;

use Joomgallery\Component\Joomgallery\Administrator\CliCommand\Category;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\CategoryList;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\Image;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\ImageList;
use Joomgallery\Component\Joomgallery\Administrator\CliCommand\Add;
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Console\Loader\WritableLoaderInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\DI\Container;
use Joomla\Event\SubscriberInterface;
use Throwable;

/**
 * Adds commands to the Joomla console
 *
 * @package     Joomla.Plugin
 * @since       2.5
 */
class JoomgalleryConsole extends CMSPlugin implements SubscriberInterface
{
  use MVCFactoryAwareTrait;

  // command classes in folder
  // administrator\components\com_joomgallery\src\CliCommand
  // administrator\components\com_joomgallery\src\CliCommand
  private static $commands = [
    CategoryList::class,
    Category::class,
    ImageList::class,
    Image::class,
    Add::class,
  ];

  protected $autoloadLanguage = true;

  public function init()
  {
    $this->loadLanguage();
  }

  public static function getSubscribedEvents(): array
  {
      return [
        ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
      ];
  }

  //
  public function registerCLICommands(ApplicationEvent $event): void
  {
    // $test = new CategoriesList ();

    foreach (self::$commands as $commandFQN) {
      try {
        if (!class_exists($commandFQN)) {
          continue;
        }

        $command = new $commandFQN();

        if (method_exists($command, 'setMVCFactory')) {
          $command->setMVCFactory($this->getMVCFactory());
        }

        $test = $this->getApplication();

        $this->getApplication()->addCommand($command);
      } catch (Throwable $e) {

        print ($commandFQN . ': error ' . $e->getMessage());
        // $this->ioStyle->writeln($commandFQN . ': error ' . $e->getMessage());

        continue;
      }
    }
  }

}

