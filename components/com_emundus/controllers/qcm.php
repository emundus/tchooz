<?php
/**
 * Messages controller used for the creation and emission of messages from the platform.
 *
 * @package    Joomla
 * @subpackage Emundus
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Hugo Moracchini
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * eMundus Component Controller
 *
 * @package    Joomla.eMundus
 * @subpackage Components
 */
class EmundusControllerQcm extends BaseController
{

	protected $app;

	private $model;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'qcm.php');

		$this->app   = Factory::getApplication();
		$this->model = $this->getModel('qcm');
	}

	public function getQuestions()
	{
		$results = [];

		$questions = $this->input->getString('questions');

		// todo: check user is inside qcm environment ?
		if (!empty($questions)) {
			$m_qcm   = $this->model;
			$results = $m_qcm->getQuestions($questions);
		}

		echo json_encode((object) $results);
		exit;
	}

	public function saveanwser()
	{
		$session      = JFactory::getSession();
		$current_user = $session->get('emundusUser');

		$m_qcm = $this->model;


		$answers  = $this->input->getRaw('answer');
		$question = $this->input->getString('question');
		$formid   = $this->input->getString('formid');
		$module   = $this->input->getInt('module');

		$results = $m_qcm->saveAnswer($question, $answers, $current_user, $formid, $module);

		echo json_encode((object) $results);
		exit;
	}

	public function updatepending()
	{
		$session      = JFactory::getSession();
		$current_user = $session->get('emundusUser');

		$m_qcm = $this->model;


		$pending = $this->input->getInt('pending');
		$formid  = $this->input->getInt('formid');

		$results = $m_qcm->updatePending($pending, $current_user, $formid);

		echo json_encode((object) $results);
		exit;
	}

	public function getintro()
	{
		$m_qcm = $this->model;


		$module = $this->input->getInt('module');

		$results = $m_qcm->getIntro($module);

		echo json_encode((object) $results);
		exit;
	}

}
