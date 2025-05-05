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
	private $_app;

	public $elements;
	public $form;
	public $program;

	function __construct($config = array())
	{
		$this->_app = Factory::getApplication();
		$this->_user = $this->_app->getIdentity();
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
		$jinput     = $this->_app->getInput();
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

		if ($this->form === 'evaluation_steps') {
			require_once(JPATH_ROOT . '/components/com_emundus/models/evaluation.php');
			$m_eval = new EmundusModelEvaluation;

			$this->elements = $m_eval->getEvaluationStepsElementsName(0, 0, $code);
		}
		elseif ($this->form === 'campaign') {
			require_once(JPATH_ROOT . '/components/com_emundus/models/campaign.php');
			$m_campaign = new EmundusModelCampaign;

			$this->elements = $m_campaign->getAllCampaignElements();
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