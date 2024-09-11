<?php
/**
 * @version     1.34.0: emundusisevaluatedbyme 2022-12-02 Brice HUBINET
 * @package     Fabrik
 * @copyright   Copyright (C) 2022 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * @description Check how can the connected user can access to an evaluation
 */

// No direct access
use JetBrains\PhpStorm\NoReturn;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */
class PlgFabrik_FormEmundusisevaluatedbyme extends plgFabrik_Form
{

	public function onLoad(): void
	{
		$listModel = $this->getModel()->getListModel();
		$table     = $listModel->getTable();

		$student_id = $this->app->getInput()->getInt($table->db_table_name . '___student_id', 0) ?: $this->data['student_id'];
		if (!empty($student_id))
		{
			$student = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($student_id);
			echo '<div class="tw-bg-white tw-pb-4"><h2 class="tw-bg-white tw-pb-3 tw-border-b">' . $student->name . '</h2></div>';
		}
	}

	public function onBeforeLoad(): bool
	{
		$query = $this->_db->getQuery(true);
		$user  = $this->app->getIdentity();

		$r          = $this->app->input->get('r', 0);
		$formid     = $this->app->input->get('formid', '256');
		$rowid      = $this->app->input->get('rowid');
		$preview      = $this->app->input->get('preview',0);
		$student_id = $this->app->input->get('jos_emundus_evaluations___student_id') ?: '';
		$fnum       = $this->app->input->get('jos_emundus_evaluations___fnum') ?: '';
		$view       = strpos(Uri::getInstance()->getPath(), '/details/') !== false ? 'details' : 'form';

		if ($preview == 1 && EmundusHelperAccess::asCoordinatorAccessLevel($user->id)) {
			return true;
		}

		if (empty($fnum) || empty($student_id))
		{
			if (!empty($rowid))
			{
				$query->select('fnum, student_id')
					->from('#__emundus_evaluations')
					->where('id = ' . $rowid);

				try
				{
					$this->_db->setQuery($query);
					$evaluation_row = $this->_db->loadAssoc();

					if (!empty($evaluation_row))
					{
						$fnum       = $evaluation_row['fnum'];
						$student_id = $evaluation_row['student_id'];
					}
				}
				catch (Exception $e)
				{
					Log::add('Failed to find fnum from rowid ' . $rowid . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			}
			else
			{
				$fnum       = '{jos_emundus_evaluations___fnum}';
				$student_id = '{jos_emundus_evaluations___student_id}';
			}
		}

		require_once(JPATH_SITE . '/components/com_emundus/models/evaluation.php');
		$m_evaluation = new EmundusModelEvaluation();
		$evaluation   = $m_evaluation->getEvaluationUrl($fnum, $formid, $rowid, $student_id, 1, $view);

		if (!empty($evaluation))
		{
			$event_datas = [
				'formid'     => $formid,
				'rowid'      => $rowid,
				'student_id' => $student_id,
				'fnum'       => $fnum
			];
			PluginHelper::importPlugin('emundus', 'custom_event_handler');
			$this->app->triggerEvent('onCallEventHandler', ['onRenderEvaluation', ['event_datas' => $event_datas]]);
		}

		$this->app->enqueueMessage($evaluation['message']);

		if ($r != 1)
		{
			$this->app->redirect($evaluation['url']);
		}

		return true;
	}

	public function onBeforeProcess(): void
	{
		$formModel = $this->getModel();

		PluginHelper::importPlugin('emundus', 'custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', ['onBeforeSubmitEvaluation', ['formModel' => $formModel]]);
	}

	#[NoReturn] public function onAfterProcess(): void
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
