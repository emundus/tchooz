<?php
/**
 * @package    eMundus
 * @subpackage Components
 *             components/com_emundus/emundus.php
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// no direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

use Joomla\CMS\Factory;

/**
 * HTML View class for the Emundus Component
 *
 * @package    Emundus
 */
class EmundusViewExport_select_columns extends JViewLegacy
{
	private $_user;

	public $elements;
	public $form;
	public $program;

	function __construct($config = array())
	{
		$this->_user = Factory::getApplication()->getIdentity();
		if (!EmundusHelperAccess::asPartnerAccessLevel($this->_user->id)) {
			die(JText::_('ACCESS_DENIED'));
		}

		require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
		require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/programme.php');

		parent::__construct($config);
	}

	function display($tpl = null)
	{
		$jinput     = Factory::getApplication()->getInput();
		$prg        = $jinput->getString('code', null);
		$this->form = $jinput->get('form', null);
		$camp       = $jinput->get('camp', null);
		$profile    = $jinput->get('profile', null);
		$all        = $jinput->get('all', null);

		if (!empty($prg)) {
			$m_program = new EmundusModelProgramme();
			$program = $m_program->getProgramme($prg);
		}
		$code    = [$prg];
		$camps   = [$camp];

		if ($this->form === 'decision') {
			require_once(JPATH_ROOT . '/components/com_emundus/models/decision.php');
			$m_decision  = new EmundusModelDecision;
			$this->elements = $m_decision->getDecisionElementsName(0, 0, $code, $all);
		}
		elseif ($this->form === 'admission') {
			require_once(JPATH_ROOT . '/components/com_emundus/models/admission.php');
			$m_admission = new EmundusModelAdmission;
			$this->elements = $m_admission->getApplicantAdmissionElementsName(0, 0, $code, $all);
		}
		elseif ($this->form === 'evaluation') {
			require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
			$m_eval = new EmundusModelEvaluation;
			$this->elements = $m_eval->getEvaluationElementsName(0, 0, $code, $all);
		}
		else {
			$this->elements = EmundusHelperFiles::getElements($code, $camps, [], $profile);
		}

		$allowed_groups = EmundusHelperAccess::getUserFabrikGroups($this->_user->id);
		if ($allowed_groups !== true) {
			foreach ($this->elements as $key => $elt) {
				if (!in_array($elt->group_id, $allowed_groups)) {
					unset($this->elements[$key]);
				}
			}
		}

		if(!empty($program)) {
			$this->program = $program->label;
		}

		parent::display($tpl);
	}
}