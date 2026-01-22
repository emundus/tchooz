<?php
/**
 * Plugin element to render fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.field
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Tchooz\Entities\ApplicationFile\ApplicationChoicesEntity;
use Tchooz\Enums\ApplicationFile\ChoicesStateEnum;
use Tchooz\Repositories\ApplicationFile\ApplicationChoicesRepository;
use Tchooz\Repositories\Programs\ProgramRepository;

jimport('joomla.application.component.model');

/**
 * Plugin element to render application choices fields
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.application_choices
 * @since       3.0
 */
class PlgFabrik_ElementApplicationchoices extends PlgFabrik_Element
{

	/**
	 * Draws the html form element
	 *
	 * @param   array  $data           to pre-populate element with
	 * @param   int    $repeatCounter  repeat group counter
	 *
	 * @return  string    elements html
	 */
	public function render($data, $repeatCounter = 0)
	{
		$listModel     = $this->getListModel();
		$db_table_name = $listModel->getTable()->db_table_name;

		$params = $this->getParams();
		$name   = $this->getHTMLName($repeatCounter);
		$id     = $this->getHTMLId($repeatCounter);

		$displayData = new stdClass;
		if (!$this->isEditable())
		{
			// If name contains index notation e.g. name[0] we need to strip it off to get the correct data key
			if (str_contains($name, '['))
			{
				$name = preg_replace('/\[.*\]/', '', $name);
				$val  = $data[$name][$repeatCounter];
			}
			else
			{
				$val = $data[$name];
			}

			if (!empty($val))
			{
				$formatted = $this->formatData($val);

				if (empty($formatted->getChoice()) || empty($formatted->getStatus()))
				{
					return '';
				}

				return $formatted->getChoice()->getCampaign()->getLabel() . ' - ' . $formatted->getStatus()->getLabel();
			}
			else
			{
				return '';
			}
		}
		else
		{
			$layout = $this->getLayout('form');

			$displayData->id           = $id;
			$displayData->name         = $name;
			$displayData->confirmation = $params->get('confirmation_application_choices', 0);
			$displayData->status       = $params->get('application_choices_status', '');
			$displayData->fnum         = !empty($data[$db_table_name . '___fnum']) ? $data[$db_table_name . '___fnum'] : '';
			$displayData->step_id      = !empty($data[$db_table_name . '___step_id']) ? $data[$db_table_name . '___step_id'] : 0;
			$displayData->value        = $this->getValue($data, $repeatCounter);
			$formatted_value           = $this->formatData($displayData->value);

			$displayData->selected_choice = !empty($formatted_value->getChoice()) ? $formatted_value->getChoice()->getId() : 0;
			if (!empty($formatted_value->getStatus()))
			{
				$displayData->selected_status = $formatted_value->getStatus()->value;
			}
			elseif (!empty($formatted_value->getChoice()))
			{
				$displayData->selected_status = $formatted_value->getChoice()->getState()->value;
			}
			else
			{
				$displayData->selected_status = 0;
			}

			$emSession = $this->app->getSession()->get('emundusUser');
			if (empty($displayData->fnum) && !empty($emSession->fnum))
			{
				$displayData->fnum = $emSession->fnum;
			}

			if (empty($displayData->fnum))
			{
				return Text::_('PLG_FABRIK_ELEMENT_APPLICATION_CHOICES_NO_FNUM');
			}

			// Get choices
			$displayData->choices            = $this->getChoices($displayData->fnum, $displayData->step_id, $displayData->status);
			$available_statuses              = ChoicesStateEnum::cases();
			$displayData->available_statuses = [];
			foreach ($available_statuses as $status)
			{
				if ($status === ChoicesStateEnum::CONFIRMED)
				{
					continue;
				}

				$displayData->available_statuses[] = [
					'value' => $status->value,
					'label' => $status->getLabel()
				];
			}

			if (empty($displayData->selected_choice) && !empty($displayData->choices))
			{
				$displayData->selected_choice = $displayData->choices[0]['id'];
			}
		}

		return $layout->render($displayData);
	}

	/**
	 * Returns javascript which creates an instance of the class defined in formJavascriptClass()
	 *
	 * @param   int  $repeatCounter  Repeat group counter
	 *
	 * @return  array
	 */
	public function elementJavascript($repeatCounter)
	{
		$id   = $this->getHTMLId($repeatCounter);
		$opts = $this->getElementJSOptions($repeatCounter);

		$opts->confirmation = $this->getParams()->get('confirmation_application_choices', 0);
		$opts->layout       = $this->isEditable() ? 'form' : 'details';

		return array('FbApplicationChoices', $id, $opts);
	}

