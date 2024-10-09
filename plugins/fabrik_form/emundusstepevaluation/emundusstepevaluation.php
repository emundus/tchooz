<?php
/**
 * @version     1.34.0: emundusisevaluatedbyme 2022-12-02 Brice HUBINET
 * @package     Fabrik
 * @copyright   Copyright (C) 2022 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * @description Check how can the connected user can access to an evaluation
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

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
		$user = $this->app->getIdentity();
		$form_model = $this->getModel();
		$db_table_name = $form_model->getTableName();
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$input = $this->app->input;
		$ccid = $input->getInt($db_table_name . '___ccid', 0);
		$step_id = $input->getInt($db_table_name . '___step_id', 0);
		$view = $input->getString('view', 'form');
		$current_row_id = $input->getInt('rowid', 0);

		$m_workflow = new EmundusModelWorkflow();
		$step_data = $m_workflow->getStepData($step_id);
		try {
			$access = EmundusHelperAccess::getUserEvaluationStepAccess($ccid, $step_data, $user->id);
		} catch (Exception $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$this->app->redirect('/');
		}

		$can_see = $access['can_see'];
		$can_edit = $access['can_edit'];

		if (!$can_see) {
			$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$this->app->redirect('/');
		}

		$current_url = '/evaluation-step-form?view=' . $view . '&formid=' . $form_model->getId() . '&tmpl=component&iframe=1&' . $db_table_name . '___ccid=' . $ccid . '&' . $db_table_name . '___step_id=' . $step_id . '&rowid=' . $current_row_id;
		$final_url = $current_url;

		if (!empty($step_data) && $can_edit && $view === 'form') {
			if ($step_data->multiple == 0) {
				// if step_data is not multiple, we need to redirect to the unique row for this ccid
				$query->clear()
					->select('id')
					->from($db_table_name)
					->where($db->quoteName('ccid') . ' = ' . $ccid)
					->andWhere($db->quoteName('step_id') . ' = ' . $step_id);

				$db->setQuery($query);
				$row_id = $db->loadResult();
			} else {
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

			if (!empty($row_id)) {
				// if coord or admin, he is allowed to edit all rows, so if rowid is not 0, keep it
				// if not, replace it with the rowid

				if (!EmundusHelperAccess::asCoordinatorAccessLevel($user->id) || $current_row_id == 0) {
					$final_url = preg_replace('/&rowid=\d+/', '&rowid=' . $row_id, $final_url);
					if (strpos($final_url, 'rowid') === false) {
						$final_url .= '&rowid=' . $row_id;
					}
				}
			}
		}

		if (!$can_edit && $view !== 'details') {
			$this->app->enqueueMessage(Text::_('READONLY_ACCESS'), 'error');
			$final_url = str_replace('view=form', 'view=details', $final_url);
		}

		if ($current_url !== $final_url) {
			$this->app->redirect($final_url);
		}
	}

	public function onBeforeProcess(): void
	{
		$form_model = $this->getModel();
		$db_table_name = $form_model->getTableName();

		PluginHelper::importPlugin('emundus', 'custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', ['onBeforeSubmitEvaluation', ['formModel' => $form_model]]);

		$ccid = $this->app->input->getInt($db_table_name . '___ccid', 0);
		$fnum = EmundusHelperFiles::getFnumFromId($ccid);
		$form_model->updateFormData($db_table_name . '___fnum', $fnum);
	}

	public function onAfterProcess(): void
	{
		$formModel = $this->getModel();

		PluginHelper::importPlugin('emundus', 'custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', ['onAfterSubmitEvaluation', ['formModel' => $formModel]]);

		echo '<script src="' . Uri::base() . 'media/com_emundus/js/lib/sweetalert/sweetalert.min.js"></script>';

		echo '<script>window.parent.ScrollToTop();</script>';

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
