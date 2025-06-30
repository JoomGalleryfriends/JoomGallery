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

use InvalidArgumentException;
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

class Category extends AbstractCommand
{
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:category';

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
    // ToDo: Option Full with all items automatically

    $this->addOption('id', null, InputOption::VALUE_REQUIRED, 'category ID');
    $this->addOption('max_line_length', null, InputOption::VALUE_OPTIONAL, 'trim lenght of variable for item keeps in one line');

    $help = "<info>%command.name%</info> list variables of one category
  Usage: <info>php %command.full_name%</info>
    * You must specify an ID of the category with the <info>--id<info> option. Otherwise, it will be requested
    * You may restrict the value string length using the <info>--max_line_length</info> option. A result line that is too long will confuse the output lines
  ";
    $this->setDescription(Text::_('List all variables of a category'));
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
    $this->ioStyle->title('JoomGallery Category');

    $categoryId      = $input->getOption('id') ?? '';
    $max_line_length = $input->getOption('max_line_length') ?? null;

    if (empty ($categoryId))
    {
      $this->ioStyle->error("The category id '" . $categoryId . "' is invalid (empty) !");

      return Command::FAILURE;
    }

    $categoryAssoc = $this->getItemAssocFromDB($categoryId);

    // If no categories are found show a warning and set the exit code to 1.
    if (empty($categoryAssoc))
    {
      $this->ioStyle->error("The category id '" . $categoryId . "' is invalid, No category found matching your criteria!");

      return Command::FAILURE;
    }

    $strCategoryAssoc = $this->assoc2DefinitionList($categoryAssoc, $max_line_length);

    // ToDo: Use horizontal table again ;-)
    foreach ($strCategoryAssoc as $value)
    {
      if (!\is_array($value))
      {
        throw new InvalidArgumentException('Value should be an array, string, or an instance of TableSeparator.');
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
  private function getItemAssocFromDB(string $categoryId): array
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);
    $query
      ->select('*')
      ->from('#__joomgallery_categories')
      ->where($db->quoteName('id') . ' = ' . (int) $categoryId);

    $db->setQuery($query);
    $categoryAssoc = $db->loadAssoc();

    return $categoryAssoc;
  }

  /**
   * Trim length of each value in array $categoryAssoc to max_len
   *
   * @param   array  $categoryAssoc  in data as association key => val
   * @param          $max_len
   *
   * @return array
   *
   * @since version
   */
  private function assoc2DefinitionList(array $categoryAssoc, $max_len = 70)
  {
    $items = [];

    if (empty($max_len))
    {
      $max_len = 70;
    }

    foreach ($categoryAssoc as $key => $value)
    {
      $items[] = [$key => mb_strimwidth((string) $value, 0, $max_len, '...')];
    }

    return $items;
  }

}

