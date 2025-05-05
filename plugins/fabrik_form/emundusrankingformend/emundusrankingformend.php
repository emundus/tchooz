<?php
/**
 * @version 2: emundusisapplicationsent 2018-12-04 Hugo Moracchini
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Locks access to a file if the file is not of a certain status.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';
require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');


use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Run emundus triggers link to Fabrik events
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */

class PlgFabrik_FormEmundusrankingformend extends plgFabrik_Form
{
	public function onBeforeLoad() {
		$formModel = $this->getModel();
		$table = $formModel->getTableName();

		$app = Factory::getApplication();
		$input = $app->input;
		$view = $input->getString('view', 'form');
		$fnum = $input->getString($table .  '___fnum', '');
		$current_user = $app->getIdentity();

		if (!empty($fnum)) {
			if (is_array($fnum)) {
				$fnum = current($fnum);
			}

			if (!EmundusHelperAccess::asAccessAction(1, 'r', $current_user->id, $fnum)) {
				$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
				$app->redirect('/');
			}

			require_once(JPATH_ROOT . '/components/com_emundus/models/ranking.php');
			$m_ranking = new EmundusModelRanking();
			$current_user_hierarchy_id = $m_ranking->getUserHierarchy($current_user->id);

			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select('*')
				->from('#__emundus_ranking_hierarchy')
				->where('form_id = ' . $db->quote($formModel->getId()));

			$db->setQuery($query);
			$hierarchy = $db->loadObject();

			if (!EmundusHelperAccess::asCoordinatorAccessLevel($current_user->id) && $current_user_hierarchy_id != $hierarchy->id) {
				$app->enqueueMessage(Text::_('RANKING_FORM_READ_ONLY_ACCESS'));
				$view = 'details';
			}

			if ($view !== 'details') {
				$selects = [];

				if (!empty($hierarchy->package_start_date_field)) {
					list($start_date_table, $field) = explode('.', $hierarchy->package_start_date_field);

					$selects[] = $start_date_table . '.' . $field . ' as form_start_date';
				}

				if (!empty($hierarchy->package_end_date_field)) {
					list($end_date_table, $field) = explode('.', $hierarchy->package_end_date_field);

					$selects[] = $end_date_table . '.' . $field . ' as form_end_date';
				}

				$select = implode(', ', $selects);

				if (!empty($select)) {
					$query->clear()
						->select($select)
						->from('#__emundus_campaign_candidature')
						->leftJoin('#__emundus_setup_campaigns ON #__emundus_setup_campaigns.id = #__emundus_campaign_candidature.campaign_id')
						->leftJoin('#__emundus_setup_campaigns_more ON #__emundus_setup_campaigns_more.campaign_id = #__emundus_setup_campaigns.id')
						->where('#__emundus_campaign_candidature.fnum = ' . $db->quote($fnum));

					$db->setQuery($query);
					$form_dates = $db->loadObject();

					$timestamp_start = !empty($form_dates->form_start_date) ? strtotime($form_dates->form_start_date) : 0;
					$timestamp_end = !empty($form_dates->form_end_date) ? strtotime($form_dates->form_end_date) : 0;
					$timestamp_now = time();

					if (!empty($form_dates->form_start_date) && $timestamp_now < $timestamp_start) {
						if (!EmundusHelperAccess::asCoordinatorAccessLevel($current_user->id)) {
							$app->enqueueMessage(Text::_('EMUNDUS_RANKING_FORM_NOT_YET_OPEN'), 'error');
							$view = 'details';
						} else {
							$app->enqueueMessage(Text::_('EMUNDUS_RANKING_FORM_NOT_YET_OPEN_COORDINATOR'), 'warning');
						}
					}

					if (!empty($form_dates->form_end_date) && $timestamp_now > $timestamp_end) {
						if (!EmundusHelperAccess::asCoordinatorAccessLevel($current_user->id)) {
							$app->enqueueMessage(Text::_('EMUNDUS_RANKING_FORM_CLOSED'), 'error');
							$view = 'details';
						} else {
							$app->enqueueMessage(Text::_('EMUNDUS_RANKING_FORM_CLOSED_COORDINATOR'), 'warning');
						}
					}
				}
			}

			$query->clear()
				->select('id')
				->from($table)
				->where('fnum = ' . $db->quote($fnum));

			$db->setQuery($query);
			$id = $db->loadResult();

			if (!empty($id)) {
				$app->redirect(Route::_('index.php?option=com_fabrik&view=' . $view . '&formid=' . $formModel->getId() . '&rowid=' . $id .  '&tmpl=component&iframe=1'));
			}
		}
	}

	public function onAfterProcess()
	{
		$formModel = $this->getModel();
		$fnum = $formModel->formData['fnum_raw'];
		$table = $formModel->getTableName();

		$redirect = Route::_('index.php?option=com_fabrik&view=form&formid=' . $formModel->getId() . '&tmpl=component&iframe=1&' . $table . '___fnum=' . $fnum);
		echo '<script>
			const FinishedHiearchyFormEvent = new CustomEvent("FinishedHiearchyFormEvent", {
				detail: {
					fnum: ' . $fnum . '
				}
			});
			            
            window.dispatchEvent(FinishedHiearchyFormEvent);
            window.postMessage({ type: "FinishedHiearchyFormEvent", fnum: ' . $fnum . ' }, "*");
            window.parent.postMessage({ type: "FinishedHiearchyFormEvent", fnum: ' . $fnum . ' }, "*");
            
            window.location.href = "' . $redirect . '";
		</script>';
		die();
	}
}