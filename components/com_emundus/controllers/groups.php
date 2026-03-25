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

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Symfony\Component\Yaml\Yaml;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Actions\GroupAccessEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\List\AdditionalColumnTag;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\ColorEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;
use Tchooz\Enums\StatusEnum;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Actions\GroupAccessRepository;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Groups\GroupRepository;
use Tchooz\Repositories\Programs\ProgramRepository;
use Tchooz\Repositories\User\EmundusUserRepository;

class EmundusControllerGroups extends EmundusController
{
	private GroupRepository $groupRepository;

	private GroupAccessRepository $groupAccessRepository;

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->groupRepository = new GroupRepository();
		$this->groupAccessRepository = new GroupAccessRepository();
	}

	public function setGroupRepository(GroupRepository $groupRepository): EmundusControllerGroups
	{
		$this->groupRepository = $groupRepository;

		return $this;
	}

	public function setGroupAccessRepository(GroupAccessRepository $groupAccessRepository): EmundusControllerGroups
	{
		$this->groupAccessRepository = $groupAccessRepository;

		return $this;
	}

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached.
	 * @param   boolean  $urlparams  An array of safe URL parameters and their variable types.
	 *
	 * @return  DisplayController  This object to support chaining.
	 *
	 * @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
	 *
	 * @since      1.0.0
	 */
	function display($cachable = false, $urlparams = false)
	{
		// Set a default view if none exists
		if (!$this->input->get('view'))
		{
			$default = 'groups';
			$this->input->set('view', $default);
		}
		$user   = JFactory::getUser();
		$menu   = $this->app->getMenu()->getActive();
		$access = !empty($menu) ? $menu->access : 0;
		if (!EmundusHelperAccess::isAllowedAccessLevel($user->id, $access))
		{
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
		{
			die("You are not allowed to access to this page.");
		}
		$db               = JFactory::getDBO();
		$ids              = $this->input->get('ud', null, 'POST', 'array', 0);
		$ag_id            = $this->input->get('assessor_group', null, 'POST', 'none', 0);
		$au_id            = $this->input->get('assessor_user', null, 'POST', 'none', 0);
		$limitstart       = $this->input->get('limitstart', null, 'POST', 'none', 0);
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);

		if (empty($ids) && !empty($reqids))
		{
			$ids = $reqids;
		}
		JArrayHelper::toInteger($ids, null);
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				if (!empty($ag_id) && isset($ag_id))
				{
					$db->setQuery('SELECT * FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND group_id=' . $ag_id);
					$cpt = $db->loadResultArray();

					//** Delete members of group to add **/
					$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND user_id IN (select user_id from #__emundus_groups where group_id=' . $ag_id . ')';
					$db->setQuery($query);
					$db->execute() or die($db->getErrorMsg());

					if (count($cpt) == 0)
					{
						$db->setQuery('INSERT INTO #__emundus_groups_eval (applicant_id, group_id, user_id) VALUES (' . $id . ',' . $ag_id . ',null)');
					}

				}
				elseif (!empty($au_id) && isset($au_id))
				{
					$db->setQuery('SELECT * FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND user_id=' . $au_id);
					$cpt = $db->loadResultArray();

					$db->setQuery('SELECT * FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND group_id IN (select group_id from #__emundus_groups where user_id=' . $au_id . ')');
					$cpt_grp = $db->loadResultArray();

					if (count($cpt) == 0 && count($cpt_grp) == 0)
					{
						$db->setQuery('INSERT INTO #__emundus_groups_eval (applicant_id, group_id, user_id) VALUES (' . $id . ',null,' . $au_id . ')');
					}
				}
				else
				{
					$db->setQuery('DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id);
				}
				$db->execute() or die($db->getErrorMsg());
			}
		}
		if (count($ids) > 1)
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANTS_AFFECTED') . count($ids), 'message');
		}
		elseif (count($ids) == 1)
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANT_AFFECTED') . count($ids), 'message');
		}
		else
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir);
		}
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
		{
			die("You are not allowed to access to this page.");
		}
		$db               = JFactory::getDBO();
		$ids              = $this->input->get('ud', null, 'POST', 'array', 0);
		$ag_id            = $this->input->get('assessor_group', null, 'POST', 'none', 0);
		$au_id            = $this->input->get('assessor_user', null, 'POST', 'none', 0);
		$limitstart       = $this->input->get('limitstart', null, 'POST', 'none', 0);
		$filter_order     = $this->input->get('filter_order', null, 'POST', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'POST', null, 0);

		if (empty($ids) && !empty($reqids))
		{
			$ids = $reqids;
		}
		JArrayHelper::toInteger($ids, null);
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				if (!empty($ag_id) && isset($ag_id))
				{
					$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND group_id=' . $ag_id;
					$db->setQuery($query);
					$db->execute() or die($db->getErrorMsg());
				}
				elseif (!empty($au_id) && isset($au_id))
				{
					$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $id . ' AND user_id=' . $au_id;
					$db->setQuery($query);
					$db->execute() or die($db->getErrorMsg());
				}
			}
		}
		if (count($ids) > 1)
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANTS_UNAFFECTED') . count($ids), 'message');
		}
		elseif (count($ids) == 1)
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_GROUPS_MESSAGE_APPLICANT_UNAFFECTED') . count($ids), 'message');
		}
		else
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir);
		}
	}

	/**
	 * @deprecated
	 */
	function delassessor()
	{
		$user = JFactory::getUser();
		if (!EmundusHelperAccess::isAdministrator($user->id) && !EmundusHelperAccess::isCoordinator($user->id))
		{
			$this->setRedirect('index.php', Text::_('You are not allowed to access to this page.'), 'error');

			return;
		}
		$uid              = $this->input->get('uid', null, 'GET', null, 0);
		$aid              = $this->input->get('aid', null, 'GET', null, 0);
		$pid              = $this->input->get('pid', null, 'GET', null, 0);
		$limitstart       = $this->input->get('limitstart', null, 'GET', null, 0);
		$filter_order     = $this->input->get('filter_order', null, 'GET', null, 0);
		$filter_order_Dir = $this->input->get('filter_order_Dir', null, 'GET', null, 0);

		if (!empty($aid) && is_numeric($aid))
		{
			$db    = JFactory::getDBO();
			$query = 'DELETE FROM #__emundus_groups_eval WHERE applicant_id=' . $db->Quote($aid);
			if (!empty($pid) && is_numeric($pid))
			{
				$query .= ' AND group_id=' . $db->Quote($pid);
			}
			if (!empty($uid) && is_numeric($uid))
			{
				$query .= ' AND user_id=' . $db->Quote($uid);
			}
			$db->setQuery($query);
			$db->execute();
		}
		$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_ACTIONS_ACTION_DONE'), 'message');
	}

	/**
	 * @deprecated
	 */
	function defaultEmail($reqids = null)
	{
		//$allowed = array("Super Users", "Administrator", "Editor");
		$user   = JFactory::getUser();
		$menu   = $this->app->getMenu()->getActive();
		$access = !empty($menu) ? $menu->access : 0;
		if (!EmundusHelperAccess::isAllowedAccessLevel($user->id, $access))
		{
			die("You are not allowed to access to this page.");
		}
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
		if (isset($current_user->email))
		{
			$from     = $current_user->email;
			$from_id  = $current_user->id;
			$fromname = $current_user->name;
		}
		elseif ($mainframe->getCfg('mailfrom') != '' && $mainframe->getCfg('fromname') != '')
		{
			$from     = $mainframe->getCfg('mailfrom');
			$fromname = $mainframe->getCfg('fromname');
			$from_id  = 62;
		}
		else
		{
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
		foreach ($eval_criteria as $e)
		{
			$eval .= '<li>' . $e->label . ' (' . $e->sub_labels . ')</li>';
		}
		$eval .= '</ul>';

		// template replacements
		$patterns = array('/\[ID\]/', '/\[NAME\]/', '/\[EMAIL\]/', '/\[APPLICANTS_LIST\]/', '/\[SITE_URL\]/', '/\[EVAL_CRITERIAS\]/', '/\[EVAL_PERIOD\]/', '/\n/');
		$error    = 0;
		foreach ($users as $uid)
		{
			$user = JFactory::getUser($uid);

			$query = 'SELECT applicant_id
						FROM #__emundus_groups_eval
						WHERE user_id=' . $user->id . ' OR group_id IN (select group_id from #__emundus_groups where user_id=' . $user->id . ')';
			$db->setQuery($query);
			$db->query();
			$applicants = $db->loadResultArray();

			if (count($applicants) > 0)
			{
				$list = '<ul>';
				foreach ($applicants as $ap)
				{
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
				if (JUtility::sendMail($from, $obj[0]->name, $user->email, $obj[0]->subject, $body, 1))
				{
					//if ($body === 0) {
					// Due to the server being located in France but the platform possibly being elsewhere, we have to adapt to the timezone.

					$sql = "INSERT INTO `#__messages` (`user_id_from`, `user_id_to`, `subject`, `message`, `date_time`)
						VALUES ('" . $from_id . "', '" . $user->id . "', '" . $obj[0]->subject . "', '" . $body . "', NOW())";
					$db->setQuery($sql);
					$db->execute();
				}
				else
				{
					$error++;
				}
			}
		}
		if ($error > 0)
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('ACTION_ABORDED'), 'error');
		}
		else
		{
			$this->setRedirect('index.php?option=com_emundus&view=groups&limitstart=' . $limitstart . '&filter_order=' . $filter_order . '&filter_order_Dir=' . $filter_order_Dir, Text::_('COM_EMUNDUS_ACTIONS_ACTION_DONE'), 'message');
		}
	}


	/**
	 * @deprecated
	 */
	public function addgroups()
	{
		$tab = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$data = $this->input->get('data', null, 'POST', 'none', 0);

			require_once(JPATH_ROOT . '/components/com_emundus/models/groups.php');
			$m_groups = $this->getModel('Groups');
			$result   = $m_groups->addGroupsByProgrammes($data);

			if ($result === true)
			{
				$tab = array('status' => 1, 'msg' => Text::_('GROUPS_ADDED'), 'data' => $result);
			}
			else
			{
				$tab['msg'] = Text::_('ERROR_CANNOT_ADD_GROUPS');
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getGroups(): EmundusResponse
	{
		require_once(JPATH_ROOT . '/components/com_emundus/models/groups.php');
		$m_groups = new EmundusModelGroups();
		$groups   = $m_groups->getGroups();

		return EmundusResponse::ok(array_values($groups), Text::_('GROUPS_RETRIEVED'));
	}

	/**
	 * @deprecated
	 */
	public function getuserstoshareto()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (
			(EmundusHelperAccess::asPartnerAccessLevel($this->user->id) && EmundusHelperAccess::asAccessAction('share_filters', 'c', $this->user->id)
				||
				EmundusHelperAccess::asAdministratorAccessLevel($this->user->id)
			)
		)
		{
			$m_groups = $this->getModel('Groups');
			$users    = $m_groups->getUsersToShareTo($this->user->id);

			if (!empty($users))
			{
				$response['status'] = true;
				$response['msg']    = Text::_('COM_EMUNDUS_SELECT_USERS');
				$response['data']   = $users;
				$response['code']   = 200;
			}
			else
			{
				$response['msg']  = Text::_('NO_USERS');
				$response['code'] = 200;
			}
		}

		$this->sendJsonResponse($response);
	}

	/**
	 * @deprecated
	 */
	public function getgroupstoshareto()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'code' => 403];

		if (
			(EmundusHelperAccess::asPartnerAccessLevel($this->user->id) && EmundusHelperAccess::asAccessAction('share_filters', 'c', $this->user->id)
				||
				EmundusHelperAccess::asAdministratorAccessLevel($this->user->id)
			)
		)
		{
			$response['msg'] = Text::_('NO_GROUPS');
			$m_groups        = $this->getModel('Groups');
			$user_groups     = $m_groups->getUsersGroups([$this->user->id]);

			if (!empty($user_groups))
			{
				$emundus_config = JComponentHelper::getParams('com_emundus');
				$all_rights_grp = $emundus_config->get('all_rights_group', 1);

				$group_ids = array_map(function ($user_group) {
					return $user_group->group_id;
				}, $user_groups);
				$group_ids = array_unique($group_ids);

				if (in_array($all_rights_grp, $group_ids))
				{
					$groups = $m_groups->getGroups();
				}
				else
				{
					$groups = $m_groups->getGroups($group_ids);
				}

				if (!empty($groups))
				{
					$response['status'] = true;
					$response['msg']    = Text::_('COM_EMUNDUS_SELECT_GROUPS');
					$response['data']   = array_values($groups);
					$response['code']   = 200;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function getallgroups(): EmundusResponse
	{
		$sort      = $this->input->getString('sort', 'ASC');
		$recherche = $this->input->getString('recherche', '');
		$lim       = $this->input->getInt('lim', 0);
		$page      = $this->input->getInt('page', 0);
		$program   = $this->input->getString('program', '');
		$order_by  = $this->input->getString('order_by', 'id');
		$order_by  = $order_by == 'label' ? 'label' : $order_by;

		$emundusConfig = ComponentHelper::getParams('com_emundus');
		$allRightsGroup = $emundusConfig->get('all_rights_group', 1);

		$actionRepository  = new ActionRepository();
		$programAction     = $actionRepository->getByName('program');
		$programAccess     = EmundusHelperAccess::asAccessAction($programAction->getId(), CrudEnum::READ->value, $this->user->id);
		$programEditAccess = EmundusHelperAccess::asAccessAction($programAction->getId(), CrudEnum::UPDATE->value, $this->user->id);

		$filters = [];
		if (!empty($program))
		{
			$filters['esgrc.course'] = $program;
		}
		$order_by = $this->groupRepository->buildOrderBy($order_by, $sort);
		$groups   = $this->groupRepository->getList($filters, $lim, $page, [], $order_by, $recherche);

		$groupsSerialized = [
			'datas' => [],
			'count' => $groups->getTotalItems()
		];

		foreach ($groups->getItems() as $key => $group)
		{
			assert($group instanceof GroupEntity);

			$groupObject = (object)$group->__serialize();
			$groupObject->id    = $group->getId();
			$groupObject->label = ['fr' => $group->getLabel(), 'en' => $group->getLabel()];
			$groupObject->canDelete = $group->getId() != $allRightsGroup;

			$groupObject->additional_columns = [
				new AdditionalColumn(
					Text::_('COM_EMUNDUS_ONBOARD_GROUPS_DESCRIPTION'),
					'',
					ListDisplayEnum::TABLE,
					'',
					$group->getDescription()
				)
			];

			if ($programAccess)
			{
				$programsObjects = [];
				foreach ($group->getPrograms() as $program)
				{
					$programsObjects[] = (object) [
						'id'       => $program->getId(),
						'label'    => $program->getLabel(),
						'menuLink' => $programEditAccess ? ('index.php?option=com_emundus&view=programme&layout=edit&id=' . $program->getId()) : null
					];
				}

				$longValue = LayoutFactory::buildLongLayout(
					'COM_EMUNDUS_ONBOARD_GROUPS_PROGRAMS',
					'COM_EMUNDUS_ONBOARD_GROUPS_PROGRAMS_ASSOCIATED',
					'COM_EMUNDUS_ONBOARD_GROUPS_PROGRAMS_NONE',
					$programsObjects,
				);

				$groupObject->additional_columns[] = new AdditionalColumn(
					Text::_('COM_EMUNDUS_ONBOARD_GROUPS_PROGRAMS'),
					'',
					ListDisplayEnum::ALL,
					'',
					$longValue['shortTags'],
					[],
					null,
					$longValue['longTags'],
				);
			}

			$statusesObjects = [];
			foreach ($group->getStatuses() as $status)
			{
				$statusesObjects[] = (object) [
					'id'       => $status->getId(),
					'label'    => $status->getLabel(),
					'menuLink' => ''
				];
			}

			$longValue = LayoutFactory::buildLongLayout(
				'COM_EMUNDUS_ONBOARD_GROUPS_STATUSES',
				'COM_EMUNDUS_ONBOARD_GROUPS_STATUSES_RESTRICTIVE',
				'COM_EMUNDUS_ONBOARD_GROUPS_STATUSES_NONE',
				$statusesObjects,
			);

			$groupObject->additional_columns[] = new AdditionalColumn(
				Text::_('COM_EMUNDUS_ONBOARD_GROUPS_STATUSES'),
				'',
				ListDisplayEnum::ALL,
				'',
				$longValue['shortTags'],
				[],
				null,
				$longValue['longTags'],
			);

			$groupObject->additional_columns[] = new AdditionalColumn(
				Text::_('COM_EMUNDUS_ONBOARD_GROUPS_PUBLISHED'),
				'',
				ListDisplayEnum::ALL,
				'',
				'',
				[
					new AdditionalColumnTag(
						Text::_('COM_EMUNDUS_ONBOARD_GROUPS_PUBLISHED'),
						$group->isPublished() ? StatusEnum::PUBLISHED->getLabel() : StatusEnum::UNPUBLISHED->getLabel(),
						'',
						$group->isPublished() ? StatusEnum::PUBLISHED->getClass() : StatusEnum::UNPUBLISHED->getClass(),
					)
				],
				ListColumnTypesEnum::TAGS
			);

			$groupsSerialized['datas'][$key] = $groupObject;
		}

		return EmundusResponse::ok($groupsSerialized, Text::_('COM_EMUNDUS_GROUPS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getgroup(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_ID_REQUIRED'));
		}

		$group = $this->groupRepository->getById($id);
		if (empty($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_GROUP_NOT_FOUND'), 404);
		}

		$groupSerialized                  = $group->__serialize();
		$groupSerialized['published']     = $group->isPublished() ? 1 : 0;
		$groupSerialized['filter_status'] = $group->isFilterStatus() ? 1 : 0;

		$colors = [];
		$yaml   = Yaml::parse(file_get_contents('templates/g5_helium/custom/config/default/styles.yaml'));
		if (!empty($yaml))
		{
			$colors = $yaml['accent'];
		}
		if (empty($colors))
		{
			$colors = ColorEnum::cases();
		}

		$groupSerialized['class'] = $colors[str_replace('label-', '', $group->getClass())] ?? '';

		return EmundusResponse::ok($groupSerialized, Text::_('COM_EMUNDUS_GROUP_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function savegroup(): EmundusResponse
	{
		$groupId = $this->input->getInt('id', 0);

		$label         = $this->input->getString('label', '');
		$description   = $this->input->getString('description', '');
		$color         = $this->input->getString('class', '');
		$published     = 1;
		$anonymize     = $this->input->getInt('anonymize', 0);
		$filter_status = $this->input->getInt('filter_status', 0);
		$statuses      = $this->input->getString('status', '');
		$visibleGroups = $this->input->getString('visible_groups', '');
		if(!empty($visibleGroups))
		{
			$visibleGroups = explode(',', $visibleGroups);
		}
		else {
			$visibleGroups = [];
		}
		$visibleAttachments = $this->input->getString('visible_attachments', '');
		if(!empty($visibleAttachments))
		{
			$visibleAttachments = explode(',', $visibleAttachments);
		}
		else {
			$visibleAttachments = [];
		}

		if (empty($label))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_LABEL_REQUIRED'));
		}

		$colors = [];
		$yaml   = Yaml::parse(file_get_contents('templates/g5_helium/custom/config/default/styles.yaml'));
		if (!empty($yaml))
		{
			$colors = $yaml['accent'];
		}
		if (empty($colors))
		{
			$colors = ColorEnum::cases();
		}
		$colorLabel = array_search($color, $colors);
		if (!empty($colorLabel))
		{
			$colorLabel = 'label-' . $colorLabel;
		}

		if ($filter_status == 1 && !empty($statuses))
		{
			$statusRepository = new StatusRepository();

			$statuses         = explode(',', $statuses);
			$statusesEntities = $statusRepository->getItemsByField('step', $statuses, true);
		}

		if (!empty($groupId))
		{
			$group = $this->groupRepository->getById($groupId);
			if (empty($group))
			{
				throw new Exception(Text::_('COM_EMUNDUS_GROUP_NOT_FOUND'), 404);
			}

			$group->setLabel($label)
				->setDescription($description)
				->setClass($colorLabel)
				->setPublished($published)
				->setAnonymize($anonymize)
				->setFilterStatus($filter_status)
				->setStatuses($statusesEntities ?? [])
				->setVisibleGroups($visibleGroups)
				->setVisibleAttachments($visibleAttachments);
		}
		else
		{
			$group = new GroupEntity(
				0,
				$label,
				$description,
				$published,
				[],
				$anonymize,
				$filter_status,
				$statusesEntities ?? [],
				$visibleGroups,
				$visibleAttachments,
				$colorLabel
			);
		}

		$this->groupRepository->flush($group);

		return EmundusResponse::ok($group->__serialize(), Text::_('COM_EMUNDUS_GROUPS_SAVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function associateprograms(): EmundusResponse
	{
		$groupId  = $this->input->getInt('group_id', 0);
		$programs = $this->input->getString('program_codes', '');
		if (empty($groupId))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_ID_REQUIRED'));
		}

		$group = $this->groupRepository->getById($groupId);
		if (empty($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_GROUP_NOT_FOUND'), 404);
		}

		// Get programs entities
		$programCodes      = explode(',', $programs);
		$programRepository = new ProgramRepository();
		$programs          = $programRepository->getItemsByField('code', $programCodes, true);

		$group->setPrograms($programs);

		if (!$this->groupRepository->flush($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_ERROR_ASSOCIATING_PROGRAMS'), 500);
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_PROGRAMS_ASSOCIATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function getaccessrights(): EmundusResponse
	{
		$groupId = $this->input->getInt('group_id', 0);
		if (empty($groupId))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_ID_REQUIRED'));
		}

		$group = $this->groupRepository->getById($groupId);
		if (empty($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_GROUP_NOT_FOUND'), 404);
		}

		$actionRepository      = new ActionRepository();
		$actions               = $actionRepository->getList(['status' => [1, 2]], 0, 0, [], 'type, ordering');
		$rights                = $this->groupAccessRepository->getItemsByField('group_id', $group->getId(), true);
		
		$rightsSerialized = [];
		foreach ($actions->getItems() as $action)
		{
			assert($action instanceof ActionEntity);

			foreach ($rights as $right)
			{
				assert($right instanceof GroupAccessEntity);

				if(!empty($right->getAction()) && $right->getAction()->getId() == $action->getId())
				{
					$right->setGroup(null);

					$right->getAction()->setLabel(Text::_($right->getAction()->getLabel()));
					$description = $right->getAction()->getDescription() ? Text::_($right->getAction()->getDescription()) : '';
					$right->getAction()->setDescription($description);
					$rightsSerialized[$right->getAction()->getType()->value][] = $right->__serialize();
					continue 2;
				}
			}

			$emptyRight = new GroupAccessEntity(0, null, $action, new CrudEntity(0, 0, 0, 0, 0));

			$emptyRight->getAction()->setLabel(Text::_($emptyRight->getAction()->getLabel()));
			$description = $emptyRight->getAction()->getDescription() ? Text::_($emptyRight->getAction()->getDescription()) : '';
			$emptyRight->getAction()->setDescription($description);
			$rightsSerialized[$emptyRight->getAction()->getType()->value][] = $emptyRight->__serialize();
		}

		return EmundusResponse::ok($rightsSerialized, Text::_('COM_EMUNDUS_GROUPS_ACCESS_RIGHTS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function updateaccessrights(): EmundusResponse
	{
		$groupId = $this->input->getInt('group_id', 0);
		if (empty($groupId))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_ID_REQUIRED'));
		}

		$accessRights = $this->input->getString('access_rights','');
		$accessRights = json_decode($accessRights);
		if(empty($accessRights))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUPS_ACCESS_RIGHTS_REQUIRED'));
		}

		$group = $this->groupRepository->getById($groupId);
		if (empty($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_GROUP_NOT_FOUND'), 404);
		}
		

		$actionRepository = new ActionRepository();
		foreach ($accessRights as $accessRight)
		{
			$action = $actionRepository->getById($accessRight->action_id);
			$groupAccessEntity = new GroupAccessEntity(
				$accessRight->id ?? 0,
				$group,
				$action,
				new CrudEntity(0, $accessRight->crud->create, $accessRight->crud->read, $accessRight->crud->update, $accessRight->crud->delete)
			);

			if (!$this->groupAccessRepository->flush($groupAccessEntity))
			{
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_UPDATING_ACCESS_RIGHTS'), 500);
			}
		}

		require_once(JPATH_ROOT . '/administrator/components/com_emundus/helpers/update.php');
		EmundusHelperUpdate::clearJoomlaCache();

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_GROUPS_ACCESS_RIGHTS_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER)]
	public function getusersgroup(): EmundusResponse
	{
		$groupId = $this->input->getInt('group_id', 0);
		if (empty($groupId))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_ID_REQUIRED'));
		}

		$group = $this->groupRepository->getById($groupId);
		if (empty($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_GROUP_NOT_FOUND'), 404);
		}

		$emundusUserRepository = new EmundusUserRepository();
		$users = $emundusUserRepository->getUsersByGroup($groupId);

		$usersData = [];
		foreach($users as $user)
		{
			$usersData[] = $user->__serialize();
		}

		return EmundusResponse::ok($usersData, Text::_('COM_EMUNDUS_GROUP_USERS_RETRIEVED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function deletegroup(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		if (empty($id))
		{
			$ids = $this->input->getString('ids');
			$id  = explode(',', $ids);
			$id = array_filter($id, function ($item) {
				return is_numeric($item);
			});
		}

		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_ID_REQUIRED'));
		}

		if(!is_array($id))
		{
			$id = [$id];
		}

		foreach ($id as $groupId)
		{
			if(!$this->groupRepository->delete((int)$groupId))
			{
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_DELETING_GROUP'), 500);
			}
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_GROUP_DELETED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function duplicategroup(): EmundusResponse
	{
		$id = $this->input->getInt('id', 0);
		$newName = $this->input->getString('input', '');
		if (empty($id))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_GROUP_ID_REQUIRED'));
		}

		$group = $this->groupRepository->getById($id);
		if (empty($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_GROUP_NOT_FOUND'), 404);
		}

		if(empty($newName))
		{
			$newName = $group->getLabel() . ' (copy)';
		}

		$group->setId(0);
		$group->setLabel($newName);

		if(!$this->groupRepository->flush($group))
		{
			throw new Exception(Text::_('COM_EMUNDUS_ERROR_DUPLICATING_GROUP'), 500);
		}

		// Duplicate ACL
		$groupAccessList = $this->groupAccessRepository->getItemsByField('group_id', $id, true);
		foreach($groupAccessList as $groupAccess)
		{
			if(empty($groupAccess->getAction()))
			{
				continue;
			}

			$groupAccess->setId(0);
			$groupAccess->setGroup($group);
			if (!$this->groupAccessRepository->flush($groupAccess))
			{
				throw new Exception(Text::_('COM_EMUNDUS_ERROR_DUPLICATING_GROUP_ACCESS_RIGHTS'), 500);
			}
		}

		return EmundusResponse::ok($group->__serialize(), Text::_('COM_EMUNDUS_GROUP_DUPLICATED'));
	}
}
