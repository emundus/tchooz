<?php
/**
 * @version     1.34.0: emundusisevaluatedbyme 2022-12-02 Brice HUBINET
 * @package     Fabrik
 * @copyright   Copyright (C) 2022 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * @description Check how can the connected user can access to an evaluation
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterSubmitEvaluationDefinition;
use Tchooz\Repositories\Workflow\StepRepository;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');


/**
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */
class PlgFabrik_FormEmundusstepevaluation extends plgFabrik_Form
{
	public function onBeforeLoad(): void
	{
		$language     = $this->app->getLanguage();
		$current_lang = $language->getTag();

		$language->load('com_emundus', JPATH_SITE . '/components/com_emundus', $current_lang, true);

		$defaultLanguage = ComponentHelper::getParams('com_languages')->get('site', 'fr-FR');
		if ($current_lang !== $defaultLanguage)
		{
			$currentLangPath = '/' . substr($current_lang, 0, 2);
		}
		else
		{
			$currentLangPath = '';
		}

		$user          = $this->app->getIdentity();
		$form_model    = $this->getModel();
		$db_table_name = $form_model->getTableName();
		$db            = Factory::getContainer()->get('DatabaseDriver');
		$query         = $db->createQuery();

		$input          = $this->app->input;
		$ccid           = $input->getInt($db_table_name . '___ccid', 0);
		$step_id        = $input->getInt($db_table_name . '___step_id', 0);
		$view           = $input->getString('view', 'form');
		$current_row_id = $input->getInt('rowid', 0);
		$fnum = EmundusHelperFiles::getFnumFromId($ccid);

		if (empty($fnum) || !EmundusHelperAccess::asAccessAction(1, 'r', $user->id, $fnum))
		{
			$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$this->app->redirect('/');
		}

		$campaign_id = null;
		if (!empty($ccid))
		{
			$query->clear()
				->select('campaign_id')
				->from('#__emundus_campaign_candidature')
				->where('id = ' . $ccid);
			$db->setQuery($query);
			$campaign_id = $db->loadResult();
		}

		$stepRepository = new StepRepository();
		$step = $stepRepository->getStepById($step_id);

		if (empty($step))
		{
			$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$this->app->redirect('/');
		}

		$current_url = $currentLangPath . '/evaluation-step-form?view=' . $view . '&formid=' . $form_model->getId() . '&tmpl=component&iframe=1&' . $db_table_name . '___ccid=' . $ccid . '&' . $db_table_name . '___step_id=' . $step_id;
		if (!empty($current_row_id))
		{
			$current_url .= '&rowid=' . $current_row_id;
		}

		if (!empty($current_row_id))
		{
			$query->clear()
				->select('*')
				->from($step->getTable())
				->where('id = ' . $current_row_id);

			$db->setQuery($query);
			$evaluationRow = $db->loadObject();

			if (empty($evaluationRow->id))
			{
				$this->app->enqueueMessage(Text::_('ERROR_NOT_FOUND'), 'error');
				$this->app->redirect('/');
			}

			if ($evaluationRow->evaluator != $user->id)
			{
				if (EmundusHelperAccess::asAccessAction($step->getType()->getActionId(), 'u', $user->id, $fnum))
				{
					// nothing to do, user can access
				}
				else
				{
					if ($step->getMultiple() === 0)
					{
						if (EmundusHelperAccess::asAccessAction($step->getType()->getActionId(), 'c', $user->id, $fnum))
						{
							// case 1, nothing to do, redirect will be done later
						}
						else if (EmundusHelperAccess::asAccessAction($step->getType()->getActionId(), 'r', $user->id, $fnum))
						{
							$view = 'details';
						}
						else
						{
							$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
							$this->app->redirect('/');
						}
					}
					else
					{
						if (EmundusHelperAccess::asAccessAction($step->getType()->getActionId(), 'r', $user->id, $fnum))
						{
							$view = 'details';
						}
						else
						{
							$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
							$this->app->redirect('/');
						}
					}
				}
			}
		}
		else
		{
			// no current row id, try to find existing evaluation for this user
			$query->clear()
				->select('*')
				->from($step->getTable())
				->where('evaluator = ' . $user->id)
				->where('step_id = ' . $step->getId())
				->where('ccid = ' . $ccid);

			$db->setQuery($query);
			$evaluationRow = $db->loadObject();

			if (!empty($evaluationRow->id))
			{
				$current_row_id = $evaluationRow->id;
			}
			else if ($step->getMultiple() === 0)
			{
				$query->clear()
					->select('*')
					->from($step->getTable())
					->where('step_id = ' . $step->getId())
					->where('ccid = ' . $ccid);

				$db->setQuery($query);
				$evaluationRow = $db->loadObject();

				if (!empty($evaluationRow->id))
				{
					$current_row_id = $evaluationRow->id;
				}
			}
		}

		if ($view === 'form')
		{
			/**
			 * administrator and coordinator can always access the evaluation form
			 * other users can access the form only if they are in the date range
			 */
			if (!EmundusHelperAccess::asAdministratorAccessLevel($user->id) && !EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
			{
				$workflowModel = new EmundusModelWorkflow();
				$dates = $workflowModel->calculateStartAndEndDates($step, $fnum, $campaign_id);

				if (!$dates['infinite'])
				{
					$now = EmundusHelperDate::getNow(Factory::getApplication()->get('offset', 'UTC'));
					$startDate = $dates['start_date'];
					$endDate = $dates['end_date'];

					if ($now < $startDate || $now > $endDate)
					{
						$this->app->enqueueMessage(Text::_('COM_EMUNDUS_EVALUATION_NOT_IN_DATE_RANGE'), 'error');
						$view = 'details';
					}
				}
			}
		}

		$final_url   = $currentLangPath . '/evaluation-step-form?view=' . $view . '&formid=' . $form_model->getId() . '&tmpl=component&iframe=1&' . $db_table_name . '___ccid=' . $ccid . '&' . $db_table_name . '___step_id=' . $step_id;
		if (!empty($current_row_id))
		{
			$final_url .= '&rowid=' . $current_row_id;
		}

		if ($current_url !== $final_url)
		{
			$this->app->redirect($final_url);
		}

		$form_model->data[$db_table_name . '___fnum'] = $fnum;
		// log user access to evaluation
		require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
		$m_files      = new EmundusModelFiles();
		$applicant_id = $m_files->getFnumInfos($fnum)['applicant_id'];

		require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
		EmundusModelLogs::log($user->id, $applicant_id, $fnum, $step->getType()->getActionId(), 'r', 'COM_EMUNDUS_ACCESS_EVALUATION', json_encode(array('step_id' => $step_id)));

		if (!empty($fnum) && empty($form_model->getRowId()))
		{
			if (!class_exists('EmundusHelperFabrik'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
			}
			EmundusHelperFabrik::fillFormFromAliases($form_model, $db_table_name, $fnum);
		}
	}

	public function onBeforeProcess(): void
	{
		$form_model = $this->getModel();

		PluginHelper::importPlugin('emundus', 'custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', ['onBeforeSubmitEvaluation', ['formModel' => $form_model]]);
	}

	public function onJSReady()
	{
		$form_model = $this->getModel();

		$db_table_name = $form_model->getTableName();
		$db            = Factory::getContainer()->get('DatabaseDriver');
		$query         = $db->createQuery();

		$input          = $this->app->input;
		$ccid           = $input->getInt($db_table_name . '___ccid', 0);
		$step_id        = $input->getInt($db_table_name . '___step_id', 0);
		$view           = $input->getString('view', 'form');
		$current_row_id = $input->getInt('rowid', 0);

		$campaign_id = null;
		if (!empty($ccid))
		{
			$query->clear()
				->select('campaign_id')
				->from('#__emundus_campaign_candidature')
				->where('id = ' . $ccid);
			$db->setQuery($query);
			$campaign_id = $db->loadResult();
		}

		$m_workflow = new EmundusModelWorkflow();
		$step_data  = $m_workflow->getStepData($step_id, $campaign_id);

		// If step_data is lock display a sweetalert to confirm submittion
		if ($step_data->lock == 1)
		{
			echo '<script>document.addEventListener("DOMContentLoaded", function () {beforeSubmitEvaluation('.$form_model->getId().');});</script>';
		}
	}

	public function onAfterProcess(): void
	{
		$form_model = $this->getModel();
		$ccid  = $this->app->input->getInt($form_model->getTableName() . '___ccid', 0);
		$fnum = EmundusHelperFiles::getFnumFromId($ccid);
		$applicantId = EmundusHelperFiles::getApplicantIdFromFnum($fnum);
		PluginHelper::importPlugin('emundus', 'custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', [
			'onAfterSubmitEvaluation',
			[
				'formModel' => $form_model,
				'context' => new EventContextEntity($this->user, [$fnum], [$applicantId], [onAfterSubmitEvaluationDefinition::FORM_KEY => $form_model->getId()])
			]
		]);

		echo '<script src="' . Uri::base() . 'media/com_emundus/js/lib/sweetalert/sweetalert.min.js"></script>';

		echo '<style>
			.em-swal-title{
			  margin: 8px 8px 32px 8px !important;
			  font-family: "Maven Pro", sans-serif;
			}
			</style>';

		$targetOrigin = Uri::base();
		die("<script>
	     document.addEventListener('DOMContentLoaded', function() {
             // post a message to the parent window to trigger a reload of the evaluations list
             window.parent.postMessage({event: 'evaluationSubmitted', fnum: '" . $fnum . "'}, '" .  $targetOrigin . "');
             
	        Swal.fire({
	          position: 'top',
	          icon: 'success',
	          title: '" . Text::_('COM_EMUNDUS_EVALUATION_SAVED') . "',
	          showConfirmButton: false,
	          timer: 2000,
	          customClass: {
	            title: 'em-swal-title',
	          }
	        }).then((result) => {
	            window.location.href = window.location.href.replace('r=1', 'r=0');
			});
	     });
	     </script>");
	}
}