	/**
	 * Manipulates posted form data for insertion into database
	 *
	 * @param   mixed  $val   This elements posted form data
	 * @param   array  $data  Posted form data
	 *
	 * @return  mixed
	 */
	public function storeDatabaseFormat($val, $data)
	{
		$task = $this->app->input->get('task', '');
		if ($task === 'form.process' || $task == 'process')
		{
			$params       = $this->getParams();
			$confirmation = $params->get('confirmation_application_choices', 0);

			if ($confirmation == 1)
			{
				$formatted = $this->formatData($val);

				if (!empty($formatted->getChoice()) && !empty($formatted->getChoice()->getId()) && !empty($formatted->getStatus()))
				{
					$repository = new ApplicationChoicesRepository();

					$formatted->getChoice()->setState($formatted->getStatus());
					$repository->flush($formatted->getChoice(), false);
				}
			}
		}

		return $val;
	}

	private function formatData($val): FormattedResult
	{
		$formatted = new FormattedResult();

		if (!empty($val) && str_contains($val, '|'))
		{
			$parts  = explode('|', $val);
			$id     = $parts[0];
			$status = $parts[1];
			$status = ChoicesStateEnum::tryFrom($status);

			if (!empty($id) && !empty($status))
			{
				$repository = new ApplicationChoicesRepository();
				$choice     = $repository->getById($id);

				$formatted->setChoice($choice);
				$formatted->setStatus($status);
			}
		}

		return $formatted;
	}

	private function getChoices(string $fnum, int $step_id = 0, int|string $status): array
	{
		$user = $this->app->getIdentity();

		if (!class_exists('EmundusModelProgramme'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/programme.php';
		}
		$m_programme   = new EmundusModelProgramme();
		$user_programs = $m_programme->getUserPrograms($user->id);
		$programs      = $user_programs;

		if (!empty($step_id))
		{
			if (!class_exists('EmundusModelWorkflow'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/workflow.php';
			}
			$m_workflow = new EmundusModelWorkflow();
			$step_data  = $m_workflow->getStepData($step_id);

			if (!empty($step_data) && !empty($step_data->programs))
			{
				$programRepository = new ProgramRepository();
				$step_programs     = $programRepository->getCodesByIds($step_data->programs);

				$programs = array_intersect($programs, $step_programs);
			}
		}

		if(!empty($status))
		{
			$status = (int) $status;
			$status = ChoicesStateEnum::tryFrom($status);
		}
		else {
			$status = null;
		}

		$repository                 = new ApplicationChoicesRepository();
		$applicationChoicesEntities = $repository->getChoicesByFnum($fnum, $programs, $status);
		if (empty($applicationChoicesEntities))
		{
			$applicationChoicesEntities = $repository->getChoicesByFnum($fnum, $user_programs, $status);
		}

		$choices = [];
		foreach ($applicationChoicesEntities as $entity)
		{
			$entityObject               = $entity->__serialize();
			$entityObject['state_html'] = $entity->getState()->getHtmlBadge();
			$choices[]                  = $entityObject;
		}

		return $choices;
	}

	/**
	 * Internal element validation
	 *
	 * @param   array  $data           Form data
	 * @param   int    $repeatCounter  Repeat group counter
	 *
	 * @return  bool
	 */
	public function validate($data, $repeatCounter = 0): bool
	{
		return true;
	}

	public function getValidationErr(): string
	{
		return Text::_('PLG_FABRIK_ELEMENT_APPLICATION_CHOICES_ERROR');
	}
}

class FormattedResult
{
	public ?ApplicationChoicesEntity $choice = null;
	public ?ChoicesStateEnum $status = null;

	public function __construct(ApplicationChoicesEntity $choice = null, ChoicesStateEnum $status = null)
	{
		$this->choice = null;
		$this->status = null;
	}

	public function getChoice(): ?ApplicationChoicesEntity
	{
		return $this->choice;
	}

	public function setChoice(?ApplicationChoicesEntity $choice): void
	{
		$this->choice = $choice;
	}

	public function getStatus(): ?ChoicesStateEnum
	{
		return $this->status;
	}

	public function setStatus(?ChoicesStateEnum $status): void
	{
		$this->status = $status;
	}
}
