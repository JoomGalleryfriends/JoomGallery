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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Image extends AbstractCommand
{
//  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:image';

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

    // ToDo: Full with all items automatically

    $this->addOption('id', null, InputOption::VALUE_REQUIRED, 'image ID');
    $this->addOption('max_line_length', null, InputOption::VALUE_OPTIONAL, 'trim lenght of variable for item keeps in one line');
    //$this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'image ID');

    $help = "<info>%command.name%</info> lists variables of one image
  Usage: <info>php %command.full_name%</info>
    * You need to give a id of image using the <info>--id</info> option. Otherwisi ti will be requested
    * You may restrict the value sting length using the <info>--max_line_length</info> option. A result line that is too long will confuse the output lines
  "
    ;
    $this->setDescription(Text::_('List all variables of a joomgallery image'));
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
    $this->ioStyle->title('JoomGallery Image');

    $imageId = $input->getOption('id') ?? '';
    $max_line_length = $input->getOption('max_line_length') ?? null;

    if (empty ($imageId)){
      $this->ioStyle->error("The image id '" . $imageId . "' is invalid (empty) !");

      return Command::FAILURE;
    }

    $imageAssoc = $this->getItemAssocFromDB($imageId);

    if (empty ($imageAssoc)){
      $this->ioStyle->error("The image id '" . $imageId . "' is invalid, No image found matching your criteria!");

      return Command::FAILURE;
    }

//    echo 'imageAssoc: ' . json_encode($imageAssoc, JSON_UNESCAPED_SLASHES) . "\n" . "\n";
//    echo 'imageAssoc count: ' . count($imageAssoc) . "\n\n";
//    echo '---------------------------' . "\n";

    $strImageAssoc = $this->assoc2DefinitionList($imageAssoc, $max_line_length);

//    echo 'strImageAssoc: ' . json_encode($strImageAssoc, JSON_UNESCAPED_SLASHES) . "\n" . "\n";

    // ToDo: Use horizontal table again ;-)
    foreach ($strImageAssoc as $value) {
//      if (\is_string($value)) {
//        $headers[] = new TableCell($value, ['colspan' => 2]);
//        $row[] = null;
//        continue;
//      }
      if (!\is_array($value)) {
        throw new InvalidArgumentException('Value should be an array, string, or an instance of TableSeparator.');
      }

      $headers[] = key($value);
      $row[] = current($value);
    }

    $this->ioStyle->horizontalTable($headers, [$row]);

    return Command::SUCCESS;
  }

  /**
   * Retrieves extension list from DB
   *
   * @return array
   *
   * @since 4.0.0
   */
  private function getItemAssocFromDB(string $imageId): array | null
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);
    $query
      ->select('*')
      ->from('#__joomgallery');

      $query->where($db->quoteName('id') . ' = ' . (int) $imageId);

    $db->setQuery($query);
    $imageAssoc = $db->loadAssoc();

    return $imageAssoc;
  }


  private function assoc2DefinitionList(array $imageAssoc, $max_len = 70)
  {
    $items = [];

    if(empty($max_len)){
      $max_len = 70;
    }

//    $count = 0;
    foreach ($imageAssoc as $key => $value) {
//      $count++;
//      if ($count > 8) {
//        break;
//      }

//      echo '$key: ' . json_encode($key, JSON_UNESCAPED_SLASHES) . "\n" . "\n";
//      echo '$value: ' . json_encode($key, JSON_UNESCAPED_SLASHES) . "\n" . "\n";

//      echo '[' . $count . '] ' . "key: " . $key . " value: " . $value . "\n";
//      $items[$key] = (string) $value;
      //$items[] = $key => (string) $value;
      $items[] = [$key => mb_strimwidth((string) $value, 0, $max_len,'...')];
      //$items[] = [[$key => (string) $value]];
    }

    return $items;
  }

}

