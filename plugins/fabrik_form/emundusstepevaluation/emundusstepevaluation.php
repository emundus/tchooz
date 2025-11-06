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
		try
		{
			$access = EmundusHelperAccess::getUserEvaluationStepAccess($ccid, $step_data, $user->id);
		}
		catch (Exception $e)
		{
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$this->app->redirect('/');
		}

		$can_see  = $access['can_see'];
		$can_edit = $access['can_edit'];

		if (!$can_see)
		{
			$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$this->app->redirect('/');
		}

		$current_url = $currentLangPath.'/evaluation-step-form?view=' . $view . '&formid=' . $form_model->getId() . '&tmpl=component&iframe=1&' . $db_table_name . '___ccid=' . $ccid . '&' . $db_table_name . '___step_id=' . $step_id . '&rowid=' . $current_row_id;
		$final_url   = $current_url;

        if (!empty($step_data)) {
            if ($step_data->multiple == 0)
            {
                // if step_data is not multiple, we need to redirect to the unique row for this ccid
                $query->clear()
                    ->select('id')
                    ->from($db_table_name)
                    ->where($db->quoteName('ccid') . ' = ' . $ccid)
                    ->andWhere($db->quoteName('step_id') . ' = ' . $step_id);

                $db->setQuery($query);
                $row_id = $db->loadResult();
            }
            else
            {
                // if multiple, we need to redirect to the row of the current user if it exists
                $query->clear()
                    ->select('id')
                    ->from($db_table_name)
                    ->where($db->quoteName('ccid') . ' = ' . $ccid)
                    ->andWhere($db->quoteName('step_id') . ' = ' . $step_id)
                    ->where($db->quoteName('evaluator') . ' = ' . $user->id);

                $db->setQuery($query);
                $row_id = $db->loadResult();
            }
        }

        if (!empty($step_data) && (($can_edit && $view === 'form') || (!$can_edit && $view !== 'details')))
        {
            if (!empty($row_id))
            {
                // if coord or admin, he is allowed to edit all rows, so if rowid is not 0, keep it
                // if not, replace it with the rowid

                if ($current_row_id == 0 || EmundusHelperAccess::asAccessAction($step_data->action_id, 'r', $user->id))
                {
                    $final_url = preg_replace('/&rowid=\d+/', '&rowid=' . $row_id, $final_url);
                    if (!str_contains($final_url, 'rowid'))
                    {
                        $final_url .= '&rowid=' . $row_id;
                    }
                }
            }
        }

        if (!$can_edit && $view !== 'details') {
            $this->app->enqueueMessage(Text::_($access['reason_cannot_edit']));
            $final_url = str_replace('view=form', 'view=details', $final_url);
        }

		if(!empty($step_data) && $step_data->lock == 1 && $view === 'form' && !empty($row_id))
		{
			$query->clear()
				->select('evaluator')
				->from($db_table_name)
				->where('id = ' . $row_id);
			$db->setQuery($query);
			$evaluator = $db->loadResult();

			// I can edit form only if i have update right and i'm not the evaluator
			if(!EmundusHelperAccess::asAccessAction($step_data->action_id, 'u', $user->id) || $evaluator === $user->id)
			{
				$final_url = str_replace('view=form', 'view=details', $final_url);
			}
		}

		if ($current_url !== $final_url)
		{
			$this->app->redirect($final_url);
		}

		$fnum                                         = EmundusHelperFiles::getFnumFromId($ccid);
		$form_model->data[$db_table_name . '___fnum'] = $fnum;

		// log user access to evaluation
		require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
		$m_files      = new EmundusModelFiles();
		$applicant_id = $m_files->getFnumInfos($fnum)['applicant_id'];

		require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
		EmundusModelLogs::log($user->id, $applicant_id, $fnum, $step_data->action_id, 'r', 'COM_EMUNDUS_ACCESS_EVALUATION', json_encode(array('step_id' => $step_id)));

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

		die("<script>
	     document.addEventListener('DOMContentLoaded', function() {
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
