<?php
namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

defined('_JEXEC') or die;

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

class CategoryList extends AbstractCommand
{
//  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:category:list';

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

    $this->addOption('owner', null, InputOption::VALUE_OPTIONAL, 'user ID (created_by)');
    $this->addOption('created', null, InputOption::VALUE_OPTIONAL, 'created_by');
    $this->addOption('parent', null, InputOption::VALUE_OPTIONAL, 'parent category');

//    // ToDo: option to limit by user (owner), ?parent ...
//    $this->addOption('owner', null, InputOption::VALUE_OPTIONAL, 'username (created_by)');
//     		\nYou may filter on the user of category using the <info>--owner</info> option:
    // $this->addOption('search', 's', InputOption::VALUE_OPTIONAL, Text::_('COM_JOOMGALLERY_CLI_CONFIG_SEARCH'));

    $help = "<info>%command.name%</info>will list all joomgallery categories
  Usage: <info>php %command.full_name%</info>
    * You may filter on the user id of category using the <info>--owner</info> option.
    * You may filter on created_by of category using the <info>--created</info> option.
    * You may filter on the parent id of category using the <info>--parent_id</info> option.
    Example: <info>php %command.full_name% --created_by=291</info>"
    ;
    $this->setDescription(Text::_('List all joomgallery categories'));
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
    $this->ioStyle->title('JoomGallery Category list');


////--- First try ---------------------------------------------------------------------------
//    // Get the categories, using the backend model
//    /** @var Joomgallery\Component\Joomgallery\Administrator\Model\CategoriesModel;  */
//    $categoriesModel = $this->getMVCFactory()->createModel('Categories', 'Administrator');
//
//    //--- assign option ----------------------------
//
////    $search = $input->getOption('search') ?? null;
////    if ($search)
////    {
////      $categoriesModel->setState('filter.search', $search);
////    }
//
//    $owner = $input->getOption('owner') ?? null;
//
//    if ($owner) // created_by
//    {
//      $categoriesModel->setState('filter.created_by', $owner);
//      // rename
//      // not matching option error
//      // $input->setOption('created_by', $owner);
//
//      $app = $this->getApplication();
//      $input = $app->getInput();
//      // $input->set('created_by', $owner);
//
//      //$filter = $input->get('filter');
//
////      $filter = $input->getFilter();
////
////      $filter->set('filter.created_by', $owner);
//    }
//
//    $categories = $categoriesModel->getItems();

    $created_by_id = $input->getOption('created') ?? '';
    if ( empty ($created_by_id) )
    {
      $created_by_id = $input->getOption('owner') ?? '';
    }

    $parent_id = $input->getOption('parent') ?? '';

    $categories = $this->getItemsFromDB($created_by_id, $parent_id);


    // If no categories are found show a warning and set the exit code to 1.
    if (empty($categories))
    {
      $this->ioStyle->warning('No categories found matching your criteria');

      return Command::FAILURE;
    }

    // Reshape the categories into something humans can read.
    $categories = array_map(
      function (object $item): array
      {
        return [
          $item->id,
          $item->title,
          $item->published ? Text::_('JYES') : Text::_('JNO'),
          $item->hidden ? Text::_('JYES') : Text::_('JNO'),
          $item->created_by,
          $item->created_time,
          $item->modified_by,
          $item->modified_time,
          $item->parent_id, // JGLOBAL_ROOT
          // $item->,

        ];
      },
      $categories
    );

    // echo 'title: ' . json_encode(['ID', 'Title', 'Published', 'Hidden', 'Created/Owner','Created','Modified by','Modified','Parent',], JSON_UNESCAPED_SLASHES) . "\n" . "\n";
    // echo 'categories: ' . json_encode($categories, JSON_UNESCAPED_SLASHES) . "\n" . "\n";

    // Display the categories in a table and set the exit code to 0
    $this->ioStyle->table(
      [
        'ID', 'Title', 'Published', 'Hidden', 'Created/Owner','Created','Modified by','Modified','Parent',
      ],
      $categories
    );

    return Command::SUCCESS;
  }

  /**
   * Retrieves extension list from DB
   *
   * @return array
   *
   * @since 4.0.0
   */
  private function getItemsFromDB(string $userId, string $parent_id): array
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);
    $query
      ->select('*')
      ->from('#__joomgallery_categories');

    if ( ! empty ($userId) )
    {
      $query->where($db->quoteName('created_by') . ' = ' . (int) $userId);
    }

    if ( ! empty ($parent_id) )
    {
      $query->where($db->quoteName('parent_id') . ' = ' . (int) $parent_id);
    }

    $db->setQuery($query);
//    $categories = $db->loadAssocList('id');
    $categories = $db->loadObjectList();

    return $categories;
  }




}

