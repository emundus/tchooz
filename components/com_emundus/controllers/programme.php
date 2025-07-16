<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      Benjamin Rivalland
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;


/**
 * campaign Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      5.0.0
 */
class EmundusControllerProgramme extends BaseController
{
	protected $app;

	private $_user;
	private $m_programme;

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

		$this->app   = Factory::getApplication();
		$this->_user = $this->app->getIdentity();

		$this->m_programme = $this->getModel('programme');
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
			$default = 'programme';
			$this->input->set('view', $default);
		}
		parent::display();
	}

	public function getprogrammes()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$programmes = $this->m_programme->getProgrammes();

			if (count($programmes) > 0) {
				$tab = array('status' => 1, 'msg' => Text::_('PROGRAMMES_RETRIEVED'), 'data' => $programmes);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_PROGRAMMES'), 'data' => $programmes);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function addprogrammes()
	{
		$data = $this->input->get('data', null, 'POST', 'none', 0);

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$result = $this->m_programme->addProgrammes($data);

			if ($result === true) {
				$tab = array('status' => 1, 'msg' => Text::_('PROGRAMMES_ADDED'), 'data' => $result);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_ADD_PROGRAMMES'), 'data' => $result);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function editprogrammes()
	{
		$data = $this->input->get('data', null, 'POST', 'none', 0);

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$result = $this->m_programme->editProgrammes($data);

			if ($result === true) {
				$tab = array('status' => 1, 'msg' => Text::_('PROGRAMMES_EDITED'), 'data' => $result);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_EDIT_PROGRAMMES'), 'data' => $result);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getallprogramforfilter()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$programs = $this->m_programme->getAllPrograms('all', 0, null, 'ASC', '', $this->_user, '', 'p.label');

			if (count((array) $programs) > 0) {
				$type = $this->input->getString('type', '');

				$values = [];
				foreach ($programs['datas'] as $key => $program) {
					$values[] = [
						'label' => $program->label,
						'value' => $type === 'id' ? $program->id : $program->code
					];
				}

				$response = ['status' => true, 'msg' => Text::_('PROGRAMS_FILTER_RETRIEVED'), 'data' => $values];
			}
			else {
				$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_PROGRAMS');
			}
		}
		echo json_encode((object) $response);
		exit;
	}

	public function getallprogram()
	{
		$response = array('status' => false, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$filter    = $this->input->getString('filter', null);
			$sort      = $this->input->getString('sort', 'DESC');
			$recherche = $this->input->getString('recherche', '');
			$category  = $this->input->getString('category', '');
			$lim       = $this->input->getInt('lim', 0);
			$page      = $this->input->getInt('page', 0);
			$order_by  = $this->input->getString('order_by', 'p.id');
			$order_by  = $order_by == 'label' ? 'p.label' : $order_by;

			$programs = $this->m_programme->getAllPrograms($lim, $page, $filter, $sort, $recherche, $this->_user, $category, $order_by);

			if (count((array) $programs) > 0) {
				foreach ($programs['datas'] as $key => $program) {
					$programs['datas'][$key]->label              = ['fr' => Text::_($program->label), 'en' => Text::_($program->label)];

					if (!empty($program->nb_campaigns))
					{
						$campaigns = $this->m_programme->getAssociatedCampaigns($program->code, $this->_user->id);

						if (!empty($campaigns))
						{
							$translation = 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED';
							$title = 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE';
							if($program->nb_campaigns < 2) {
								$title = 'COM_EMUNDUS_ONBOARD_CAMPAIGN_ASSOCIATED_TITLE';
								$translation = 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_SINGLE';
							}

							$tags       = '<div>';
							$short_tags = $tags;
							$tags       .= '<h2 class="tw-mb-2">' . Text::_($title) . '</h2>';
							$tags       .= '<div class="tw-flex tw-flex-wrap">';
							foreach ($campaigns as $campaign)
							{
								$tags .= '<a href="'.EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid='.$campaign->id).'" class="tw-cursor-pointer tw-mr-2 tw-mb-2 tw-h-max tw-px-3 tw-py-1 tw-bg-main-100 tw-text-neutral-900 tw-text-sm tw-rounded-coordinator em-campaign-tag"> ' . $campaign->label . ' (' . $campaign->year . ')</a>';
							}
							$tags .= '</div>';

							$short_tags .= '<span class="tw-cursor-pointer tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-text-sm hover:!tw-underline">' . count($campaigns) . Text::_($translation) . '</span>';
							$short_tags .= '</div>';
							$tags       .= '</div>';
						} else
						{
							$short_tags = Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_NOT');
						}
					}
					else
					{
						$short_tags = Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_NOT');
					}

					$campaigns_assiocated_column = [
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE'),
						'value'   => $short_tags,
						'classes' => '',
						'display' => 'all'
					];

					if (isset($tags))
					{
						$campaigns_assiocated_column['long_value'] = $tags;
					}

					$programs['datas'][$key]->additional_columns = [
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGCODE'),
							'value'   => $program->code,
							'classes' => 'em-font-size-14 em-neutral-700-color',
							'display' => 'all',
							'order_by' => 'p.code'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'),
							'value'   => Text::_($program->programmes),
							'classes' => 'em-font-size-14 em-neutral-700-color',
							'display' => 'all',
							'order_by' => 'p.programmes'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
							'value'   => $program->published ? Text::_('PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH'),
							'classes' => $program->published ? 'em-p-5-12 em-bg-main-100 em-text-neutral-900 em-font-size-14 em-border-radius' : 'em-p-5-12 em-bg-neutral-200 em-text-neutral-900 em-font-size-14 em-border-radius',
							'display' => 'table',
							'order_by' => 'p.published'
						],
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_APPLY_ONLINE'),
							'value'   => $program->apply_online ? Text::_('JYES') : Text::_('JNO'),
							'classes' => '',
							'display' => 'table',
							'order_by' => 'p.apply_online'
						],
						[
							'type'    => 'tags',
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_TAGS'),
							'values'  => [
								[
									'key'     => Text::_('COM_EMUNDUS_ONBOARD_STATE'),
									'value'   => $program->published ? Text::_('PUBLISHED') : Text::_('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH'),
									'classes' => $program->published ? 'em-p-5-12 em-font-weight-600 em-bg-main-100 em-text-neutral-900 em-font-size-14 label' : 'em-p-5-12 em-font-weight-600 em-bg-neutral-200 em-text-neutral-900 em-font-size-14 label',
								],
								[
									'key'     => Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_APPLY_ONLINE'),
									'value'   => $program->apply_online ? Text::_('COM_EMUNDUS_ONBOARD_PROGRAM_APPLY_ONLINE') : '',
									'classes' => $program->apply_online ? 'em-p-5-12 em-font-weight-600 em-bg-neutral-200 em-text-neutral-900 em-font-size-14 label' : 'hidden',
								]
							],
							'display' => 'blocs',
							'classes' => 'em-mt-8 em-mb-8'
						],
						$campaigns_assiocated_column
					];
				}

				$response = ['status' => true, 'msg' => Text::_('PROGRAMS_RETRIEVED'), 'data' => $programs];
			}
			else {
				$response['msg'] = Text::_('ERROR_CANNOT_RETRIEVE_PROGRAMS');
			}
		}
		echo json_encode((object) $response);
		exit;
	}

	public function getallsessions()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$sessions = $this->m_programme->getAllSessions();

			if (count((array) $sessions) > 0) {
				$tab = array('status' => 1, 'msg' => Text::_('PROGRAMS_RETRIEVED'), 'data' => $sessions);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_PROGRAMS'), 'data' => $sessions);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getprogramcount()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$filterCount    = $this->input->getString('filterCount');
			$rechercheCount = $this->input->getString('rechercheCount');

			$programs = $this->m_programme->getProgramCount($filterCount, $rechercheCount);

			$tab = array('status' => 1, 'msg' => Text::_('PROGRAMS_RETRIEVED'), 'data' => $programs);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getprogrambyid()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$id = $this->input->getInt('id');

			$program = $this->m_programme->getProgramById($id);

			if (!empty($program)) {
				$tab = array('status' => 1, 'msg' => Text::_('PROGRAMS_RETRIEVED'), 'data' => $program);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_PROGRAMS'), 'data' => $program);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function createprogram()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$data = $this->input->getRaw('body');
			$data = json_decode($data, true);

			$result = $this->m_programme->addProgram($data);

			if (is_array($result)) {
				$response = array('status' => true, 'msg' => Text::_('PROGRAMS_ADDED'), 'data' => $result);
			}
			else {
				$response = array('status' => false, 'msg' => Text::_('ERROR_CANNOT_ADD_PROGRAMS'), 'data' => $result);
			}
		}

		echo json_encode((object) $response);
		exit;
	}


	public function updateprogram()
	{
		$tab = array('status' => 0, 'msg' => Text::_('ACCESS_DENIED'));

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {


			$data = $this->input->getRaw('body');
			$id   = $this->input->getString('id');

			if (!empty($id) && !empty($data)) {
				$result = $this->m_programme->updateProgram($id, $data);

				if ($result) {
					$tab = array('status' => 1, 'msg' => Text::_('PROGRAMS_ADDED'), 'data' => $result);
				}
				else {
					$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_ADD_PROGRAMS'), 'data' => $result);
				}
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('MISSING_PARAMS'));
			}
		}

		echo json_encode((object) $tab);
		exit;
	}

	public function deleteprogram()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {

			$data   = $this->input->getInt('id');
			$result = $this->m_programme->deleteProgram($data);

			if ($result) {
				$response = ['status' => true, 'msg' => Text::_('PROGRAMS_ADDED'), 'data' => $result];
			}
			else {
				$response['msg'] = Text::_('ERROR_CANNOT_ADD_PROGRAMS');
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function unpublishprogram()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {

			$data   = $this->input->getInt('id');
			$result = $this->m_programme->unpublishProgram($data);

			if ($result) {
				$response = array('status' => 1, 'msg' => Text::_('PROGRAMS_ADDED'), 'data' => $result);
			}
			else {
				$response = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_ADD_PROGRAMS'), 'data' => $result);
			}
		}
		echo json_encode((object) $response);
		exit;
	}

	public function publishprogram()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {

			$data = $this->input->getInt('id');

			$result = $this->m_programme->publishProgram($data);

			if ($result) {
				$response = array('status' => 1, 'msg' => Text::_('PROGRAM_PUBLISHED'), 'data' => $result);
			}
			else {
				$response = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_PUBLISH_PROGRAM'), 'data' => $result);
			}
		}
		echo json_encode((object) $response);
		exit;
	}

	public function getprogramcategories()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$categories = $this->m_programme->getProgramCategories();

			if (!empty($categories)) {
				$response = array('status' => true, 'msg' => Text::_('PROGRAMS_RETRIEVED'), 'data' => $categories);
			} else {
				$response['data'] = [];
			}
		}

		echo json_encode((object) $response);
		exit;
	}

	public function getmanagers()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$group = $this->input->getInt('group');

			$managers = $this->m_programme->getManagers($group);

			if (!empty($managers)) {
				$tab = array('status' => 1, 'msg' => Text::_('MANAGERS_RETRIEVED'), 'data' => $managers);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_MANAGERS'), 'data' => $managers);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getevaluators()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$group = $this->input->getInt('group');

			$evaluators = $this->m_programme->getEvaluators($group);

			if (!empty($evaluators)) {
				$tab = array('status' => 1, 'msg' => Text::_('EVALUATORS_RETRIEVED'), 'data' => $evaluators);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_EVALUATORS'), 'data' => $evaluators);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function affectusertogroup()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$group      = $this->input->getInt('group');
			$prog_group = $this->input->getInt('prog_group');
			$email      = $this->input->getString('email');

			$changeresponse = $this->m_programme->affectusertogroups($group, $email, $prog_group);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function affectuserstogroup()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$group      = $this->input->getInt('group');
			$prog_group = $this->input->getInt('prog_group');
			$managers   = $this->input->getRaw('users');

			$changeresponse = $this->m_programme->affectuserstogroup($group, $managers, $prog_group);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function removefromgroup()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$user_id    = $this->input->getInt('id');
			$group      = $this->input->getInt('group');
			$prog_group = $this->input->getInt('prog_group');

			$changeresponse = $this->m_programme->removefromgroup($user_id, $group, $prog_group);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getusers()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$filters = $this->input->getRaw('filters');
			$page    = $this->input->getRaw('page');

			$user_ids = $this->m_programme->getusers($filters, $page['page']);

			if (!empty($this->_users)) {
				$tab = array('status' => 1, 'msg' => Text::_('USERS_RETRIEVED'), 'data' => $user_ids);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_USERS'), 'data' => $user_ids);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function updatevisibility()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$visibility = $this->input->getBool('visibility');
			$cid        = $this->input->getInt('cid');
			$gid        = $this->input->getInt('gid');

			$changeresponse = $this->m_programme->updateVisibility($cid, $gid, $visibility);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getevaluationgrid()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$pid = $this->input->getInt('pid');

			$grid = $this->m_programme->getEvaluationGrid($pid);

			if ($grid) {
				$tab = array('status' => 1, 'msg' => Text::_('GRID_RETRIEVED'), 'data' => $grid);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_GRID'), 'data' => $grid);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getgridsmodel()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {
			$grids = $this->m_programme->getGridsModel();

			if ($grids) {
				$tab = array('status' => 1, 'msg' => Text::_('GRID_RETRIEVED'), 'data' => $grids);
			}
			else {
				$tab = array('status' => 0, 'msg' => Text::_('ERROR_CANNOT_RETRIEVE_GRID'), 'data' => $grids);
			}
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function creategrid()
	{


		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$label    = $this->input->getString('label');
			$intro    = $this->input->getString('intro');
			$modelid  = $this->input->getInt('modelid');
			$template = $this->input->getBool('template');
			$pid      = $this->input->getInt('pid');

			if ($modelid != -1) {
				$changeresponse = $this->m_programme->createGridFromModel($label, $intro, $modelid, $pid);
			}
			else {
				$changeresponse = $this->m_programme->createGrid($label, $intro, $pid, $template);
			}
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function deletegrid()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$grid = $this->input->getInt('grid');
			$pid  = $this->input->getInt('pid');

			$changeresponse = $this->m_programme->deleteGrid($grid, $pid);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function affectgrouptoprogram()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$group = $this->input->getInt('group');
			$pid   = $this->input->getInt('pid');

			$changeresponse = $this->m_programme->affectGroupToProgram($group, $pid);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function deletegroupfromprogram()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result         = 0;
			$changeresponse = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$group = $this->input->getInt('group');
			$pid   = $this->input->getInt('pid');

			$changeresponse = $this->m_programme->deleteGroupFromProgram($group, $pid);
		}
		echo json_encode((object) $changeresponse);
		exit;
	}

	public function getgroupsbyprograms()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$programs = $this->input->getRaw('programs');

			$groups = $this->m_programme->getGroupsByPrograms($programs);

			$tab = array('status' => 1, 'msg' => Text::_('GRID_RETRIEVED'), 'groups' => $groups);
		}
		echo json_encode((object) $tab);
		exit;
	}

	public function getcampaignsbyprogram()
	{
		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->_user->id)) {
			$result = 0;
			$tab    = array('status' => $result, 'msg' => Text::_("ACCESS_DENIED"));
		}
		else {


			$program = $this->input->getInt('pid');

			$campaigns = $this->m_programme->getCampaignsByProgram($program);

			$tab = array('status' => 1, 'msg' => Text::_('CAMPAIGNS_RETRIEVED'), 'campaigns' => $campaigns);
		}
		echo json_encode((object) $tab);
		exit;
	}

}
