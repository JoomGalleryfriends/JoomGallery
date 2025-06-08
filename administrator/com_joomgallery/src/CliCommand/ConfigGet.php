<?php
namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
//use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigGet extends AbstractCommand
{
//  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:config:get';

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
   * @since   4.0.0
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
   * @since   4.0.0
   */
  protected function configure(): void
  {
//    $this->setDescription(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_DESC'));
//    $this->setHelp(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_HELP'));
//
//    $this->addOption('search', 's', InputOption::VALUE_OPTIONAL, Text::_('COM_JOOMGALLERY_CLI_CONFIG_SEARCH'));

    $this->addArgument('option', InputArgument::REQUIRED, 'Name of the option');
    $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'configuration ID');

    $help = "<info>%command.name%</info> displays the current value of JoomGallery configuration option (Table)
  Usage: <info>php %command.full_name%</info> <option>
    * You may specify an ID of the configuration with the <info>--id<info> option. Otherwise, it will be '1'
		"		;

    $this->setDescription('Display the current value of a configuration option');
    $this->setHelp($help);

  }


  /**
   * @inheritDoc
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    $this->configureIO($input, $output);
    $this->ioStyle->title("Current option in JoomGallery Configuration (table)");

    $option = $this->cliInput->getArgument('option');
    $configId = $input->getOption('id') ?? '1';

//    if (empty ($configId)){
//      $this->ioStyle->error("The configuration id '" . $configId . "' is invalid (empty) !");
//
//      return Command::FAILURE;
//    }

    $configurationAssoc = $this->getItemAssocFromDB($configId);

    if (empty ($configurationAssoc)){
      $this->ioStyle->error("The configuration id '" . $configId . "' is invalid, No configuration found matching your criteria!");

      return Command::FAILURE;
    }

    if( ! \array_key_exists($option, $configurationAssoc) ){
      $this->ioStyle->error("Can't find option *$option* in configuration list");

      return Command::FAILURE;
    }

    $value = $this->formatConfigValue($configurationAssoc[$option]);

    $this->ioStyle->table(['Option', 'Value'], [[$option, $value]]);

    return Command::SUCCESS;
  }


  /**
   * Retrieves extension list from DB
   *
   * @return array
   *
   * @since 4.0.0
   */
  private function getItemAssocFromDB(string $configId): array | null
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
   * Formats the Configuration value
   *
   * @param   mixed  $value  Value to be formatted
   *
   * @return string
   *
   * @since 4.0.0
   */
  protected function formatConfigValue($value): string
  {
    if ($value === false) {
      return 'false';
    }

    if ($value === true) {
      return 'true';
    }

    if ($value === null) {
      return 'Not Set';
    }

    if (\is_array($value)) {
      return json_encode($value);
    }

    if (\is_object($value)) {
      return json_encode(get_object_vars($value));
    }

    return $value;
  }










}

