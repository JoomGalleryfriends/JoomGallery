<?php

namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigSet extends AbstractCommand
{
//  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:config:set';

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

    $this->addArgument('option', InputArgument::REQUIRED, 'Name of the option');
    $this->addArgument('value', null, 'Value of the option');
    $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'configuration ID', 1);
    $this->addOption('verify', null, InputOption::VALUE_OPTIONAL, 'configuration ID', false);

    $help = "<info>%command.name%</info> sets the value for a JoomGallery configuration option (Table)
  Usage: <info>php %command.full_name%</info> <option> <value>
    * You may specify an ID of the configuration with the <info>--id<info> option. Otherwise, it will be '1'
    * You may verify the written value with <info>--veryfy=true<info> option. This compares the given option with the resulting table value
		";

    $this->setDescription('Set a value for a configuration option');
    $this->setHelp($help);

  }


  /**
   * @inheritDoc
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    $this->configureIO($input, $output);
    $this->ioStyle->title("Current option in JoomGallery Configuration (table)");

    $option   = $this->cliInput->getArgument('option');
    $value    = $this->cliInput->getArgument('value');
    $configId = $input->getOption('id') ?? '1';
    $veryfyIn = $input->getOption('verify') ?? 'false';

//    if (empty ($configId)){
//      $this->ioStyle->error("The configuration id '" . $configId . "' is invalid (empty) !");
//
//      return Command::FAILURE;
//    }

    // $isDoVerify = true/false, 0/1;
    $isDoVerify = $this->isTrue($veryfyIn);

    $configurationAssoc = $this->getItemAssocFromDB($configId);

    if (empty ($configurationAssoc))
    {
      $this->ioStyle->error("The configuration id '" . $configId . "' is invalid, No configuration found matching your criteria!");

      return Command::FAILURE;
    }

    if (!\array_key_exists($option, $configurationAssoc))
    {
      $this->ioStyle->error("Can't find option *$option* in configuration list");

      return Command::FAILURE;
    }

//    echo 'value: "' . $value . '"' . "\n";
    $sanitizeValue = $this->sanitizeValue($value);
//    echo 'sanitizeValue: "' . $sanitizeValue . '"' . "\n";


    $isUpdated = $this->writeOptionToDB($configId, $option, $sanitizeValue);
    if ($isUpdated)
    {
      $this->ioStyle->success("Configuration set for option: '" . $option . "' value: '" . $value . "'");
    }
    else
    {
      $this->ioStyle->error("Can't update configuration option: '" . $option . "' for value: '" . $value . "'");

      return Command::FAILURE;
    }

    if ($isDoVerify)
    {
      $verifiedValue = $this->getOptionFromDB($configId, $option);

      if ($verifiedValue != $value)
      {

        $this->ioStyle->error("Configuration set for "
          . "option: '" . $option . "' in value: '" . $value . "'" . " results in table value: '" . $verifiedValue . "'");
      }
      else
      {
        $this->ioStyle->note('Written value confirmed');
      }
    }

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
   * Sanitize the options array for boolean
   *
   * @param   array  $option  Options array
   *
   * @return array
   *
   * @since  4.0.X
   */
  public function sanitizeValue($value)
  {
    switch ($value)
    {
      case $value === 'false':
        $value = false;
        break;
      case $value === 'true':
        $value = true;
        break;
      case $value === 'null':
        $value = null;
        break;

    }

    return $value;
  }

  private function writeOptionToDB(mixed $configId, string $option, $value): bool
  {
    $isUpdated = false;

//    echo 'writeOptionToDB:value: "' . $value . '"' . "\n";
//    echo 'writeOptionToDB:option: "' . $value . '"' . "\n";

    try
    {
      $db    = $this->getDatabase();
      $query = $db->getQuery(true);

//      $query->update($db->quoteName('#__finder_logging'))
//        ->set('hits = (hits + 1)')
//        ->where($db->quoteName('md5sum') . ' = ' . $db->quote($entry->md5sum));
//      $db->setQuery($query);
//      $db->execute();

      $query
        ->update($db->quoteName('#__joomgallery_configs'))
        ->set($db->quoteName($option) . ' = ' . $db->quote($value))
        //->set($db->quote($option) . ' = ' . $db->quote($value))
        //->set($db->quote('note')  . ' = ' .  'xxxxx')
        ->where($db->quoteName('id') . ' = ' . (int) $configId);
//        ->where($db->quoteName('id') . ' = ' . $db->quote((int) $configId));

//      echo 'writeOptionToDB: 1' . "\n";
//      echo '---------------------------' . "\n";
//      echo($query->__toString()) . "\n";
//      echo '---------------------------' . "\n";
//      echo 'writeOptionToDB: 1B' . "\n";

      $db->setQuery($query);

//      echo 'writeOptionToDB: 2';
      $db->execute();

//      echo 'writeOptionToDB: 3';
      $isUpdated = true;
    }
    catch (\Exception $e)
    {

      $this->ioStyle->error(
        Text::sprintf(
          'Cannot update option "' . $option . '" to database for value "' . $value . '", verify that you specified the correct database details \n%s',
          $e->getMessage()
        )
      );

    }

    return $isUpdated;
  }

  /**
   *
   *
   * @return array
   *
   * @since  4.0.X
   */
  private function getOptionFromDB(string $configId, string $option)
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);

    $query
      ->select($db->quoteName($option))
      ->from('#__joomgallery_configs')
      ->where($db->quoteName('id') . ' = ' . (int) $configId);

//    echo "\n";
//    echo '---------------------------' . "\n";
//    echo($query->__toString()) . "\n";
//    echo '---------------------------' . "\n";
//
//    echo 'getOptionFromDB: 1' . "\n";
    $db->setQuery($query);

//    echo 'getOptionFromDB: 2' . "\n";
    $value = $db->loadResult();

//    echo 'getOptionFromDB  3: "' . $option . "' value: '" . (string) json_encode($value) . "'" . "\n";
//    echo 'getOptionFromDB  4: "' . $option . "' value: '" . (string) $value . "'" . "\n";

    return $value;
  }

  // $isDoVerify = true/false, 0/1
  private function isTrue(mixed $veryfyIn)
  {
    $isDoVerify = false;

    if (!empty ($veryfyIn))
    {

      if (strtolower($veryfyIn) == 'true')
      {
        $isDoVerify = true;
      }

      if ($veryfyIn == '1')
      {
        $isDoVerify = true;
      }
    }

    return $veryfyIn;
  }


}

