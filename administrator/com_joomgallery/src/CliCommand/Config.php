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

class Config extends AbstractCommand
{
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:config';

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
    $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'configuration ID');
    $this->addOption('max_line_length', null, InputOption::VALUE_OPTIONAL, 'trim lenght of variable for item keeps in one line');

    $help = "<info>%command.name%</info> list variables of one configuration
  Usage: <info>php %command.full_name% </info>
    * You may specify an ID of the configuration with the <info>--id<info> option. Otherwise, it will be '1'
    * You may restrict the value string length using the <info>--max_line_length</info> option. A result line that is too long will confuse the output lines
  ";
    $this->setDescription(Text::_('List all variables of selected joomgallery configuration'));
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
    $this->ioStyle->title('JoomGallery Configuration');

    $configId        = $input->getOption('id') ?? '1';
    $max_line_length = $input->getOption('max_line_length') ?? null;

    $configurationAssoc = $this->getItemAssocFromDB($configId);

    if (empty ($configurationAssoc))
    {
      $this->ioStyle->error("The configuration id '" . $configId . "' is invalid, No configuration found matching your criteria!");

      return Command::FAILURE;
    }

    $strConfigurationAssoc = $this->assoc2DefinitionList($configurationAssoc, $max_line_length);

    // ToDo: Use horizontal table again ;-)
    foreach ($strConfigurationAssoc as $value)
    {
      if (!\is_array($value))
      {
        throw new \InvalidArgumentException('Value should be an array, string, or an instance of TableSeparator.');
      }

      $headers[] = key($value);
      $row[]     = current($value);
    }

    $this->ioStyle->horizontalTable($headers, [$row]);

    return Command::SUCCESS;
  }

  /**
   * Retrieves extension list from DB
   *
   * @return array
   *
   * @since  4.0.X
   */
  private function getItemAssocFromDB(string $configId): array|null
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);
    $query
      ->select('*')
      ->from('#__joomgallery_configs')
      ->where($db->quoteName('id') . ' = ' . (int) $configId);

    $db->setQuery($query);
    $configurationAssoc = $db->loadAssoc();

    return $configurationAssoc;
  }

  /**
   * trim length of each value in array $configurationAssoc to max_len
   *
   * @param   array  $configurationAssoc
   * @param          $max_len
   *
   * @return array
   *
   * @since version
   */
  private function assoc2DefinitionList(array $configurationAssoc, $max_len = 70)
  {
    $items = [];

    if (empty($max_len))
    {
      $max_len = 70;
    }

    foreach ($configurationAssoc as $key => $value)
    {
      $items[] = [$key => mb_strimwidth((string) $value, 0, $max_len, '...')];
    }

    return $items;
  }

}
