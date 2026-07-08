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
use Joomla\CMS\Factory;
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
