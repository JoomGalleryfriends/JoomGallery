<?php
/**
 ******************************************************************************************
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later            ToDo: Format  **
 *****************************************************************************************/

// WIP: in development, but can not be called from caommand line (plugin)

namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

defined('_JEXEC') or die;

use Joomgallery\Component\Joomgallery\Administrator\Model\CategoryModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Filter\InputFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CategoryAdd extends AbstractCommand
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

  /**
   * category title from user input
   * @var string
   */
  private $title;

  /**
   * category published from user input, yes/no true/false
   * @var string
   */
  private $published;

  /**
   * category created by from user input
   * @var string
   */
  private $created_by;

  /**
   * category created time from user input
   * @var string
   */
  private $created_time;

  /**
   * category modified by from user input
   * @var string
   */
  private $modified_by;

  /**
   * category modified time from user input
   * @var string
   */
  private $modified_time;

  /**
   * category parent id from user input
   * @var string
   */
  private $parent_id;

  /**
   * Instantiate the command.
   *
   * @since  4.0.X
   */
  public function __construct()
  {
    parent::__construct();
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
   * Configure the command.
   *
   * @return  void
   */
  protected function configure(): void
  {

    // ToDo: title as argument ? $this->addArgument('title', 't', InputOption::VALUE_REQUIRED, 'Title');
    $this->addOption('title', 't', InputOption::VALUE_REQUIRED, 'Title');
    $this->addOption('published', null, InputOption::VALUE_OPTIONAL, 'Published (yes/no)');
    $this->addOption('created_time', null, InputOption::VALUE_OPTIONAL, 'Created time');
    $this->addOption('created_by', 'c', InputOption::VALUE_REQUIRED, 'Created by (owner)');
    $this->addOption('modified_time', null, InputOption::VALUE_OPTIONAL, 'Modified time');
    $this->addOption('modified_by', 'm', InputOption::VALUE_OPTIONAL, 'Modified by');
//    $this->addOption('parent_title', 'p', InputOption::VALUE_OPTIONAL, 'parent title');
    $this->addOption('parent_id', 'p', InputOption::VALUE_OPTIONAL, 'parent id (1=no parent)');

    $help = "<info>%command.name%</info> will add a joomgallery category
	Usage: <info>php %command.full_name%</info>";

    $this->setDescription(Text::_('WIP, not finished: Add joomgallery category'));
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
    $this->ioStyle->title('WIP, not finished: JoomGallery add category');

    //--- assign option ----------------------------

    // Get filter to remove invalid characters
    $filter = new InputFilter();

    // create/update time fallback
    $date       = Factory::getDate();
    $actualTime = $date->toSql();

    echo "actualTime: $actualTime\n";

    //--- title -----------------------

    $this->title = $filter->clean($this->getStringFromOption('title', 'Please enter a category title'));

    //--- created_by -----------------------

    $this->created_by = $filter->clean($this->getStringFromOption(
      'created_by', 'Please enter a username (owner)'));
    $created_by_Id    = $this->getUserId($this->created_by);

    if (empty($created_by_Id))
    {
      $this->ioStyle->error("The user (owner)" . $this->created_by . " does not exist!");

      return Command::FAILURE;
    }

    $this->published     = $filter->clean($input->getOption('published') ?? '0');
    $this->created_time  = $filter->clean($input->getOption('created_time') ?? $actualTime);
    $this->modified_time = $filter->clean($input->getOption('modified_time') ?? $actualTime);
    $this->parent_id     = $filter->clean($input->getOption('parent_id') ?? '1');

    //--- modified_by -----------------------

    $this->modified_by = $filter->clean($input->getOption('modified_by')) ?? null;

    // not given by input use created by
    if (empty($this->modified_by))
    {
      $this->modified_by = $this->created_by;
    }
    else
    {

      $modified_by_Id = $this->getUserId($this->modified_by);

      if (empty($modified_by_Id))
      {
        $this->ioStyle->error("The user (author)" . $this->modified_by . " does not exist!");

        return Command::FAILURE;
      }
    }

    //--- validate -----------------------------------

    if (!is_numeric($this->published))
    {
      $this->ioStyle->error('Invalid published value passed! (0/1) ? ');

      return Command::FAILURE;
    }


    $category = [
      'title'         => $filter->clean($this->title, 'STRING'),
      'published'     => $filter->clean($this->published, 'INT'),
      'created_by'    => $filter->clean($this->created_by, 'STRING'),
      'created_time'  => $filter->clean($this->created_time, 'STRING'),
      'modified_by'   => $filter->clean($this->modified_time, 'STRING'),
      'modified_time' => $filter->clean($this->title, 'STRING'),
      'parent_id'     => $filter->clean($this->parent_id, 'INT'),
    ];

    echo json_encode($category, JSON_PRETTY_PRINT) . "\n";

    // Save the category, using the backend model
    /** @var  CategoryModel $categoryModel */
    $categoryModel = $this->getMVCFactory()->createModel('Category', 'Administrator');

    echo "add:save 01" . "\n";

    if (!$categoryModel->save($category))
    {
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
      echo "add:save error 02" . "\n";

      $this->ioStyle->error($categoryModel->getError());

      return Command::FAILURE;
    }

    echo "add:after save 01" . "\n";

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
   * @since  4.0.X
   */
  protected function getStringFromOption($option, $question): string
  {
    $answer = (string) $this->getApplication()->getConsoleInput()->getOption($option);

    while (!$answer)
    {
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
   * @since  4.0.X
   */
  protected function getUserId($username)
  {
    // $db    = $this->getDatabase();
    $db    = Factory::getContainer()->get(DatabaseInterface::class);
    $query = $db->getQuery(true)
      ->select($db->quoteName('id'))
      ->from($db->quoteName('#__users'))
      ->where($db->quoteName('username') . '= :username')
      ->bind(':username', $username);
    $db->setQuery($query);

    return $db->loadResult();
  }


} // class

