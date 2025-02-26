<?php
/**
 * @package    eMundus
 * @subpackage Components
 * @link       http://www.emundus.fr
 * @license    GNU/GPL
 * @author     Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;


/**
 * eMundus Component Controller
 *
 * @package    Joomla.Tutorials
 * @subpackage Components
 */
class EmundusControllerGroups extends BaseController
{

	protected $app;

	private $_user;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     \JController
	 * @since   1.0.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @return  DisplayController  This object to support chaining.
	 *
	 * @since   1.0.0
	 */
	function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view')) {
			$default = 'groups';
			$this->input->set('view', $default);
		}
		$user   = JFactory::getUser();
		$menu   = $this->app->getMenu()->getActive();
		$access = !empty($menu) ? $menu->access : 0;
		if (!EmundusHelperAccess::isAllowedAccessLevel($user->id, $access)) {
			parent::display();
		}
	}

	function clear()
	{
		unset($_SESSION['s_elements']);
		unset($_SESSION['s_elements_values']);
		$limitstart       = $this->input->get('limitstart', null, 'POST', 'none', 0);
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);
		$Itemid           = $this->app->getMenu()->getActive()->id;
		$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir . '&Itemid=' . $Itemid);
	}

	////// AFFECT ASSESSOR ///////////////////

	/**
	 * @deprecated
	 */
	function setAssessor($reqids = null)
	{
		//$allowed = array("Super Users", "Administrator", "Editor");
		$user   = JFactory::getUser();
		$menu   = $this->app->getMenu()->getActive();
		$access = !empty($menu) ? $menu->access : 0;
		if (!EmundusHelperAccess::isAllowedAccessLevel($user->id, $access))
			die("You are not allowed to access to this page.");
		$db               = JFactory::getDBO();
		$ids              = $this->input->get('ud', null, 'POST', 'array', 0);
		$ag_id            = $this->input->get('assessor_group', null, 'POST', 'none', 0);
		$au_id            = $this->input->get('assessor_user', null, 'POST', 'none', 0);
		$limitstart       = $this->input->get('limitstart', null, 'POST', 'none', 0);
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);

		if (empty($ids) && !empty($reqids))
			$ids = $reqids;
		JArrayHelper::toInteger($ids, null);
		if (!empty($ids)) {
			foreach ($ids as $id) {
				if (!empty($ag_id) && isset($ag_id)) {
					$db->setQuery('SELECT * FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND group_id=' . $ag_id);
					$cpt = $db->loadResultArray();

					//** Delete members of group to add **/
					$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND user_id IN (select user_id from #__emundus_groups where group_id=' . $ag_id . ')';
					$db->setQuery($query);
					$db->execute() or die($db->getErrorMsg());

					if (count($cpt) == 0)
						$db->setQuery('INSERT INTO #__emundus_groups_eval (applicant_id, group_id, user_id) VALUES (' . $id . ',' . $ag_id . ',null)');

				}
				elseif (!empty($au_id) && isset($au_id)) {
					$db->setQuery('SELECT * FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND user_id=' . $au_id);
					$cpt = $db->loadResultArray();

					$db->setQuery('SELECT * FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND group_id IN (select group_id from #__emundus_groups where user_id=' . $au_id . ')');
					$cpt_grp = $db->loadResultArray();

					if (count($cpt) == 0 && count($cpt_grp) == 0)
						$db->setQuery('INSERT INTO #__emundus_groups_eval (applicant_id, group_id, user_id) VALUES (' . $id . ',null,' . $au_id . ')');
				}
				else {
					$db->setQuery('DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id);
				}
				$db->execute() or die($db->getErrorMsg());
			}
		}
		if (count($ids) > 1)
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANTS_AFFECTED') . count($ids), 'message');
		elseif (count($ids) == 1)
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANT_AFFECTED') . count($ids), 'message');
		else
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir);
	}

	////// UNAFFECT ASSESSOR ///////////////////

	/**
	 * @deprecated
	 */
	function unsetAssessor($reqids = null)
	{
		//$allowed = array("Super Users", "Administrator", "Editor");
		$user   = JFactory::getUser();
		$menu   = $this->app->getMenu()->getActive();
		$access = !empty($menu) ? $menu->access : 0;
		if (!EmundusHelperAccess::isAllowedAccessLevel($user->id, $access))
			die("You are not allowed to access to this page.");
		$db               = JFactory::getDBO();
		$ids              = $this->input->get('ud', null, 'POST', 'array', 0);
		$ag_id            = $this->input->get('assessor_group', null, 'POST', 'none', 0);
		$au_id            = $this->input->get('assessor_user', null, 'POST', 'none', 0);
		$limitstart       = $this->input->get('limitstart', null, 'POST', 'none', 0);
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);

		if (empty($ids) && !empty($reqids)) {
			$ids = $reqids;
		}
		JArrayHelper::toInteger($ids, null);
		if (!empty($ids)) {
			foreach ($ids as $id) {
				if (!empty($ag_id) && isset($ag_id)) {
					$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND group_id=' . $ag_id;
					$db->setQuery($query);
					$db->execute() or die($db->getErrorMsg());
				}
				elseif (!empty($au_id) && isset($au_id)) {
					$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND user_id=' . $au_id;
					$db->setQuery($query);
					$db->execute() or die($db->getErrorMsg());
				}
			}
		}
		if (count($ids) > 1)
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANTS_UNAFFECTED') . count($ids), 'message');
		elseif (count($ids) == 1)
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANT_UNAFFECTED') . count($ids), 'message');
		else
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir);
	}

	/**
	 * @deprecated
	 */
	function delassessor()
	{
		$user = JFactory::getUser();
		if (!EmundusHelperAccess::isAdministrator($user->id) && !EmundusHelperAccess::isCoordinator($user->id)) {
			$this->setRedirect('index.php', Text::_('You are not allowed to access to this page.'), 'error');

			return;
		}
		$uid              = $this->input->get('uid', null, 'GET', null, 0);
		$aid              = $this->input->get('aid', null, 'GET', null, 0);
		$pid              = $this->input->get('pid', null, 'GET', null, 0);
		$limitstart       = $this->input->get('limitstart', null, 'GET', null, 0);
		$filter_order     = $this->input->get('filter_order', null, 'GET', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'GET', null, 0);

		if (!empty($aid) && is_numeric($aid)) {
			$db    = JFactory::getDBO();
			$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $db->Quote($aid);
			if (!empty($pid) && is_numeric($pid))
				$query .= ' AND group_id=' . $db->Quote($pid);
			if (!empty($uid) && is_numeric($uid))
				$query .= ' AND user_id=' . $db->Quote($uid);
			$db->setQuery($query);
			$db->execute();
		}
		$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_ACTIONS_ACTION_DONE'), 'message');
	}

	function defaultEmail($reqids = null)
	{
		//$allowed = array("Super Users", "Administrator", "Editor");
		$user   = JFactory::getUser();
		$menu   = $this->app->getMenu()->getActive();
		$access = !empty($menu) ? $menu->access : 0;
		if (!EmundusHelperAccess::isAllowedAccessLevel($user->id, $access))
			die("You are not allowed to access to this page.");
		$mainframe        = $this->app;
		$db               = JFactory::getDBO();
		$limitstart       = $this->input->get('limitstart', null, 'POST', 'none', 0);
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);

		// List of evaluators
		$query = 'SELECT eg.user_id
					FROM `#__emundus_groups` as eg
					LEFT JOIN `#__emundus_groups_eval` as ege on ege.group_id=eg.group_id
					WHERE eg.user_id is not null
					GROUP BY eg.user_id';
		$db->setQuery($query);
		$users_1 = $db->loadResultArray();

		$query = 'SELECT ege.user_id
					FROM `#__emundus_groups_eval` as ege
					WHERE ege.user_id is not null
					GROUP BY ege.user_id';
		$db->setQuery($query);
		$users_2 = $db->loadResultArray();

		$users = array_merge_recursive($users_1, $users_2);

		// R�cup�ration des donn�es du mail
		$query = 'SELECT id, subject, emailfrom, name, message
						FROM #__emundus_setup_emails
						WHERE lbl like "assessors_set"';
		$db->setQuery($query);
		$db->query();
		$obj = $db->loadObjectList();

		// setup mail
		if (isset($current_user->email)) {
			$from     = $current_user->email;
			$from_id  = $current_user->id;
			$fromname = $current_user->name;
		}
		elseif ($mainframe->getCfg('mailfrom') != '' && $mainframe->getCfg('fromname') != '') {
			$from     = $mainframe->getCfg('mailfrom');
			$fromname = $mainframe->getCfg('fromname');
			$from_id  = 62;
		}
		else {
			$query = 'SELECT id, name, email' .
				' FROM #__users' .
				// administrator
				' WHERE gid = 25 LIMIT 1';
			$db->setQuery($query);
			$admin    = $db->loadObject();
			$from     = $admin->email;
			$from_id  = $admin->id;
			$fromname = $admin->name;
		}

		// Evaluations criterias
		$query = 'SELECT id, label, sub_labels
						FROM #__fabrik_elements
						WHERE group_id=41 AND (plugin like "fabrikradiobutton" OR plugin like "fabrikdropdown")';
		$db->setQuery($query);
		$db->query();
		$eval_criteria = $db->loadObjectList();

		$eval = '<ul>';
		foreach ($eval_criteria as $e) {
			$eval .= '<li>' . $e->label . ' (' . $e->sub_labels . ')</li>';
		}
		$eval .= '</ul>';

		// template replacements
		$patterns = array('/\[ID\]/', '/\[NAME\]/', '/\[EMAIL\]/', '/\[APPLICANTS_LIST\]/', '/\[SITE_URL\]/', '/\[EVAL_CRITERIAS\]/', '/\[EVAL_PERIOD\]/', '/\n/');
		$error    = 0;
		foreach ($users as $uid) {
			$user = JFactory::getUser($uid);

			$query = 'SELECT applicant_id
						FROM #__emundus_groups_eval
						WHERE user_id=' . $user->id . ' OR group_id IN (select group_id from #__emundus_groups where user_id=' . $user->id . ')';
			$db->setQuery($query);
			$db->query();
			$applicants = $db->loadResultArray();

			if (count($applicants) > 0) {
				$list = '<ul>';
				foreach ($applicants as $ap) {
					$app  = JFactory::getUser($ap);
					$list .= '<li>' . $app->name . ' [' . $app->id . ']</li>';
				}
				$list .= '</ul>';

				$query = 'SELECT esp.evaluation_start, esp.evaluation_end
						FROM #__emundus_setup_profiles AS esp
						LEFT JOIN #__emundus_users AS eu ON eu.profile=esp.id
						WHERE user_id=' . $user->id;
				$db->setQuery($query);
				$db->query();
				$period = $db->loadRow();

				$period_str = strftime(Text::_('DATE_FORMAT_LC2'), strtotime($period[0])) . ' ' . Text::_('COM_EMUNDUS_TO') . ' ' . strftime(Text::_('DATE_FORMAT_LC2'), strtotime($period[1]));

				$replacements = array($user->id, $user->name, $user->email, $list, JURI::base(), $eval, $period_str, '<br />');
				// template replacements
				$body = preg_replace($patterns, $replacements, $obj[0]->message);
				unset($replacements);
				unset($list);
				// mail function
				if (JUtility::sendMail($from, $obj[0]->name, $user->email, $obj[0]->subject, $body, 1)) {
					//if ($body === 0) {
					// Due to the server being located in France but the platform possibly being elsewhere, we have to adapt to the timezone.

					$sql = "INSERT INTO `#__messages` (`user_id_from`, `user_id_to`, `subject`, `message`, `date_time`)
						VALUES ('" . $from_id . "', '" . $user->id . "', '" . $obj[0]->subject . "', '" . $body . "', NOW())";
					$db->setQuery($sql);
					$db->execute();
				}
				else {
					$error++;
				}
			}
		}
		if ($error > 0)
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('ACTION_ABORDED'), 'error');
		else
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_ACTIONS_ACTION_DONE'), 'message');
	}


	public function addgroups()
	{
		$tab = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$data = $this->input->get('data', null, 'POST', 'none', 0);

			require_once(JPATH_ROOT . '/components/com_emundus/models/groups.php');
			$m_groups = $this->getModel('Groups');
			$result   = $m_groups->addGroupsByProgrammes($data);

			if ($result === true) {
				$tab = array('status' => 1, 'msg' => Text::_('GROUPS_ADDED'), 'data' => $result);
			} else {
				$tab['msg'] = Text::_('ERROR_CANNOT_ADD_GROUPS');
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getGroups()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/groups.php');
			$m_groups = new EmundusModelGroups();
			$groups = $m_groups->getGroups();

			$response = array('status' => true, 'msg' => Text::_('GROUPS_RETRIEVED'), 'data' => array_values($groups));
		}

		echo json_encode((object) $response);
		exit;
	}

}
