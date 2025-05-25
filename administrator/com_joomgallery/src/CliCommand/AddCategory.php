<?php
namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Filter\InputFilter;

use Joomgallery\Component\Joomgallery\Administrator\Model\CategoryModel;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddCategory extends AbstractCommand
{
  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:category:add';

  /**
   * @var   SymfonyStyle
   */
  private $ioStyle;

  /**
   * @var   InputInterface
   */
  private $cliInput;

  private $title;
  private $published;
  private $created_by;
  private $created_time;
  private $modified_by;
  private $modified_time;
  // ToDo: private $parent_title;
  private $parent_id;

  /**
   * @inheritDoc
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    // Configure the Symfony output helper
    $this->configureSymfonyIO($input, $output);
//    $this->ioStyle->title(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_DESC'));
    $this->ioStyle->title('JoomGallery add category');

    //--- assign option ----------------------------

    // Get filter to remove invalid characters
    $filter = new InputFilter();

    // create/update time fallback
    $date = Factory::getDate();
    $actualTime = $date->toSql();

    //--- title -----------------------

    $this->title = $filter->clean($this->getStringFromOption(
      'title', 'Please enter a category title'));

    //--- created_by -----------------------

    $this->created_by = $filter->clean($this->getStringFromOption(
      'created_by', 'Please enter a username (owner)'));
    $created_by_Id = $this->getUserId($this->created_by);

    if (empty($created_by_Id)) {
      $this->ioStyle->error("The user (owner)" . $this->created_by . " does not exist!");

      return Command::FAILURE;
    }

    //--- modified_by -----------------------

    $this->modified_by = $filter->clean($input->getOption('modified_by')) ?? null;

    // not given by input use created by
    if (empty($this->modified_by)) {
      $this->modified_by = $this->created_by;
      $this->modified_time = $created_by_Id;
    } else {

      $this->modified_by_Id = $this->getUserId($this->modified_by);

      if (empty($modified_by_Id)) {
        $this->ioStyle->error("The user (author)" . $this->modified_by . " does not exist!");

        return Command::FAILURE;
      }
    }

    $this->published     = $filter->clean($input->getOption('published') ?? '0');
    $this->created_time  = $filter->clean($input->getOption('created_time')  ?? $actualTime);
    $this->modified_time = $filter->clean($input->getOption('modified_time') ?? $actualTime);
//    $this->parent_title  = $filter->clean($input->getOption('parent_title')  ?? 'root');
    $this->parent_id  = $filter->clean($input->getOption('parent_id')  ?? '1');

    //--- validate -----------------------------------

    if (!is_numeric($this->published)) {
      $this->ioStyle->error('Invalid published value passed! (0/1) ? ');
      return Command::FAILURE;
    }


    $category = [
      'title' => $filter->clean($this->title, 'STRING'),
      'published' => $filter->clean($this->published, 'INT'),
      'created_by' => $filter->clean($this->created_by, 'STRING'),
      'created_time' => $filter->clean($this->title, 'STRING'),
      'modified_by' => $filter->clean($this->modified_by, 'STRING'),
      'modified_time' => $filter->clean($this->title, 'STRING'),
//      'parent_title' => $filter->clean($this->parent_title, 'STRING'),
      'parent_id' => $filter->clean($this->parent_id, 'INT'),
    ];

    // Save the category, using the backend model
    /** @var Joomgallery\Component\Joomgallery\Administrator\Model\CategoryModel; $categoriesModel */
    $categoryModel = $this->getMVCFactory()->createModel('Category', 'Administrator');

    if (!$categoryModel->save($category)) {
//      switch ($categoryModel->getError()) {
//        case "JLIB_DATABASE_ERROR_USERNAME_INUSE":
//          $this->ioStyle->error("The username already exists!");
//          break;
//        case "JLIB_DATABASE_ERROR_EMAIL_INUSE":
//          $this->ioStyle->error("The email address already exists!");
//          break;
//        case "JLIB_DATABASE_ERROR_VALID_MAIL":
//          $this->ioStyle->error("The email address is invalid!");
//          break;
//      }
      $this->ioStyle->error($categoryModel->getError());

      return Command::FAILURE;
    }

    $this->ioStyle->success("User created!");

    return Command::SUCCESS;



//    yyyy
//
//            // Get filter to remove invalid characters
//        $filter = new InputFilter();
//
//        $user = [
//          'username' => $filter->clean($this->user, 'USERNAME'),
//          'password' => $this->password,
//          'name'     => $filter->clean($this->name, 'STRING'),
//          'email'    => $this->email,
//          'groups'   => $this->userGroups,
//        ];
//
//        $categoryObj = User::getInstance();
//        $categoryObj->bind($user);
//
//
//
//
//
//
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
//
//    $categories = $categoriesModel->getItems();
//
//    // If no categories are found show a warning and set the exit code to 1.
//    if (empty($categories))
//    {
//      $this->ioStyle->warning('No categories found matching your criteria');
//
//      return 1;
//    }
//
//    // Reshape the categories into something humans can read.
//    $categories = array_map(
//      function (object $item): array
//      {
//        return [
//          $item->id,
//          $item->title,
//          $item->published ? Text::_('JYES') : Text::_('JNO'),
//          $item->created_by,
//          $item->created_time,
//          $item->modified_by,
//          $item->modified_time,
//          $item->parent_title, // JGLOBAL_ROOT
//          // $item->,
//
//        ];
//      },
//      $categories
//    );
//
//    // Display the categories in a table and set the exit code to 0
//    $this->ioStyle->table(
//      [
////        Text::_('JGLOBAL_FIELD_ID_LABEL'),
////        Text::_('JGLOBAL_TITLE'),
////        Text::_('JPUBLISHED'),
////        Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL') . ' ('.  Text::_('COM_JOOMGALLERY_OWNER') . ')', // ToDo: Owner
////        Text::_('JGLOBAL_FIELD_CREATED_LABEL'),
////        Text::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL'),
////        Text::_('JGLOBAL_FIELD_MODIFIED_LABEL'),
////        Text::_('JGLOBAL_LINK_PARENT_CATEGORY_LABEL'),
//        // Text::_(''),
//
//        'ID', 'Title', 'Published', 'Created by (owner)','Created','Modified by','Modified','Parent',
//      ],
//      $categories
//    );

//    return 0;
  }

  /**
   * Method to get a value from option
   *
   * @param   string  $option    set the option name
   *
   * @param   string  $question  set the question if user enters no value to option
   *
   * @return  string
   *
   * @since   4.0.0
   */
  protected function getStringFromOption($option, $question): string
  {
    $answer = (string) $this->getApplication()->getConsoleInput()->getOption($option);

    while (!$answer) {
      $answer = (string) $this->ioStyle->ask($question);
    }

    return $answer;
  }

  /**
   * Method to get a user object
   *
   * @param   string  $username  username
   *
   * @return  object
   *
   * @since   4.0.0
   */
  protected function getUserId($username)
  {
    // $db    = $this->getDatabase();
    $db = Factory::getContainer()->get(DatabaseInterface::class);
    $query = $db->getQuery(true)
      ->select($db->quoteName('id'))
      ->from($db->quoteName('#__users'))
      ->where($db->quoteName('username') . '= :username')
      ->bind(':username', $username);
    $db->setQuery($query);

    return $db->loadResult();
  }

  /**
   * Configure the command.
   *
   * @return  void
   */
  protected function configure(): void
  {
//    $this->setDescription(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_DESC'));
//    $this->setHelp(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_HELP'));
//
//    $this->addOption('search', 's', InputOption::VALUE_OPTIONAL, Text::_('COM_JOOMGALLERY_CLI_CONFIG_SEARCH'));

    $this->addOption('title', 't', InputOption::VALUE_REQUIRED, 'Title');
    $this->addOption('published', null, InputOption::VALUE_OPTIONAL, 'Published (yes/no)');
    $this->addOption('created_time', null, InputOption::VALUE_OPTIONAL, 'Created time');
    $this->addOption('created_by', 'c', InputOption::VALUE_REQUIRED, 'Created by (owner)');
    $this->addOption('modified_time', null, InputOption::VALUE_OPTIONAL, 'Modified time');
    $this->addOption('modified_by', 'm', InputOption::VALUE_OPTIONAL, 'Modified by');
//    $this->addOption('parent_title', 'p', InputOption::VALUE_OPTIONAL, 'parent title');
    $this->addOption('parent_id', 'p', InputOption::VALUE_OPTIONAL, 'parent id (1=no parent)');

    $help = "<info>%command.name%</info> will add a joomgallery category
		    \nUsage: <info>php %command.full_name%</info>";

    $this->setDescription(Text::_('Add joomgallery category'));
    $this->setHelp($help);

  }

  /**
   * Configure the IO.
   *
   * @param   InputInterface   $input   The input to inject into the command.
   * @param   OutputInterface  $output  The output to inject into the command.
   *
   * @return  void
   */
  private function configureSymfonyIO(InputInterface $input, OutputInterface $output)
  {
    $this->cliInput = $input;
    $this->ioStyle  = new SymfonyStyle($input, $output);
  }

}

