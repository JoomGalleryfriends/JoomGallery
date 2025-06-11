<?php

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

class ConfigDynprocessing extends AbstractCommand
{
//  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:config:dynamicprocessing';

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

    $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'configuration ID');

    $help = "<info>%command.name%</info> displays config:Dynprocessing value as it is shortened otherwise
  Usage: <info>php %command.full_name%</info>
    * You may specify an ID of the configuration with the <info>--id<info> option. Otherwise, it will be '1'
  ";
    $this->setDescription(Text::_('List all variables of a joomgallery config'));
    $this->setHelp($help);
  }


  /**
   * @inheritDoc
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    // Configure the Symfony output helper
    $this->configureIO($input, $output);
    $this->ioStyle->title('JoomGallery dynamicprocessing Data');

    $configId = $input->getOption('id') ?? '1';

    $jsonParams = $this->getParamsAsJsonFromDB($configId);

    // If no params returned  show a warning and set the exit code to 1.
    if (empty ($jsonParams))
    {

      $this->ioStyle->error("The config id '" . $configId . "' is invalid or parameters are empty !");

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
  private function getParamsAsJsonFromDB(string $configId): string
  {
    $sParams = '';
    $db      = $this->getDatabase();
    $query   = $db->getQuery(true);
    $query
      ->select('jg_dynamicprocessing')
      ->from('#__joomgallery_configs')
      ->where($db->quoteName('id') . ' = ' . (int) $configId);

    $db->setQuery($query);
    $sParams = $db->loadResult();

    return $sParams;
  }

  private function assoc2DefinitionList(array $configAssoc, $max_len = 70)
  {
    $items = [];

    if (empty($max_len))
    {
      $max_len = 70;
    }

//    $count = 0;
    foreach ($configAssoc as $key => $value)
    {
//      $count++;
//      if ($count > 8) {
//        break;
//      }

//      echo '$key: ' . json_encode($key, JSON_UNESCAPED_SLASHES) . "\n" . "\n";
//      echo '$value: ' . json_encode($key, JSON_UNESCAPED_SLASHES) . "\n" . "\n";

//      echo '[' . $count . '] ' . "key: " . $key . " value: " . $value . "\n";
//      $items[$key] = (string) $value;
      //$items[] = $key => (string) $value;
      $items[] = [$key => mb_strimwidth((string) $value, 0, $max_len, '...')];
      //$items[] = [[$key => (string) $value]];
    }

    return $items;
  }

}

