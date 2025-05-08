<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Config;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Layout\LayoutHelper;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Config.
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $item;

	protected $form;

  protected $fieldsets;

  protected $is_global_config;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		/** @var ConfigModel $model */
    $model = $this->getModel();

		$this->state            = $model->getState();
		$this->item             = $model->getItem();
		$this->form             = $model->getForm();
		$this->fieldsets        = $model->getFieldsets();
    $this->is_global_config = ($this->item->id === 1) ? true : false;

		// Check for errors.
		if(count($errors = $model->getErrors()))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		/** @var Toolbar $model */
    $toolbar = $this->getDocument()->getToolbar();

		$user  = Factory::getApplication()->getIdentity();
		$isNew = ($this->item->id == 0);

		if(isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_CONFIG_SETS').' :: '.Text::_('COM_JOOMGALLERY_CONFIG_EDIT'), "sliders-h");

		// If not checked out, can save the item.
		if(!$checkedOut && ($this->getAcl()->checkACL('core.edit') || ($this->getAcl()->checkACL('core.create'))))
		{
			ToolbarHelper::apply('config.apply', 'JTOOLBAR_APPLY');

			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure
			(
				function (Toolbar $childBar) use ($checkedOut, $isNew)
				{
					$childBar->save('config.save', 'JTOOLBAR_SAVE');

					if(!$checkedOut && ($this->getAcl()->checkACL('core.create')))
					{
						$childBar->save2new('config.save2new');
					}

					// If an existing item, can save to a copy.
					if(!$isNew && $this->getAcl()->checkACL('core.create'))
					{
						$childBar->save2copy('config.save2copy');
					}
				}
			);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CLOSE');
		}

		if(!$isNew)
		{
			// $resetGroup = $toolbar->dropdownButton('reset-group')
			// 	->text('Settings')
			// 	->toggleSplit(false)
			// 	->icon('fas fa-ellipsis-h')
			// 	->buttonClass('btn btn-action')
			// 	->listCheck(false);

			// $childBar = $resetGroup->getChildToolbar();

			$toolbar->confirmButton('reset')
				->text('JRESET')
				->task('config.reset')
				->message('COM_JOOMGALLERY_RESET_CONFIRM')
				->icon('icon-refresh')
				->listCheck(false);
				
			$toolbar->standardButton('export')
				->text('JTOOLBAR_EXPORT')
				->task('config.export')
				->icon('icon-download')
				->listCheck(false);
		
			$import_modal_opt = array(
				'selector'=> 'import_modal',
				'doTask' => '',
				'btnClass' => 'button-import btn btn-primary',
				'htmlAttributes' => '',
				'class' => 'icon-upload',
				'text' => Text::_('COM_JOOMGALLERY_IMPORT'));
			$import_modal_btn = LayoutHelper::render('joomla.toolbar.popup', $import_modal_opt);
			$toolbar->appendButton('Custom', $import_modal_btn);
		}
	}

  /**
	 * Method to get an array of JFormField objects in a given fieldset by name.
   *
   * @param    string  $name   name of the fieldset
	 *
	 * @return   array   Array with field names
	 *
	 */
  public function getFieldset($name)
	{
    return $this->form->getFieldset($name);
  }

  /**
  * Render a single field.
  *
  * @param   object  $field   Field object to render
  *
  * @return  string  html code for field output
  */
  public function renderField($field)
  {
    $global_only = false;
    if(!$this->is_global_config && !empty($field->getAttribute('global_only')) && $field->getAttribute('global_only') == true)
    {
      $global_only = true;
    }

    $sensitive = false;
    if(!empty($field->getAttribute('sensitive')) && $field->getAttribute('sensitive') == true)
    {
      $sensitive = true;
    }

    if($global_only)
    {
      // Fields with global_only attribute --> Not editable
      $field_data = array(
        'id' => $field->id,
        'name' => $field->name,
        'label' => LayoutHelper::render('joomla.form.renderlabel', array('text'=>Text::_($field->getAttribute('label')), 'for'=>$field->id, 'required'=>false, 'classes'=>array(), 'sensitive'=>$sensitive)),
        'input' => LayoutHelper::render('joomla.form.field.value', array('id'=>$field->id, 'value'=>$field->value, 'class'=>'')),
        'description' => Text::_('COM_JOOMGALLERY_CONFIG_EDIT_ONLY_IN_GLOBAL'),
      );
      echo LayoutHelper::render('joomla.form.renderfield', $field_data);
    }
    else
    {
      echo $field->renderField(array('sensitive' => $sensitive));
    }
  }
}
