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

class ImageList extends AbstractCommand
{
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:image:list';

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
    $this->addOption('owner', null, InputOption::VALUE_OPTIONAL, 'user ID (created_by)');
    $this->addOption('created', null, InputOption::VALUE_OPTIONAL, 'created_by');
    $this->addOption('category', null, InputOption::VALUE_OPTIONAL, 'category id');

    $help = "<info>%command.name%</info>will list all joomgallery images
  Usage: <info>php %command.full_name%</info>
    * You may filter on the user id of image using the <info>--owner</info> option.
    * You may filter on created_by of image using the <info>--created</info> option.
    * You may filter on the category id of image using the <info>--category</info> option.
    Example: <info>php %command.full_name% --created_by=14</info>";
    $this->setDescription(Text::_('List all joomgallery images'));
    $this->setHelp($help);
  }

  /**
   * Internal function to execute the command.
   *
   * @param   InputInterface   $input   The input to inject into the command.
   * @param   OutputInterface  $output  The output to inject into the command.
   *
   * @return  integer  The command exit code
   *
   * @since   4.0.0
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    // Configure the Symfony output helper
    $this->configureIO($input, $output);

    $this->ioStyle->title('JoomGallery Image list');

    $created_by_id = $input->getOption('created') ?? '';
    if (empty ($created_by_id))
    {
      $created_by_id = $input->getOption('owner') ?? '';
    }

    $cat_id = $input->getOption('category') ?? '';

    $images = $this->getItemsFromDB($created_by_id, $cat_id);

    // If no images are found show a warning and set the exit code to 1.
    if (empty($images))
    {
      $this->ioStyle->warning('No images found matching your criteria');

      return 1;
    }

    // Reshape the images into something humans can read.
    $images = array_map(
      function (object $item): array {
        return [
          $item->id,
          $item->title,
          $item->published ? Text::_('JYES') : Text::_('JNO'),
          $item->hidden ? Text::_('JYES') : Text::_('JNO'),
          $item->created_by,
          $item->created_time,
          $item->modified_by,
          $item->modified_time,
          $item->catid, // JGLOBAL_ROOT
          // $item->,

        ];
      },
      $images
    );

    // Display the images in a table and set the exit code to 0
    $this->ioStyle->table(
      [
        'ID', 'Title', 'Published', 'Hidden', 'Created/Owner', 'Created', 'Modified by', 'Modified', 'Category',
      ],
      $images
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
  private function getItemsFromDB(string $userId, string $cat_id): array
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);
    $query
      ->select('*')
      ->from('#__joomgallery');

    if (!empty ($userId))
    {
      $query->where($db->quoteName('created_by') . ' = ' . (int) $userId);
    }

    if (!empty ($cat_id))
    {
      $query->where($db->quoteName('catid') . ' = ' . (int) $cat_id);
    }

    $db->setQuery($query);
    $images = $db->loadObjectList();

    return $images;
  }

}

