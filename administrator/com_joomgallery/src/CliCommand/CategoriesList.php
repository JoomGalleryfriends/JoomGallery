<?php
namespace Joomgallery\Component\Joomgallery\Administrator\CliCommand;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\MVC\Model\DatabaseAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CategoriesList extends \Joomla\Console\Command\AbstractCommand
{
  use MVCFactoryAwareTrait;
  use DatabaseAwareTrait;

  /**
   * The default command name
   *
   * @var    string
   */
  protected static $defaultName = 'joomgallery:categories:list';

  /**
   * @var   SymfonyStyle
   */
  private $ioStyle;

  /**
   * @var   InputInterface
   */
  private $cliInput;

  /**
   * @inheritDoc
   */
  protected function doExecute(InputInterface $input, OutputInterface $output): int
  {
    // Configure the Symfony output helper
    $this->configureSymfonyIO($input, $output);
//    $this->ioStyle->title(Text::_('COM_JOOMGALLERY_CLI_ITEMS_LIST_DESC'));
    $this->ioStyle->title('JoomGallery Categories list');

    // Get the categories, using the backend model
    /** @var Joomgallery\Component\Joomgallery\Administrator\Model\CategoriesModel;  */
    $categoriesModel = $this->getMVCFactory()->createModel('Categories', 'Administrator');

    //--- assign option ----------------------------

//    $search = $input->getOption('search') ?? null;
//    if ($search)
//    {
//      $categoriesModel->setState('filter.search', $search);
//    }

    $owner = $input->getOption('owner') ?? null;

    if ($owner) // created_by
    {
      $categoriesModel->setState('filter.created_by', $owner);
      // rename
      // not matching option error
      // $input->setOption('created_by', $owner);

      $app = $this->getApplication();
      $input = $app->getInput();
      // $input->set('created_by', $owner);

      //$filter = $input->get('filter');

//      $filter = $input->getFilter();
//
//      $filter->set('filter.created_by', $owner);
    }

    $categories = $categoriesModel->getItems();

    // If no categories are found show a warning and set the exit code to 1.
    if (empty($categories))
    {
      $this->ioStyle->warning('No categories found matching your criteria');

      return 1;
    }

    // Reshape the categories into something humans can read.
    $categories = array_map(
      function (object $item): array
      {
        return [
          $item->id,
          $item->title,
          $item->published ? Text::_('JYES') : Text::_('JNO'),
          $item->created_by,
          $item->created_time,
          $item->modified_by,
          $item->modified_time,
          $item->parent_title, // JGLOBAL_ROOT
          // $item->,

        ];
      },
      $categories
    );

    // Display the categories in a table and set the exit code to 0
    $this->ioStyle->table(
      [
//        Text::_('JGLOBAL_FIELD_ID_LABEL'),
//        Text::_('JGLOBAL_TITLE'),
//        Text::_('JPUBLISHED'),
//        Text::_('JGLOBAL_FIELD_CREATED_BY_LABEL') . ' ('.  Text::_('COM_JOOMGALLERY_OWNER') . ')', // ToDo: Owner
//        Text::_('JGLOBAL_FIELD_CREATED_LABEL'),
//        Text::_('JGLOBAL_FIELD_MODIFIED_BY_LABEL'),
//        Text::_('JGLOBAL_FIELD_MODIFIED_LABEL'),
//        Text::_('JGLOBAL_LINK_PARENT_CATEGORY_LABEL'),
        // Text::_(''),

        'ID', 'Title', 'Published', 'Created by (owner)','Created','Modified by','Modified','Parent',
      ],
      $categories
    );

    return Command::SUCCESS;
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

    // ToDo: option to limit by user (owner), ?parent ...
    $this->addOption('owner', null, InputOption::VALUE_OPTIONAL, 'username (created_by)');
    $this->addOption('search', 's', InputOption::VALUE_OPTIONAL, Text::_('COM_JOOMGALLERY_CLI_CONFIG_SEARCH'));

    $help = "<info>%command.name%</info> will list all joomgallery categories 
		    \nUsage: <info>php %command.full_name%</info>";

    $this->setDescription(Text::_('List all joomgallery categories'));
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

