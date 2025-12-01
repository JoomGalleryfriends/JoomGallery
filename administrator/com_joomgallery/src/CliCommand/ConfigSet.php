<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

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
   * @since   4.2.0
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
   * @since   4.2.0
   */
  protected function configure(): void
  {
    $this->addArgument('option', InputArgument::REQUIRED, 'Name of the option');
    $this->addArgument('value', null, 'Value of the option');
    $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'configuration ID', 1);
    $this->addOption('verify', null, InputOption::VALUE_OPTIONAL, 'check result from DB with requested', false);

    $help = "<info>%command.name%</info> set the value for a JoomGallery configuration option (Table)
  Usage: <info>php %command.full_name%</info> <option> <value>
    * You may specify an ID of the configuration with the <info>--id<info> option. Otherwise, it will be '1'
    * You may verify the written value with <info>--veryfy=true<info> option. This compares the given option with the resulting table value
		";
    $this->setDescription('Set a value for a configuration option');
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
   * @since   4.2.0
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    $this->configureIO($input, $output);
    $this->ioStyle->title('Set JoomGallery Configuration option (table)');

    $option   = $this->cliInput->getArgument('option');
    $value    = $this->cliInput->getArgument('value');
    $configId = $input->getOption('id') ?? '1';
    $verifyIn = $input->getOption('verify') ?? 'false';

    // $isDoVerify = true/false, 0/1;
    $isDoVerify = $this->isTrue($verifyIn);

    // list of parameter with values
    $configurationAssoc = $this->getItemAssocFromDB($configId);

    if(empty($configurationAssoc))
    {
      $this->ioStyle->error("The configuration id '" . $configId . "' is invalid, No configuration found matching your criteria!");

      return Command::FAILURE;
    }

    // validate option for existence
    if(!\array_key_exists($option, $configurationAssoc))
    {
      $this->ioStyle->error("Can't find option '$option' in configuration list");

      return Command::FAILURE;
    }

    // ToDo: Make it sql save ....

    // Sanitize for boolean. Boolean result is either '1' or '0'
    $sanitizeValue = $this->sanitizeValue($value);

    echo "\$sanitizeValue: '{$sanitizeValue}'" . "\r\n";

    $isUpdated = $this->writeOptionToDB($configId, $option, $sanitizeValue);

    if($isUpdated)
    {
      $this->ioStyle->success("Configuration set for option: '" . $option . "' value: '" . $value . "'");
    }
    else
    {
      $this->ioStyle->error("Can't update configuration option: '" . $option . "' for value: '" . $value . "'");

      return Command::FAILURE;
    }

    $this->ioStyle->note('\$isDoVerify: ' . $isDoVerify);

    if($isDoVerify)
    {
      $verifiedValue = $this->getOptionFromDB($configId, $option);

      if($verifiedValue != $value)
      {
        $this->ioStyle->error(
            'Configuration set for '
            . "option: '" . $option . "' in value: '" . $value . "'" . " results in table value: '" . $verifiedValue . "'"
        );
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
   * @since   4.2.0
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
   * Sanitize the value for boolean. Boolean result is either '1' or '0'
   *
   * @param   array  $option  Options array
   *
   * @return array
   *
   * @since   4.2.0
   */
  private function sanitizeValue($value)
  {
    echo "value in: '{$value}'" . "\r\n";

    $value = $value === 'false' ? 0 : $value;
    $value = $value === 'true' ? 1 : $value;
    // $value = $value === 'null' ? null : $value;

    echo "value out: '" . json_encode($value) . "'" . "\r\n";
    echo "value out: '" . $value . "'" . "\r\n";

    return $value;
  }

  /**
   * Write given configuration value to DB set
   *
   * @param   mixed   $configId
   * @param   string  $option
   * @param           $value
   *
   * @return bool
   *
   * @since   4.2.0
   */
  private function writeOptionToDB(mixed $configId, string $option, $value): bool
  {
    $isUpdated = false;

    try
    {
      $db    = $this->getDatabase();
      $query = $db->getQuery(true);

      $query
        ->update($db->quoteName('#__joomgallery_configs'))
        ->set($db->quoteName($option) . ' = ' . $db->quote($value))
        ->where($db->quoteName('id') . ' = ' . (int) $configId);

      $db->setQuery($query);

      $db->execute();

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
   * read value from database for verifying the set process
   *
   * @return array
   *
   * @since   4.2.0
   */
  private function getOptionFromDB(string $configId, string $option)
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);

    $query
      ->select($db->quoteName($option))
      ->from('#__joomgallery_configs')
      ->where($db->quoteName('id') . ' = ' . (int) $configId);
    $db->setQuery($query);

    $value = $db->loadResult();

    return $value;
  }

  /**
   * Check string input for true (1)
   *
   * @param   mixed  $value
   *
   * @return bool
   *
   * @since   4.2.0
   */
  private function isTrue(mixed $value)
  {
    $isTrue = false;

    if(!empty($value))
    {
      if(strtolower($value) == 'true')
      {
        $isTrue = true;
      }

      if(strtolower($value) == 'on')
      {
        $isTrue = true;
      }

      // ToDo: positive ?
      if($value == '1')
      {
        $isTrue = true;
      }
    }

    return $isTrue;
  }
}
