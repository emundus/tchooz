<?php
/**
 * @version         1.39.0
 * @package         eMundus
 * @copyright   (C) 2024 eMundus LLC. All rights reserved.
 * @license         GNU General Public License
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use \Tchooz\Traits\TraitResponse;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/ranking.php');

class EmundusControllerRanking extends JControllerLegacy
{
	use TraitResponse;

	public function __construct($config = array())
	{
		$user = Factory::getApplication()->getIdentity();

		if (!EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			throw new Exception('Access denied');
		}

		$this->app   = Factory::getApplication();
		$this->model = new EmundusModelRanking();

		parent::__construct($config);
	}

	public function isActivated()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			$response['data'] = $this->model->isActivated();
			$response['status'] = true;
			$response['msg'] = Text::_('SUCCESS');
			$response['code'] = 200;
		}

		$this->sendJSONResponse($response);
	}

	public function getMyFilesToRank()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			$jingput            = $this->app->input;
			$page               = $jingput->getInt('page', 1);
			$limit              = $jingput->getInt('limit', 10);
			$sort               = $jingput->getString('order', 'ASC');
			$order_by           = $jingput->getString('order_by', 'default');
			$order_by_hierarchy = $jingput->getString('order_by_hierarchy', 'default');
			$package_id         = $jingput->getInt('package_id', 0);

			try
			{
				$response['data']   = $this->model->getFilesUserCanRank($user->id, $page, $limit, $sort, $order_by_hierarchy, $package_id, $order_by);
				$response['status'] = true;
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
			}
			catch (Exception $e)
			{
				$response['msg']  = $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJSONResponse($response);
	}

	public function getOtherRankingsICanSee()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			try
			{
				$response['data']   = $this->model->getOtherRankingsRankerCanSee($user->id);
				$response['status'] = true;
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
			}
			catch (Exception $e)
			{
				$response['msg']  = $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJSONResponse($response);
	}

	public function updateFileRanking()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			$jinput = $this->app->input;
			$id     = $jinput->getInt('id', 0);

			if (!empty($id))
			{
				$rank                = $jinput->getInt('rank', -1);
				$hierarchy_id        = $jinput->getInt('hierarchy_id', 0);
				$files_user_can_rank = $this->model->getAllFilesRankerCanAccessTo($user->id);

				if (!empty($files_user_can_rank) && in_array($id, $files_user_can_rank))
				{
					$package_id = $this->model->getPackageIdOfFile($user->id, $id);

					try
					{
						$response['status'] = $this->model->updateFileRanking($id, $user->id, $rank, $hierarchy_id, $package_id);

						if ($response['status'])
						{
							$response['msg']  = Text::_('SUCCESS');
							$response['code'] = 200;
						}
						else
						{
							$response['msg']  = Text::_('ERROR');
							$response['code'] = 500;
						}
					}
					catch (Exception $e)
					{
						$response['msg']  = $e->getMessage();
						$response['code'] = $response['msg'] == 'You cannot rank your own file' ? 403 : 500;
					}
				}
			}
		}

		$this->sendJSONResponse($response);
	}

	/**
	 * @return void
	 */
	public function lockFilesOfHierarchyRanking()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			$jinput         = $this->app->input;
			$hierarchy_id   = $jinput->getInt('id', 0);
			$user_hierarchy = $this->model->getUserHierarchy($user->id);

			if ($user_hierarchy == $hierarchy_id)
			{
				$lock = $jinput->getInt('lock', 1);

				$response['status'] = $this->model->toggleLockFilesOfHierarchyRanking($hierarchy_id, $user->id, $lock);
				$response['msg']    = $response['status'] ? Text::_('SUCCESS') : Text::_('ERROR');
				$response['code']   = $response['status'] ? 200 : 500;
			}
		}

		$this->sendJSONResponse($response);
	}

	public function askToLockRankings()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			$response['code'] = 500;
			$response['msg']  = Text::_('MISSING_PARAMS');

			$jinput      = $this->app->input;
			$users       = $jinput->getString('users', '');
			$hierarchies = $jinput->getString('hierarchies', '');

			if (!empty($users) || !empty($hierarchies))
			{
				$users       = json_decode($users, true);
				$hierarchies = json_decode($hierarchies, true);

				try
				{
					$result = $this->model->askUsersToLockRankings($user->id, $users, $hierarchies);

					$response['status'] = $result['asked'];
					$response['code']   = 200;
					$response['data']   = $result['asked_to'];
					$response['msg']    = Text::_('SUCCESS');
				}
				catch (Exception $e)
				{
					$response['code'] = 500;
					$response['msg']  = $e->getMessage();
				}
			}
		}

		$this->sendJSONResponse($response);
	}

	public function getPackages()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			try
			{
				$response['data']   = $this->model->getUserPackages($user->id);
				$response['status'] = true;
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
			}
			catch (Exception $e)
			{
				$response['msg']  = $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJSONResponse($response);
	}

	public function getHierarchiesUserCanSee()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			try
			{
				$response['data']   = $this->model->getHierarchiesUserCanSee($user->id);
				$response['status'] = true;
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
			}
			catch (Exception $e)
			{
				$response['msg']  = $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJSONResponse($response);
	}

	public function exportRanking()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			$jinput      = $this->app->input;
			$package_ids = $jinput->getString('packageIds', '');

			$package_ids = json_decode($package_ids, true);
			if (!empty($package_ids))
			{
				$hierarchy_ids = $jinput->getString('hierarchyIds', '');
				$hierarchy_ids = json_decode($hierarchy_ids, true);
				$columns       = $jinput->getString('columns', '');
				$columns       = json_decode($columns, true);

				try
				{
					$response['data']   = $this->model->exportRanking($user->id, $package_ids, $hierarchy_ids, $columns);
					$response['status'] = true;
					$response['msg']    = Text::_('SUCCESS');
					$response['code']   = 200;
				}
				catch (Exception $e)
				{
					$response['msg']  = $e->getMessage();
					$response['code'] = 500;
				}
			}
		}

		$this->sendJSONResponse($response);
	}

	public function getHierarchies()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$ids = $this->app->input->getString('ids', '');
			$ids = !empty($ids) ? json_decode($ids, true) : [];

			try
			{
				$response['data']   = $this->model->getHierarchies($ids);
				$response['status'] = true;
				$response['msg']    = Text::_('SUCCESS');
				$response['code']   = 200;
			}
			catch (Exception $e)
			{
				$response['msg']  = $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJSONResponse($response);
	}

	public function createHierarchy()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$response['code'] = 500;
			$label            = $this->app->input->getString('label', '');
			$profiles         = $this->app->input->getString('profiles', '');
			$profiles         = !empty($profiles) ? explode(',', $profiles) : [];

			if (!empty($label) && !empty($profiles))
			{
				$published           = $this->app->input->getInt('published', 0);
				$visible_status      = $this->app->input->getString('visible_status', '');
				$visible_status      = $visible_status != '' ? explode(',', $visible_status) : [];
				$editable_status     = $this->app->input->getString('editable_status', '');
				$editable_status     = $editable_status != '' ? explode(',', $editable_status) : [];
				$visible_hierarchies = $this->app->input->getString('visible_hierarchies', '');
				$visible_hierarchies = !empty($visible_hierarchies) ? explode(',', $visible_hierarchies) : [];
				$parent_hierarchy    = $this->app->input->getInt('parent_hierarchy', 0);
				$form_id             = $this->app->input->getInt('form_id', 0);

				try
				{
					$response['data']   = $this->model->createHierarchy($label, $editable_status, $profiles, $parent_hierarchy, $published, $visible_hierarchies, $visible_status, $form_id);
					$response['status'] = true;
					$response['msg']    = Text::_('SUCCESS');
					$response['code']   = 200;
				}
				catch (Exception $e)
				{
					$response['msg'] = $e->getMessage();
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}
		}

		$this->sendJSONResponse($response);
	}

	public function updateHierarchy()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$response['code'] = 500;
			$id               = $this->app->input->getInt('id', 0);
			$params           = [
				'label'               => $this->app->input->getString('label', ''),
				'editable_status'     => $this->app->input->getString('editable_status', ''),
				'profile_ids'         => $this->app->input->getString('profiles', ''),
				'published'           => $this->app->input->getInt('published', 0),
				'visible_status'      => $this->app->input->getString('visible_status', ''),
				'visible_hierarchies' => $this->app->input->getString('visible_hierarchies', ''),
				'parent_id'           => $this->app->input->getInt('parent_hierarchy', 0),
				'form_id'             => $this->app->input->getInt('form_id', 0)
			];

			if (!empty($id))
			{
				$params['profile_ids']         = !empty($params['profile_ids']) ? explode(',', $params['profile_ids']) : [];
				$params['visible_status']      = $params['visible_status'] != '' ? explode(',', $params['visible_status']) : [];
				$params['editable_status']     = $params['editable_status'] != '' ? explode(',', $params['editable_status']) : [];
				$params['visible_hierarchies'] = !empty($params['visible_hierarchies']) ? explode(',', $params['visible_hierarchies']) : [];

				try
				{
					$response['data']   = $this->model->updateHierarchy($id, $params);
					$response['status'] = true;
					$response['msg']    = Text::_('SUCCESS');
					$response['code']   = 200;
				}
				catch (Exception $e)
				{
					$response['msg'] = $e->getMessage();
				}
			}
			else
			{
				$response['msg'] = Text::_('MISSING_PARAMS');
			}


		}

		$this->sendJSONResponse($response);
	}

	public function deleteHierarchy()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$id               = $this->app->input->getInt('hierarchyId', 0);
			$response['code'] = 500;
			$response['msg']  = Text::_('MISSING_PARAMS');

			if (!empty($id))
			{
				try
				{
					$response['data']   = $this->model->deleteHierarchy($id);
					$response['status'] = true;
					$response['msg']    = Text::_('SUCCESS');
					$response['code']   = 200;
				}
				catch (Exception $e)
				{
					$response['msg'] = $e->getMessage();
				}
			}
		}

		$this->sendJSONResponse($response);
	}

	public function getAllRankings()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$hierarchy_id     = $this->app->input->getInt('hierarchy_id', 0);
			$programs_filter  = $this->app->input->getString('programs_filter', '');
			$programs_filter  = !empty($programs_filter) ? explode(',', $programs_filter) : [];
			$campaigns_filter = $this->app->input->getString('campaigns_filter', '');
			$campaigns_filter = !empty($campaigns_filter) ? explode(',', $campaigns_filter) : [];
			$status_filter    = $this->app->input->getString('status_filter', '');
			$status_filter    = $status_filter !== '' ? explode(',', $status_filter) : [];

			$search_file_or_user = $this->app->input->getString('search_file_or_user', '');
			$search_ranker       = $this->app->input->getString('search_ranker', '');

			$page               = $this->app->input->getInt('page', 0);
			$limit              = $this->app->input->getInt('limit', 0);
			$order_by_column    = $this->app->input->getString('order_by_column', 'ecc.fnum');
			$order_by_direction = $this->app->input->getString('order_by_direction', 'ASC');

			$response['data']   = $this->model->getAllRankingsSuperAdmin($hierarchy_id, $page, $limit, $programs_filter, $campaigns_filter, $status_filter, $search_file_or_user, $search_ranker, $order_by_column, $order_by_direction);
			$response['status'] = true;
			$response['msg']    = Text::_('SUCCESS');
			$response['code']   = 200;
		}

		$this->sendJSONResponse($response);
	}

	public function rawUpdateRank()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$ranking_row_id = $this->app->input->getInt('rank_row_id', 0);
			$new_rank       = $this->app->input->getInt('new_rank', 0);
			$ccid           = $this->app->input->getInt('ccid', 0);
			$hierarchy_id   = $this->app->input->getInt('hierarchy_id', 0);

			$response['status'] = $this->model->rawUpdateRank($ranking_row_id, $new_rank, $ccid, $hierarchy_id, $user->id);
			$response['msg']    = $response['status'] ? Text::_('SUCCESS') : Text::_('ERROR');
			$response['code']   = $response['status'] ? 200 : 500;
		}

		$this->sendJSONResponse($response);
	}

	public function rawUpdateRanker()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$ranking_row_id = $this->app->input->getInt('rank_row_id', 0);
			$new_ranker     = $this->app->input->getInt('new_ranker', 0);

			$response['status'] = $this->model->rawUpdateRanker($ranking_row_id, $new_ranker, $user->id);
			$response['msg']    = $response['status'] ? Text::_('SUCCESS') : Text::_('ERROR');
			$response['code']   = $response['status'] ? 200 : 500;
		}

		$this->sendJSONResponse($response);
	}

	public function getAllRankers()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];
		$user     = Factory::getApplication()->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($user->id))
		{
			$response['data']   = $this->model->getRankersByHierarchy();
			$response['status'] = true;
			$response['msg']    = Text::_('SUCCESS');
			$response['code']   = 200;
		}

		$this->sendJSONResponse($response);
	}

	public function getHierarchyData()
	{
		$response = ['status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => [], 'code' => 403];

		$hierarchy_id = $this->app->input->getInt('hierarchy_id', 0);

		if (EmundusHelperAccess::asCoordinatorAccessLevel(Factory::getApplication()->getIdentity()->id))
		{
			$can_access = true;
		}
		else
		{
			$user_hierarchy = $this->model->getUserHierarchy(Factory::getApplication()->getIdentity()->id);
			$can_access     = $user_hierarchy == $hierarchy_id;
		}

		if ($can_access)
		{
			$response['data']   = $this->model->getHierarchyData($hierarchy_id);
			$response['status'] = true;
			$response['msg']    = Text::_('SUCCESS');
			$response['code']   = 200;
		}

		$this->sendJSONResponse($response);
	}
}

?>