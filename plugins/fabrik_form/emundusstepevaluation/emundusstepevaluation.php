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

		$can_see = false;
		$can_edit = false;

		if (!empty($ccid) && !empty($step_id)) {
			$m_workflow = new EmundusModelWorkflow();
			$step_data = $m_workflow->getStepData($step_id);

			// verify if user can access to this evaluation form
			if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id) || EmundusHelperAccess::asAdministratorAccessLevel($user->id)) {
				$can_see = true;
				$can_edit = true;
			} else {
				$fnum = EmundusHelperFiles::getFnumFromId($ccid);

				// it's the bare minimum to potentially see the evaluation form
				if (EmundusHelperAccess::asAccessAction(5, 'r', $user->id, $fnum)) {
					// Verify if this step and this ccid are linked together by workflow
					if (!empty($step_data)) {
						$query->clear()
							->select('esp.id')
							->from($db->quoteName('#__emundus_setup_programmes', 'esp'))
							->leftJoin($db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON esc.training = esp.code')
							->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.campaign_id = esc.id')
							->where('ecc.id = ' . $ccid);

						$db->setQuery($query);
						$programme_id = $db->loadResult();

						if (!empty($programme_id) && in_array($programme_id, $step_data->programs)) {
							// get profiles who can access to this step and verify if user is in one of these profiles
							$emundus_user_session = $this->app->getSession()->get('emundusUser');

							if (in_array($emundus_user_session->profile, $step_data->roles)) {
								$can_see = true;
								if (EmundusHelperAccess::asAccessAction(5, 'c', $user->id, $fnum)) {
									// verify step is not closed
									// file must be in one of the entry statuses and current date must be between start and end date of step
									$query->clear()
										->select('status')
										->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
										->where('ecc.id = ' . $ccid);

									$db->setQuery($query);
									$status = $db->loadResult();

									if (in_array($status, $step_data->entry_status) && $step_data->start_date <= date('Y-m-d') && $step_data->end_date >= date('Y-m-d')) {
										$can_edit = true;
									}
								}
							}
						}
					}
				}
			}
		}

		if (!$can_see) {
			$this->app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$this->app->redirect('/');
		}

		$current_url = '/index.php?option=com_fabrik&view=' . $view . '&formid=' . $form_model->getId() . '&tmpl=component&iframe=1&' . $db_table_name . '___ccid=' . $ccid . '&' . $db_table_name . '___step_id=' . $step_id . '&rowid=' . $current_row_id;
		$final_url = $current_url;

		if (!empty($step_data) && $can_edit && $view === 'form') {
			if ($step_data->multiple == 0) {
				// if step_data is not multiple, we need to redirect to the unique row for this ccid
				$query->clear()
					->select('id')
					->from($db_table_name)
					->where($db->quoteName('ccid') . ' = ' . $ccid);

				$db->setQuery($query);
				$row_id = $db->loadResult();
			} else {
				// if multiple, we need to redirect to the row of the current user if it exists
				$query->clear()
					->select('id')
					->from($db_table_name)
					->where($db->quoteName('ccid') . ' = ' . $ccid)
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
