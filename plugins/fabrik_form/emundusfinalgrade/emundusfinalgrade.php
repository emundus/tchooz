<?php
/**
 * @version 2: emunduscampaign 2019-04-11 Hugo Moracchini
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Création de dossier de candidature automatique.
 */

// No direct access
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
 * Update state from final_grade
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.emundusfinalgrade
 * @since       3.0
 */

class PlgFabrik_FormEmundusFinalGrade extends plgFabrik_Form {

    /**
     * Get an element name
     *
     * @param   string  $pname  Params property name to look up
     * @param   bool    $short  Short (true) or full (false) element name, default false/full
     *
     * @return	string	element full name
     */
    public function getFieldName($pname, $short = false) {
        $params = $this->getParams();

        if ($params->get($pname) == '') {
            return '';
        }

        $elementModel = FabrikWorker::getPluginManager()->getElementPlugin($params->get($pname));

        return $short ? $elementModel->getElement()->name : $elementModel->getFullName();
    }

    /**
     * Get the fields value regardless of whether its in joined data or no
     *
     * @param   string  $pname    Params property name to get the value for
     * @param   mixed   $default  Default value
     *
     * @return  mixed  value
     */
    public function getParam($pname, $default = '') {
        $params = $this->getParams();

        if ($params->get($pname) == '') {
            return $default;
        }

        return $params->get($pname);
    }


	public function onBeforeLoad() {
		$listModel = $this->getModel()->getListModel();
		$table     = $listModel->getTable();

		$query = $this->_db->getQuery(true);
		$user =  $this->app->getIdentity();

		$r = $this->app->getInput()->get('r', 0);
		$formid = $this->app->getInput()->getInt('formid', 256);
		$rowid = $this->app->getInput()->get('rowid',0);
		$student_id = $this->app->getInput()->getInt('student_id', 0) ?: $this->data['student_id'];
		if (!empty($student_id))
		{
			$student = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($student_id);
			echo '<div class="tw-bg-white tw-pb-4"><h2 class="tw-bg-white tw-pb-3 tw-border-b">' . $student->name . '</h2></div>';
		}

		$inputs = $this->app->getInput()->getArray();
		$fnum = $this->app->getInput()->getArray()[$table->db_table_name . '___fnum'];
		$view = strpos(Uri::getInstance()->getPath(), '/details/') !== false ? 'details' : 'form';

		if(!empty($fnum) && is_array($fnum)) {
			$fnum = $fnum['value'];
		}

		if (empty($fnum) || empty($student_id)) {
			if (!empty($rowid)) {
				$query->select('fnum, student_id')
					->from('#__emundus_final_grade')
					->where('id = ' . $rowid);

				try {
					$this->_db->setQuery($query);
					$decision_row = $this->_db->loadAssoc();

					if (!empty($decision_row)) {
						$fnum = $decision_row['fnum'];
						$student_id = $decision_row['student_id'];
					}
				} catch (Exception $e) {
					Log::add('Failed to find fnum from rowid ' . $rowid . ' ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
				}
			} else {
				$fnum = '{jos_emundus_final_grade___fnum}';
				$student_id = '{jos_emundus_final_grade___student_id}';
			}
		}

		require_once(JPATH_SITE.'/components/com_emundus/models/decision.php');
		$m_decision = new EmundusModelDecision();
		$decision = $m_decision->getDecisionUrl($fnum,$formid,$rowid,$student_id,1, $view);

		if(!empty($decision)) {
			$event_datas = [
				'formid' => $formid,
				'rowid' => $rowid,
				'student_id' => $student_id,
				'fnum' => $fnum
			];
			PluginHelper::importPlugin('emundus', 'custom_event_handler');
			$this->app->triggerEvent('onCallEventHandler', ['onRenderFinalgrade', ['event_datas' => $event_datas]]);
		}

		$this->app->enqueueMessage($decision['message']);
		if($r != 1) {
			$this->app->redirect($decision['url']);
		}

		return true;
	}

	public function onBeforeProcess() {
		$formModel = $this->getModel();

		PluginHelper::importPlugin('emundus','custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', ['onBeforeSubmitFinalgrade', ['formModel' => $formModel]]);
	}

	public function onAfterProcess() {
		$formModel = $this->getModel();

		PluginHelper::importPlugin('emundus','custom_event_handler');
		$this->app->triggerEvent('onCallEventHandler', ['onAfterSubmitFinalgrade', ['formModel' => $formModel]]);
	}

    public function onBeforeCalculations() {

	    jimport('joomla.log.log');
	    Log::addLogger(array('text_file' => 'com_emundus.emundus-final-grade.php'), Log::ALL, array('com_emundus.emundus-final-grade'));

	    $formModel = $this->getModel();

	    $fnum = $formModel->formData['fnum_raw'];
	    $status = is_array($formModel->formData['final_grade_raw']) ? $formModel->formData['final_grade_raw'][0] : $formModel->formData['final_grade_raw'];

	    if (!empty($fnum) && !empty($status)) {
		    require_once(JPATH_SITE.'/components/com_emundus/models/files.php');
		    $m_files = new EmundusModelFiles();
		    $m_files->updateState([$fnum], $status);
	    }

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
          title: '" . Text::_('COM_EMUNDUS_DECISION_SAVED') . "',
          showConfirmButton: false,
          timer: 2000,
          customClass: {
            title: 'em-swal-title',
          }
        }).then((result) => {
		  history.go(-1);
		});
      });
      </script>");
    }
}
