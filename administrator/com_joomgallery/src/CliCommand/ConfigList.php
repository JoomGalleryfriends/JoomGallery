<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigList extends AbstractCommand
{
//  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:config:list';

  /**
   * @var   SymfonyStyle
   */
  private $ioStyle;

  /**
   * @var   InputInterface
   */
  private $cliInput;

  /**
   * Instantiate the command.
   *
   * @param   DatabaseInterface  $db  Database connector
   *
   * @since  4.0.X
   */
//  public function __construct(DatabaseInterface $db)
  public function __construct()
  {
    parent::__construct();

    // $db = $this->getDatabase();
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $this->setDatabase($db);
  }

  /**
   * Configure the IO.
   *
   * @param   InputInterface   $input   The input to inject into the command.
   * @param   OutputInterface  $output  The output to inject into the command.
   *
   * @return  void
   */
  private function configureIO(InputInterface $input, OutputInterface $output)
  {
    $this->cliInput = $input;
    $this->ioStyle  = new SymfonyStyle($input, $output);
  }

  /**
   * Initialise the command.
   *
   * @return  void
   *
   * @since  4.0.X
   */
  protected function configure(): void
  {
//    $this->setDescription(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_DESC'));
//    $this->setHelp(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_HELP'));
//
//    $this->addOption('search', 's', InputOption::VALUE_OPTIONAL, Text::_('COM_JOOMGALLERY_CLI_CONFIG_SEARCH'));

    // ToDo: Full with all items automatically

//    $this->addOption('owner', null, InputOption::VALUE_OPTIONAL, 'user ID (created_by)');
//    $this->addOption('created', null, InputOption::VALUE_OPTIONAL, 'created_by');
    $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'configuration set id');

//    // ToDo: option to limit by user (owner), ?parent ...
//    $this->addOption('owner', null, InputOption::VALUE_OPTIONAL, 'username (created_by)');
//     		\nYou may filter on the user of category using the <info>--owner</info> option:
    // $this->addOption('search', 's', InputOption::VALUE_OPTIONAL, Text::_('COM_JOOMGALLERY_CLI_CONFIG_SEARCH'));

//    $help = "<info>%command.name%</info>YYY will list all joomgallery images
//  Usage: <info>php %command.full_name%</info>
//    * You may filter on the user id of image using the <info>--owner</info> option.
//    * You may filter on created_by of image using the <info>--created</info> option.
//    * You may filter on the category id of image using the <info>--category</info> option.
//    Example: <info>php %command.full_name% --created_by=14</info>"
//    ;
    $help = "<info>%command.name%</info>will list all joomgallery configurations (joomgallery table)
  Usage: <info>php %command.full_name%</info>
    * You may filter on the configuration id  using the <info>--id</info> option.
    Example: <info>php %command.full_name% --created_by=14</info>";
    $this->setDescription(Text::_('List all joomgallery configurations'));
    $this->setHelp($help);
  }


  /**
   * @inheritDoc
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    // Configure the Symfony output helper
    $this->configureIO($input, $output);
//    $this->ioStyle->title(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_DESC'));
    $this->ioStyle->title('JoomGallery Configuration list (joomgallery table)');


    $id      = $input->getOption('id') ?? '';
    $configs = $this->getItemsFromDB($id);


    // If no configs are found show a warning and set the exit code to 1.
    if (empty($configs))
    {
      $this->ioStyle->warning('No configs found matching your criteria');

      return 1;
    }

    // Reshape the configs into something humans can read.
    $configs = array_map(
      function (object $item): array {
        return [
          $item->id,
          $item->title,
          $item->published ? Text::_('JYES') : Text::_('JNO'),
          $item->note,
          $item->group_id,

          $item->created_by,
          $item->modified_by,

          $item->jg_filesystem,
          $item->jg_imagetypes,
          $item->jg_pathftpupload,

          // $item->,
          // $item->,
          // $item->,

        ];
      },
      $configs
    );

    // Display the configs in a table and set the exit code to 0
    $this->ioStyle->table(
      [
        'ID', 'Title', 'Published', 'Note', 'Group ID', 'Created by', 'Modified by', 'Modified', 'Filesystem', 'iagetypes', 'pathftpupload',
      ],
      $configs
    );

    return Command::SUCCESS;
  }

  /**
   * Retrieves extension list from DB
   *
   * @return array
   *
   * @since  4.0.X
   */
  private function getItemsFromDB(string $d): array
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);
    $query
      ->select('*')
      ->from('#__joomgallery_configs');

//    if ( ! empty ($id) )
//    {
//      $query->where($db->quoteName('created_by') . ' = ' . (int) $userId);
//    }

    if (!empty ($id))
    {
      $query->where($db->quoteName('id') . ' = ' . (int) $id);
    }

    $db->setQuery($query);
    $configurations = $db->loadObjectList();

    return $configurations;
  }


}

