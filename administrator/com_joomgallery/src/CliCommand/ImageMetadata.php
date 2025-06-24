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

class ImageMetadata extends AbstractCommand
{
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:image:metadata';

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
    $this->addOption('id', null, InputOption::VALUE_REQUIRED, 'image ID');

    $help = "<info>%command.name%</info> displays parameters of one image
  Usage: <info>php %command.full_name%</info>
    * You must specify an ID of the image with the <info>--id<info> option. Otherwise, it will be requested
  ";
    $this->setDescription(Text::_('List all variables of a joomgallery image'));
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
    $this->ioStyle->title('JoomGallery Image Meta Data');

    $imageId = $input->getOption('id') ?? '';

    if (empty ($imageId))
    {
      $this->ioStyle->error("The image id '" . $imageId . "' is invalid (empty) !");

      return Command::FAILURE;
    }

    $jsonParams = $this->getParamsAsJsonFromDB($imageId);

    // If no params returned  show a warning and set the exit code to 1.
    if (empty ($jsonParams))
    {

      $this->ioStyle->error("The image id '" . $imageId . "' is invalid or parameters are empty !");

      return Command::FAILURE;
    }

    // pretty print json data

    $encoded    = json_decode($jsonParams);
    $jsonParams = json_encode($encoded, JSON_PRETTY_PRINT);

    $this->ioStyle->writeln($jsonParams);

    return Command::SUCCESS;
  }

  /**
   * Retrieves extension list from DB
   *
   * @return array
   *
   * @since  4.0.X
   */
  private function getParamsAsJsonFromDB(string $imageId): string
  {
    $sParams = '';
    $db      = $this->getDatabase();
    $query   = $db->getQuery(true);
    $query
      ->select('imgmetadata')
      ->from('#__joomgallery')
      ->where($db->quoteName('id') . ' = ' . (int) $imageId);

    $db->setQuery($query);
    $sParams = $db->loadResult();

    return $sParams;
  }

  /**
   * Trim length of each value in array $imageAssoc to max_len
   *
   * @param   array  $imageAssoc  in data as association key => val
   * @param          $max_len
   *
   * @return array
   *
   * @since version
   */
  private function assoc2DefinitionList(array $imageAssoc, $max_len = 70)
  {
    $items = [];

    if (empty($max_len))
    {
      $max_len = 70;
    }

    foreach ($imageAssoc as $key => $value)
    {
      $items[] = [$key => mb_strimwidth((string) $value, 0, $max_len, '...')];
    }

    return $items;
  }

}

