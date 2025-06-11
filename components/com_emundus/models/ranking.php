<?php
/**
 * @version     1.39.0
 * @package     eMundus
 * @copyright   (C) 2024 eMundus LLC. All rights reserved.
 * @license     GNU General Public License
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Settings\AddonEntity;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');

class EmundusModelRanking extends JModelList
{

    private bool $can_user_rank_himself = true;

    private int $all_rights_profile = 0;

    public array $filters = [];
    private EmundusHelperFiles $h_files;

    private EmundusModelLogs $logger;

    private DatabaseDriver $db;

	private ?AddonEntity $rankingAddon = null;

	private EmundusHelperCache $h_cache;

	private bool $activated = false;

	public function __construct($config = array())
    {
        parent::__construct($config);

        $session = Factory::getApplication()->getSession();
        $this->filters = $session->get('em-applied-filters', []);
        $this->h_files = new EmundusHelperFiles();
        $this->logger = new EmundusModelLogs();

        $this->db = Factory::getContainer()->get('DatabaseDriver');

        $this->all_rights_profile = 2;

	    $this->h_cache = new EmundusHelperCache();
	    $this->setRankingAddon();
	    $this->activated = $this->rankingAddon->enabled && $this->rankingAddon->displayed;

        Log::addLogger(['text_file' => 'com_emundus.ranking.php'], Log::ALL, 'com_emundus.ranking.php');
    }

	/**
	 * Load the SMS addon
	 */
	private function setRankingAddon(): void
	{
		$cache_ranking_addon = $this->h_cache->get('ranking_addon');

		if (!empty($cache_ranking_addon)) {
			$this->rankingAddon = new AddonEntity(
				$cache_ranking_addon->name,
				$cache_ranking_addon->type,
				$cache_ranking_addon->icon,
				$cache_ranking_addon->description,
				$cache_ranking_addon->configuration,
				$cache_ranking_addon->enabled,
				$cache_ranking_addon->displayed
			);
		}

		if (empty($this->rankingAddon)) {
			$query = $this->db->createQuery();

			$query->select($this->db->quoteName('value'))
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('ranking'));
			try {
				$this->db->setQuery($query);
				$config = $this->db->loadResult();

				if (!empty($config)) {
					$config = json_decode($config, true);
					$this->rankingAddon = new AddonEntity(
						'COM_EMUNDUS_ONBOARD_SETTINGS_MENU_RANKING',
						'ranking',
						'leaderboard',
						'COM_EMUNDUS_ONBOARD_SETTINGS_MENU_RANKING_DESC',
						'',
						(bool)$config['enabled'],
						(bool)$config['displayed']
					);

					$this->h_cache->set('ranking_addon', $this->rankingAddon);
				} else {
					$this->rankingAddon = new AddonEntity(
						'COM_EMUNDUS_ONBOARD_SETTINGS_MENU_RANKING',
						'ranking',
						'leaderboard',
						'COM_EMUNDUS_ONBOARD_SETTINGS_MENU_RANKING_DESC',
						'',
						false,
						false
					);
				}
			} catch (\Exception $e) {
				Log::add('Error on load sms addon : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
			}
		}
	}

	public function getRankingAddon(): AddonEntity
	{
		return $this->rankingAddon;
	}

	public function isActivated(): bool
	{
		return $this->activated;
	}

	/**
	 * @param   string  $name
	 * @param   array   $args
	 *
	 * @return void
	 * @throws Exception
	 */
    private function helpDispatchEvent(string $name, array $args): void
    {
		if (!empty($name)) {
			PluginHelper::importPlugin('emundus');
			$dispatcher = Factory::getApplication()->getDispatcher();

			$generic_event = new GenericEvent($name, $args);
			$dispatcher->dispatch($name, $generic_event);

			$event_handler_event = new GenericEvent('callEventHandler', [$name, $args]);
			$dispatcher->dispatch('callEventHandler', $event_handler_event);
		}
    }

	/**
	 * @param $label
	 * @param $status
	 * @param $profile_ids array
	 * @param $published
	 * @return int
	 * @throws Exception
	 */
	public function createHierarchy($label, $editable_status, $profile_ids, $parent_hierarchy = 0, $published = 1, $visible_hierarchies = [], $visible_status = [], $form_id = 0)
	{
		$hierarchy_id = 0;

		if (!empty($label) && !empty($profile_ids)) {
			$query = $this->db->getQuery(true);

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('id') . ' IN (' . implode(',', $profile_ids) . ')');

			try {
				$this->db->setQuery($query);
				$profile_ids = $this->db->loadColumn();
			} catch (Exception $e) {
				Log::add('createHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
				throw new Exception(Text::_('COM_EMUNDUS_RANKING_COULD_NOT_DETERMINE_PROFILE_EXISTENCE'));
			}

			if (empty($profile_ids)) {
				throw new Exception(Text::_('COM_EMUNDUS_RANKING_PROFILE_DOES_NOT_EXIST'));
			}

			$query->clear()
				->select('DISTINCT erh.id')
				->from($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh'))
				->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_profiles', 'erhp') . ' ON erhp.hierarchy_id = erh.id')
				->where($this->db->quoteName('erhp.profile_id') . ' IN (' . implode(',', $profile_ids) . ')')
				->group($this->db->quoteName('erh.id'));

			try {
				$this->db->setQuery($query);
				$hierarchy_id = $this->db->loadResult();
			} catch (Exception $e) {
				Log::add('createHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
				throw new Exception(Text::_('COM_EMUNDUS_RANKING_COULD_NOT_DETERMINE_HIERARCHY_EXISTENCE'));
			}

			if (!empty($hierarchy_id)) {
				throw new Exception(Text::_('COM_EMUNDUS_RANKING_HIERARCHY_ALREADY_EXISTS_ON_STATE'));
			}

			$query->clear()
				->insert($this->db->quoteName('#__emundus_ranking_hierarchy'))
				->columns($this->db->quoteName('label') . ', ' . $this->db->quoteName('parent_id') . ', ' . $this->db->quoteName('published') . ', ' . $this->db->quoteName('form_id'))
				->values($this->db->quote($label) . ', ' . $this->db->quote($parent_hierarchy) . ', ' . $this->db->quote($published) . ', ' . $this->db->quote($form_id));

			try {
				$this->db->setQuery($query);
				$this->db->execute();
				$hierarchy_id = $this->db->insertid();
			} catch (Exception $e) {
				Log::add('createHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
				throw new Exception(Text::_('COM_EMUNDUS_RANKING_COULD_NOT_CREATE_HIERARCHY'));
			}

			if (!empty($hierarchy_id)) {
				foreach($profile_ids as $profile_id) {
					$query->clear()
						->insert($this->db->quoteName('#__emundus_ranking_hierarchy_profiles'))
						->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('profile_id'))
						->values($this->db->quote($hierarchy_id) . ', ' . $this->db->quote($profile_id));

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (Exception $e) {
						Log::add('createHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
						continue;
					}
				}


				if (!empty($visible_hierarchies)) {
					foreach ($visible_hierarchies as $visible_hierarchy_id) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_ranking_hierarchy_view'))
							->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('visible_hierarchy_id'))
							->values($this->db->quote($hierarchy_id) . ', ' . $this->db->quote($visible_hierarchy_id));

						try {
							$this->db->setQuery($query);
							$this->db->execute();
						} catch (Exception $e) {
							Log::add('createHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
							continue;
						}
					}
				}

				if (!empty($visible_status)) {
					foreach ($visible_status as $status) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_ranking_hierarchy_visible_status'))
							->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('status'))
							->values($this->db->quote($hierarchy_id) . ', ' . $this->db->quote($status));

						try {
							$this->db->setQuery($query);
							$this->db->execute();
						} catch (Exception $e) {
							Log::add('createHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
							continue;
						}
					}
				}

				if (!empty($editable_status)) {
					foreach ($editable_status as $status) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_ranking_hierarchy_editable_status'))
							->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('status'))
							->values($this->db->quote($hierarchy_id) . ', ' . $this->db->quote($status));

						try {
							$this->db->setQuery($query);
							$this->db->execute();
						} catch (Exception $e) {
							Log::add('createHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
							continue;
						}
					}
				}
			}
		}

		return $hierarchy_id;
	}
	public function updateHierarchy($id, $params)
	{
		$updated = true;

		if (!empty($id) && !empty($params)) {
			$query = $this->db->getQuery(true);

			$columns_allowed = ['label', 'parent_id', 'published', 'form_id'];
			$columns = array_keys($params);

			if (!empty(array_intersect($columns, $columns_allowed))) {
				$query->clear()
					->update($this->db->quoteName('#__emundus_ranking_hierarchy'));

				foreach ($params as $key => $value) {
					if (in_array($key, $columns_allowed)) {
						$query->set($this->db->quoteName($key) . ' = ' . $this->db->quote($value));
					}
				}

				$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

				try {
					$this->db->setQuery($query);
					$updated = $this->db->execute();
				} catch (Exception $e) {
					Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
					throw new Exception(Text::_('COM_EMUNDUS_RANKING_COULD_NOT_UPDATE_HIERARCHY'));
				}
			}

			if ($updated) {
				$updates = [];

				if (isset($params['profile_ids'])) {
					// remove all profiles from the hierarchy
					$query->clear()
						->delete($this->db->quoteName('#__emundus_ranking_hierarchy_profiles'))
						->where($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($id));

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (Exception $e) {
						Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
					}

					// add the new profiles
					foreach($params['profile_ids'] as $profile_id) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_ranking_hierarchy_profiles'))
							->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('profile_id'))
							->values($this->db->quote($id) . ', ' . $this->db->quote($profile_id));

						try {
							$this->db->setQuery($query);
							$updates[] = $this->db->execute();
						} catch (Exception $e) {
							Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
						}
					}
				}

				if (isset($params['visible_hierarchies'])) {
					// delete all visible hierarchies
					$query->clear()
						->delete($this->db->quoteName('#__emundus_ranking_hierarchy_view'))
						->where($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($id));

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (Exception $e) {
						Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
					}

					// add the new visible hierarchies
					foreach ($params['visible_hierarchies'] as $visible_hierarchy_id) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_ranking_hierarchy_view'))
							->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('visible_hierarchy_id'))
							->values($this->db->quote($id) . ', ' . $this->db->quote($visible_hierarchy_id));

						try {
							$this->db->setQuery($query);
							$updates[] = $this->db->execute();
						} catch (Exception $e) {
							Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
						}
					}
				}

				if (isset($params['visible_status'])) {
					// delete all visible status
					$query->clear()
						->delete($this->db->quoteName('#__emundus_ranking_hierarchy_visible_status'))
						->where($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($id));

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (Exception $e) {
						Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
					}

					// add the new visible status
					foreach ($params['visible_status'] as $status) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_ranking_hierarchy_visible_status'))
							->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('status'))
							->values($this->db->quote($id) . ', ' . $this->db->quote($status));

						try {
							$this->db->setQuery($query);
							$updates[] = $this->db->execute();
						} catch (Exception $e) {
							Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
						}
					}
				}

				if (isset($params['editable_status'])) {

					// delete all editable status
					$query->clear()
						->delete($this->db->quoteName('#__emundus_ranking_hierarchy_editable_status'))
						->where($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($id));

					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (Exception $e) {
						Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
					}

					// add the new editable status
					foreach ($params['editable_status'] as $status) {
						$query->clear()
							->insert($this->db->quoteName('#__emundus_ranking_hierarchy_editable_status'))
							->columns($this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('status'))
							->values($this->db->quote($id) . ', ' . $this->db->quote($status));

						try {
							$this->db->setQuery($query);
							$updates[] = $this->db->execute();
						} catch (Exception $e) {
							Log::add('updateHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
						}
					}
				}

				$updated = !in_array(false, $updates);
			}
		}

		return $updated;
	}

    /**
     * get hierarchy packages for a user
     */
	public function getUserPackages($user_id, $package_id = null) {
		$packages = [];

		if (!empty($user_id)) {
			$hierarchy_id = $this->getUserHierarchy($user_id);
			$hierarchy = $this->getHierarchyData($hierarchy_id);

			if ($hierarchy['package_by'] === 'jos_emundus_setup_campaigns.id') {
				$ccids = $this->getAllFilesRankerCanAccessTo($user_id, $hierarchy['id']);

				$query = $this->db->getQuery(true);
				$query->select('DISTINCT esc.id, esc.label, esc.start_date, esc.end_date, esp.id as programme_id, esp.label as programme_label, esp.programmes as group_id')
					->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
					->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
					->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('cc.campaign_id'))
					->where($this->db->quoteName('cc.id') . ' IN (' . implode(',', $ccids) . ')')
					->andWhere($this->db->quoteName('esc.published') . ' = 1');

				if (!empty($package_id)) {
					$query->andWhere($this->db->quoteName('esc.id') . ' = ' . $this->db->quote($package_id));
				}

				try {
					$this->db->setQuery($query);
					$packages = $this->db->loadAssocList();
				} catch (Exception $e) {
					JLog::add('getHierarchyPackages ' . $e->getMessage(), JLog::ERROR, 'com_emundus.ranking.php');
					$packages = [];
				}

				$start_date_local = 1;
				$end_date_local = 1;

				if (!empty($hierarchy['package_start_date_field'])) {
					list($table, $column) = explode('.', $hierarchy['package_start_date_field']);
					$package_column = $table === 'jos_emundus_setup_campaigns' ? 'id' : 'campaign_id';
					$start_date_local = $table === 'jos_emundus_setup_campaigns' ? 1 : 0;

					foreach($packages as $key => $package) {
						$query->clear()
							->select($this->db->quoteName($column))
							->from($this->db->quoteName($table))
							->where($this->db->quoteName($package_column) . ' = ' . $this->db->quote($package['id']));

						try {
							$this->db->setQuery($query);
							$package_start_date = $this->db->loadResult();

							if (!empty($package_start_date)) {
								$packages[$key]['start_date'] = $package_start_date;
							}
						} catch (Exception $e) {
							Log::add('getHierarchyPackages ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
							$packages[$key]['start_date'] = null;
						}
					}
				}

				if (!empty($hierarchy['package_end_date_field'])) {
					list($table, $column) = explode('.', $hierarchy['package_end_date_field']);
					$package_column = $table === 'jos_emundus_setup_campaigns' ? 'id' : 'campaign_id';
					$end_date_local = $table === 'jos_emundus_setup_campaigns' ? 1 : 0;

					foreach($packages as $key => $package) {
						$query->clear()
							->select($this->db->quoteName($column))
							->from($this->db->quoteName($table))
							->where($this->db->quoteName($package_column) . ' = ' . $this->db->quote($package['id']));

						try {
							$this->db->setQuery($query);
							$package_end_date = $this->db->loadResult();

							if (!empty($package_end_date)) {
								$packages[$key]['end_date'] = $package_end_date;
							}
						} catch (Exception $e) {
							Log::add('getHierarchyPackages ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
							$packages[$key]['end_date'] = null;
						}
					}
				}
			} else {
				$packages[] = [
					'id' => 0,
					'label' => 'All',
					'start_date' => null,
					'end_date' => null
				];
			}
		}

		foreach($packages as $key => $package) {
			if (!empty($package['start_date'])) {
				$packages[$key]['start_time'] = strtotime($package['start_date']);
				$packages[$key]['start_date'] = EmundusHelperDate::displayDate($package['start_date'], 'd/m/Y H\hi', $start_date_local);
			}

			if (!empty($package['end_date'])) {
				$packages[$key]['end_time'] = strtotime($package['end_date']);
				$packages[$key]['end_date'] = EmundusHelperDate::displayDate($package['end_date'], 'd/m/Y H\hi', $end_date_local);

			}
		}

		return $packages;
	}

    public function getPackageIdOfFile($user, $ccid) {
        $package_id = 0;

        if (!empty($user) && !empty($ccid)) {
            $hierarchy_id = $this->getUserHierarchy($user);
            $hierarchy = $this->getHierarchyData($hierarchy_id);

            if ($hierarchy['package_by'] === 'jos_emundus_setup_campaigns.id') {
                $query = $this->db->getQuery(true);
                $query->select('esc.id')
                    ->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
                    ->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('cc.campaign_id'))
                    ->where($this->db->quoteName('cc.id') . ' = ' . $this->db->quote($ccid))
                    ->andWhere($this->db->quoteName('esc.published') . ' = 1');

                try {
                    $this->db->setQuery($query);
                    $package_id = $this->db->loadResult();
                } catch (Exception $e) {
                    Log::add('getPackageIdOfFile ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
                    $package_id = 0;
                }
            }
        }

        return $package_id;
    }

	public function getFilesUserCanRankByPackage($user_id, $page = 1, $limit = 10, $sort = 'ASC', $hierarchy_order_by = 'default') {
		$files_by_package = [];
		$packages = $this->getUserPackages($user_id);

		if (empty($packages)) {
			$files_by_package[] = [
				'package' => [
					'id' => 0,
					'label' => JText::_('COM_EMUNDUS_RANKING_ALL'),
					'start_date' => null,
					'end_date' => null
				],
				'files' => $this->getFilesUserCanRank($user_id, $page, $limit, $sort, $hierarchy_order_by)
			];
		} else {
			foreach ($packages as $package) {
				$files_package = $this->getFilesUserCanRank($user_id, $page, $limit, $sort, $hierarchy_order_by, $package['id']);
				if ((!empty($package['start_time']) && $package['start_time'] > time()) || (!empty($package['end_time']) && $package['end_date'] < time())) {
					foreach ($files_package['data'] as $key => $file) {
						$files_package['data'][$key]['locked'] = 1;
					}
				}

				$files_by_package[] = [
					'package' => $package,
					'files' => $files_package
				];
			}
		}

		return $files_by_package;
	}

	/**
	 * @param $user_id
	 * @param $page
	 * @param $limit
	 * @return array|mixed
	 */
	public function getFilesUserCanRank($user_id, $page = 1, $limit = 10, $sort = 'ASC', $hierarchy_order_by = 'default', $package_id = null, $order_by = 'rank')
	{
		$files = [
			'total' => 0,
			'data' => [],
			'maxRankValue' => -1,
		];

		/**
		 * Avoid SQL injections
		 */
		if (!is_numeric($page) || !is_numeric($limit)) {
			throw new Exception('Invalid page or limit value');
		}
		if ($sort !== 'ASC' && $sort !== 'DESC') {
			$sort = 'ASC';
		}

		$hierarchy = $this->getUserHierarchy($user_id);
		$status = $this->getStatusUserCanRank($user_id, $hierarchy);
		if (!empty($status)) {
			$ids = $this->getAllFilesRankerCanAccessTo($user_id, $hierarchy, $package_id);
			if (!empty($ids)) {
				$MAX_RANK_VALUE = 999999;

				$query = $this->db->getQuery(true);
				$offset = ($page - 1) * $limit;
				$files['total'] = count($ids);
				$query->clear()
					->select('MAX(' . $this->db->quoteName('rank') . ')')
					->from($this->db->quoteName('#__emundus_ranking'))
					->where($this->db->quoteName('ccid') . ' IN (' . implode(',', $ids) . ')')
					->andWhere($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy));

				if (!empty($package_id)) {
					$query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
				}

				try {
					$this->db->setQuery($query);
					$max = $this->db->loadResult();
					if (!empty($max)) {
						$files['maxRankValue'] = (int)$max;
					}
				} catch (Exception $e) {
					Log::add('getFilesUserCanRank ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
					throw new Exception('An error occurred while fetching the files.' . $query->__toString());
				}

				$query->clear()
					->select('er.id as rank_id, applicant.firstname, applicant.lastname, CONCAT(applicant.firstname, " ", applicant.lastname) AS applicant, cc.id, cc.fnum, er.rank, er.locked, cc.status')
					->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
					->leftJoin($this->db->quoteName('#__emundus_users', 'applicant') . ' ON ' . $this->db->quoteName('cc.applicant_id') . ' = ' . $this->db->quoteName('applicant.user_id'));

				// if the user has a hierarchy order by, we need to get the rank of the files in that hierarchy
				if (!empty($hierarchy_order_by) && $hierarchy_order_by !== 'default' && $hierarchy_order_by != $hierarchy) {
					$leftJoin = $this->db->quoteName('#__emundus_ranking', 'er') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid') . ' AND er.hierarchy_id  = ' . $hierarchy;

					if (!empty($package_id)) {
						$leftJoin .= ' AND ' . $this->db->quoteName('er.package') . ' = ' . $this->db->quote($package_id);
					}

					$query->leftJoin($leftJoin);
					$sub_query = $this->db->getQuery(true);
					$sub_query->clear()
						->select('DISTINCT cc.id as ccid, IF(er.hierarchy_id = ' . $this->db->quote($hierarchy_order_by) . ', er.rank, -1) as `rank`, IF(er.hierarchy_id = ' . $this->db->quote($hierarchy_order_by) . ', CONCAT(u.firstname, " ", u.lastname), null) as `ranker_name`')
						->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
						->leftJoin($this->db->quoteName('#__emundus_ranking', 'er') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid'))
						->leftJoin($this->db->quoteName('#__emundus_users', 'u') . ' ON ' . $this->db->quoteName('u.user_id') . ' = ' . $this->db->quoteName('er.user_id'))
						->where($this->db->quoteName('cc.id') . ' IN (' . implode(',', $ids) . ')')
						->andWhere('(' . $this->db->quoteName('er.hierarchy_id') . ' = ' . $this->db->quote($hierarchy_order_by) . ' 
                          '. ( !empty($package_id) ?  ' AND ' . $this->db->quoteName('er.package') . ' = ' . $this->db->quote($package_id)  : ' ')
							.') OR ' . $this->db->quoteName('cc.id') . ' NOT IN (
                            SELECT cc.id
                            FROM `jos_emundus_campaign_candidature` AS `cc`
                            LEFT JOIN `jos_emundus_ranking` AS `er` ON `cc`.`id` = `er`.`ccid`
                            WHERE `cc`.`id` IN (' . implode(',', $ids) . ')
                            AND `er`.`hierarchy_id` = ' . $this->db->quote($hierarchy_order_by)
							. ( !empty($package_id) ?  ' AND `er`.`package` = ' . $this->db->quote($package_id)  : ' ' )
							. ')'
						);

					if ($limit !== -1) {
						$sub_query->setLimit($limit, $offset);
					}

					if ($sort === 'ASC') {
						$sub_query->order('IFNULL(IF(`er`.`hierarchy_id` = ' . $this->db->quote($hierarchy_order_by) . ' AND `rank` != -1, `rank`, null), ' . $MAX_RANK_VALUE . ') ASC');
					} else {
						$sub_query->order('IFNULL(IF(`er`.`hierarchy_id` = ' . $this->db->quote($hierarchy_order_by) . ', `rank`, -1), -1) DESC');
					}

					$this->db->setQuery($sub_query);
					$ranks = $this->db->loadAssocList('ccid');

					if (!empty($ranks)) {
						$ids = array_keys($ranks);
					}

					$query->where($this->db->quoteName('cc.id') . ' IN (' . implode(',', $ids) . ')');

					if ($sort == 'ASC') {
						if ($order_by == 'rank') {
							$query->order('IFNULL(IF(er.rank > 0, er.rank, null), ' . $MAX_RANK_VALUE . ')' . $sort);
						} else {
							$query->order('er.user_id ' . $sort);
						}
					} else {
						if ($order_by == 'rank') {
							$query->order('IFNULL(er.rank, -1) ' . $sort);
						} else {
							$query->order('er.user_id ' . $sort);
						}
					}
				} else {
					$leftJoin = $this->db->quoteName('#__emundus_ranking', 'er') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid') . ' AND `er`.`hierarchy_id` = ' . $this->db->quote($hierarchy);

					if (!empty($package_id)) {
						$leftJoin .= ' AND ' . $this->db->quoteName('er.package') . ' = ' . $this->db->quote($package_id);
					}

					$query->leftJoin($leftJoin);
					$query->where($this->db->quoteName('cc.id') . ' IN (' . implode(',', $ids) . ')');

					if ($limit !== -1) {
						$query->setLimit($limit, $offset);
					}

					if ($sort === 'ASC') {
						if ($order_by == 'applicant') {
							$query->order('CONCAT(applicant.lastname, " ", applicant.firstname) ASC');
						} else {
							$query->order('IFNULL(IF(`rank` != -1, `rank`, null), ' . $MAX_RANK_VALUE . ') ASC');
						}
					} else {
						if ($order_by == 'applicant') {
							$query->order('CONCAT(applicant.lastname, " ", applicant.firstname) DESC');
						} else {
							$query->order('IFNULL(`rank`, -1) DESC');
						}
					}
				}

				try {
					$this->db->setQuery($query);
					$files['data'] = $this->db->loadAssocList();
				} catch (Exception $e) {
					Log::add('getFilesUserCanRank ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
					throw new Exception('An error occurred while fetching the files.' . $e->getMessage());
				}

				foreach ($files['data'] as $key => $file) {
					if (empty($file['locked']) && $file['locked'] != '0') {
						$files['data'][$key]['locked'] = 0;
					}

					if (!in_array($file['status'], $status) && $file['locked'] != 1) {
						$files['data'][$key]['locked'] = 1;
					}

					if (empty($file['rank'])) {
						$files['data'][$key]['rank'] = -1; // -1 means not ranked
					}
				}

				if (!empty($hierarchy_order_by) && $hierarchy_order_by !== 'default' && $hierarchy_order_by != $hierarchy) {
					foreach ($files['data'] as $key => $file) {
						if ($order_by == 'user_id') {
							if (isset($ranks[$file['id']])) {
								$files['data'][$key]['sort_rank'] = !empty($ranks[$file['id']]['ranker_name']) ? $ranks[$file['id']]['ranker_name'] : 'A';
							} else {
								$files['data'][$key]['sort_rank'] = 'A';
							}

							if ($files['data'][$key]['sort_rank'] == 'A' && $sort == 'ASC') {
								// the sort will be on a string, so set a value that will be at the end of the list
								$files['data'][$key]['sort_rank'] = 'Z';
							}
						} else {
							if (isset($ranks[$file['id']])) {
								$files['data'][$key]['sort_rank'] = !empty($ranks[$file['id']]['rank']) ? $ranks[$file['id']]['rank'] : -1;
							} else {
								$files['data'][$key]['sort_rank'] = -1;
							}

							if ($files['data'][$key]['sort_rank'] == -1 && $sort == 'ASC') {
								$files['data'][$key]['sort_rank'] = $MAX_RANK_VALUE;
							}
						}
					}

					// sort the files by rank
					usort($files['data'], function ($a, $b) use ($sort) {
						if ($sort == 'ASC') {
							return $a['sort_rank'] <=> $b['sort_rank'];
						} else {
							return $b['sort_rank'] <=> $a['sort_rank'];
						}
					});
				} else {
					usort($files['data'], function ($a, $b) use ($sort, $order_by) {
						if ($sort == 'ASC') {
							if ($order_by == 'applicant') {
								return $a['lastname'] <=> $b['lastname'];
							} else {
								return $a['rank'] <=> $b['rank'];
							}
						} else {
							if ($order_by == 'applicant') {
								return $b['lastname'] <=> $a['lastname'];
							} else {
								return $b['rank'] <=> $a['rank'];
							}
						}
					});
				}

				$hierarchy_data = $this->getHierarchyData($hierarchy);

				if (!empty($hierarchy_data['form_id'])) {
					// verify if a line has been added to the form for each files and lock ranking if not (must be done before the ranking)
					$files = $this->checkReviewed($files, $hierarchy_data, (int)$user_id);
				}

				if (!empty($package_id)) {
					$packages_data = $this->getUserPackages($user_id, $package_id);

					if (!empty($packages_data)) {
						// check package dates and lock files if necessary
						if ((!empty($packages_data[0]['start_time']) && $packages_data[0]['start_time'] > time()) || (!empty($packages_data[0]['end_time']) && $packages_data[0]['end_time'] < time())) {
							foreach ($files['data'] as $key => $file) {
								$files['data'][$key]['locked'] = 1;
							}
						}
					}
				}
			}
		}

		$this->helpDispatchEvent('onGetFilesUserCanRank', ['files' => &$files, 'user_id' => $user_id, 'page' => $page, 'limit' => $limit, 'sort' => $sort, 'hierarchy_id' => $hierarchy, 'hierarchy_order_by' => $hierarchy_order_by, 'package_id' => $package_id]);

		return $files;
	}

	/**
	 * @param array $files
	 * @param int $form_id
	 * @param int $user_id
	 * @return array
	 */
	private function checkReviewed(array $files, array $hierarchy_data, int $user_id): array
	{
		if (!empty($files) && !empty($hierarchy_data['form_id']) && !empty($hierarchy_data['db_table_name'])) {
			$query = $this->db->getQuery(true);

			foreach ($files['data'] as $key => $file) {
				$query->clear()
					->select('COUNT(*)')
					->from($this->db->quoteName($hierarchy_data['db_table_name']))
					->where('fnum = ' . $this->db->quote($file['fnum']));

				try {
					$this->db->setQuery($query);
					$reviewed = $this->db->loadResult();
				} catch (Exception $e) {
					JLog::add('Failed to check if file was reviewed for ranking, user ' . $user_id . ' ' . $e->getMessage(), JLog::ERROR, 'com_emundus.ranking.php');
					$reviewed = 0;
				}

				if (empty($reviewed)) {
					$files['data'][$key]['locked'] = 1;
				}

				$files['data'][$key]['reviewed'] = !empty($reviewed);
			}
		}

		return $files;
	}

    private function getStatusUserCanRank($user_id, int $hierarchy = 0): array
    {
        $status = [];

        if (!empty($user_id)) {
            if (empty($hierarchy)) {
                $hierarchy = $this->getUserHierarchy($user_id);
            }

            $query = $this->db->getQuery(true);

            $query->clear()
                ->select('status')
                ->from($this->db->quoteName('#__emundus_ranking_hierarchy_editable_status'))
                ->where($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy));

	        try {
				$this->db->setQuery($query);
				$status = $this->db->loadColumn();
			} catch (Exception $e) {
				Log::add('getStatusUserCanRank ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
			}
        }

        return $status;
    }

    public function getHierarchyData($hierarchy_id): array
    {
	    $hierarchy = [];

	    if (!empty($hierarchy_id)) {
		    $query = $this->db->getQuery(true);

		    $query->clear()
			    ->select('erh.*, GROUP_CONCAT(erhp.profile_id) as profiles, fl.db_table_name, ff.label as form_label')
			    ->from($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh'))
			    ->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_profiles', 'erhp') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhp.hierarchy_id'))
			    ->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('erh.form_id'))
			    ->leftJoin($this->db->quoteName('#__fabrik_forms', 'ff') . ' ON ' . $this->db->quoteName('fl.form_id') . ' = ' . $this->db->quoteName('ff.id'))
			    ->where($this->db->quoteName('erh.id') . ' = ' . $this->db->quote($hierarchy_id))
			    ->group($this->db->quoteName('erh.id'));

		    try {
			    $this->db->setQuery($query);
			    $hierarchy = $this->db->loadAssoc();
			    $hierarchy['profiles'] = explode(',', $hierarchy['profiles']);
			    $hierarchy['form_id'] = (int)$hierarchy['form_id'];
			    $hierarchy['form_label'] = Text::_($hierarchy['form_label']);
		    } catch (Exception $e) {
			    Log::add('getUserHierarchyData ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
		    }
	    }

	    return $hierarchy;
    }

    /*
     * Get the hierarchy of a user
     * @param $user_id
     * @param $search_current_profile, if true, it will search the hierarchy of the current profile in order to handle multiprofile users
     * @return int
     */
    public function getUserHierarchy($user_id, $search_current_profile = true): int
    {
        $hierarchy = 0;

        if (!empty($user_id)) {
            $query = $this->db->getQuery(true);

            if ($search_current_profile) {
                $emundus_user = Factory::getApplication()->getSession()->get('emundusUser');
                $profile_id = $emundus_user->profile;

                $query->clear()
                    ->select('erh.id')
                    ->from($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh'))
                    ->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_profiles', 'erhp') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhp.hierarchy_id'))
                    ->where($this->db->quoteName('erhp.profile_id') . ' = ' . $this->db->quote($profile_id));

                $this->db->setQuery($query);
                $hierarchy = $this->db->loadResult();
            }

            if (empty($hierarchy)) {
                $query->clear()
                    ->select('erh.id')
                    ->from($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh'))
                    ->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_profiles', 'erhp') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhp.hierarchy_id'))
                    ->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('eu.profile') . ' = ' . $this->db->quoteName('erhp.profile_id'))
                    ->where($this->db->quoteName('eu.user_id') . ' = ' . $this->db->quote($user_id));

                try {
                    $this->db->setQuery($query);
                    $hierarchy = $this->db->loadResult();
                } catch (Exception $e) {
                    Log::add('getUserHierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
                }
            }

			if (empty($hierarchy)) {
				$hierarchy = 0;
			}
        }

        return $hierarchy;
    }

    private function getStatusHierarchyCanSee($hierarchy_id) {
        $status = [];

        if (!empty($hierarchy_id)) {
            $db = $this->db;
            $query = $db->getQuery(true);

            $query->select('status')
                ->from('#__emundus_ranking_hierarchy_visible_status')
                ->where('hierarchy_id = ' . $db->quote($hierarchy_id));

            try {
                $db->setQuery($query);
                $status = $db->loadColumn();
            } catch (Exception $e) {
                Log::add('failed to get visible status for hierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
            }
        }

        return $status;
    }

	public function getAllFilesRankerCanAccessTo($user_id, $hierarchy = null, $package = null, $skip_status = false)
	{
		$file_ids = [];

		if (!empty($user_id)) {
			if (empty($hierarchy)) {
				$hierarchy = $this->getUserHierarchy($user_id);
			}

			$visible_status = [];
			if (!empty($hierarchy)) {
				$visible_status = $this->getStatusHierarchyCanSee($hierarchy);
			}

			$query = $this->db->getQuery(true);
			$query->select('DISTINCT cc.id')
				->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
				->leftJoin($this->db->quoteName('#__emundus_users_assoc', 'eua') . ' ON ' . $this->db->quoteName('cc.fnum') . ' = ' . $this->db->quoteName('eua.fnum'))
				->where($this->db->quoteName('eua.user_id') . ' = ' . $this->db->quote($user_id))
				->andWhere($this->db->quoteName('eua.action_id') . ' = 1')
				->andWhere($this->db->quoteName('eua.r') . ' = 1')
				->andWhere($this->db->quoteName('cc.published') . ' = 1');

			if (!$this->can_user_rank_himself) {
				$query->andWhere($this->db->quoteName('cc.applicant_id') . ' != ' . $this->db->quote($user_id));
			}

			if (!empty($package)) {
				$data = $this->getHierarchyData($hierarchy);

				if ($data['package_by'] == 'jos_emundus_setup_campaigns.id') {
					$query->andWhere($this->db->quoteName('cc.campaign_id') . ' = ' . $this->db->quote($package));
				}
			}

			if (!empty($visible_status) && !$skip_status) {
				$query->andWhere($this->db->quoteName('cc.status') . ' IN (' . implode(',', $visible_status) . ')');
			}

			try {
				$this->db->setQuery($query);
				$users_assoc_ccids = $this->db->loadColumn();
			} catch (Exception $e) {
				$users_assoc_ccids = [];
			}

			if (!empty($users_assoc_ccids)) {
				$file_ids = array_merge($file_ids, $users_assoc_ccids);
			}

			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
			$m_users = new EmundusModelUsers();
			$groups = $m_users->getUserGroups($user_id, 'Column');

			if (!empty($groups)) {
				$query->clear()
					->select('DISTINCT cc.id')
					->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
					->leftJoin($this->db->quoteName('#__emundus_group_assoc', 'ega') . ' ON ' . $this->db->quoteName('cc.fnum') . ' = ' . $this->db->quoteName('ega.fnum'))
					->where($this->db->quoteName('ega.group_id') . ' IN (' . implode(',', $groups) . ')')
					->andWhere($this->db->quoteName('ega.action_id') . ' = 1')
					->andWhere($this->db->quoteName('ega.r') . ' = 1')
					->andWhere($this->db->quoteName('cc.published') . ' = 1');

				if (!$this->can_user_rank_himself) {
					$query->andWhere($this->db->quoteName('cc.applicant_id') . ' != ' . $this->db->quote($user_id));
				}

				if (!empty($package)) {
					$data = $this->getHierarchyData($hierarchy);

					if ($data['package_by'] == 'jos_emundus_setup_campaigns.id') {
						$query->andWhere($this->db->quoteName('cc.campaign_id') . ' = ' . $this->db->quote($package));
					}
				}

				if (!empty($visible_status) && !$skip_status) {
					$query->andWhere($this->db->quoteName('cc.status') . ' IN (' . implode(',', $visible_status) . ')');
				}

				$this->db->setQuery($query);
				$group_assoc_ccids = $this->db->loadColumn();

				if (!empty($group_assoc_ccids)) {
					$file_ids = array_merge($file_ids, $group_assoc_ccids);
				}
			}

			$programs = $m_users->getUserGroupsProgramme($user_id);
			if (!empty($programs)) {
				$query->clear()
					->select('DISTINCT cc.id')
					->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
					->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('cc.campaign_id') . ' = ' . $this->db->quoteName('esc.id'))
					->where($this->db->quoteName('esc.training') . ' IN (' . implode(',', $this->db->quote($programs)) . ')')
					->andWhere($this->db->quoteName('cc.published') . ' = 1');

				if (!$this->can_user_rank_himself) {
					$query->andWhere($this->db->quoteName('cc.applicant_id') . ' != ' . $this->db->quote($user_id));
				}

				if (!empty($package)) {
					$data = $this->getHierarchyData($hierarchy);

					if ($data['package_by'] == 'jos_emundus_setup_campaigns.id') {
						$query->andWhere($this->db->quoteName('cc.campaign_id') . ' = ' . $this->db->quote($package));
					}
				}

				if (!empty($visible_status) && !$skip_status) {
					$query->andWhere($this->db->quoteName('cc.status') . ' IN (' . implode(',', $visible_status) . ')');
				}

				$this->db->setQuery($query);
				$program_assoc_ccids = $this->db->loadColumn();

				if (!empty($program_assoc_ccids)) {
					$file_ids = array_merge($file_ids, $program_assoc_ccids);
				}
			}

			$file_ids = array_unique($file_ids);
		}

		$this->helpDispatchEvent('onGetAllFilesRankerCanAccessTo', ['file_ids' => &$file_ids, 'user_id' => $user_id, 'hierarchy_id' => $hierarchy, 'package_id' => $package]);

		return $file_ids;
	}

	/**
	 * @param $user_id
	 * @return array
	 */
	public function getOtherRankingsRankerCanSee($user_id, $limit_hierarchy_ids = null, $package = null)
	{
		$rankings = [];

		if (!empty($user_id)) {
			$hierarchies = $this->getHierarchiesUserCanSee($user_id);

			if (isset($limit_hierarchy_ids)) {
				$hierarchies = array_filter($hierarchies, function ($hierarchy) use ($limit_hierarchy_ids) {
					return in_array($hierarchy['id'], $limit_hierarchy_ids);
				});
			}

			$ids = $this->getAllFilesRankerCanAccessTo($user_id, null, $package);

			if (!empty($hierarchies) && !empty($ids)) {
				$query = $this->db->getQuery(true);

				foreach ($hierarchies as $hierarchy) {
					$data = [
						'hierarchy_id' => $hierarchy['id'],
						'label' => $hierarchy['label'],
						'files' => [],
						'rankers' => []
					];

					$query->clear()
						->select('CONCAT(applicant.firstname, " ", applicant.lastname) AS applicant, cc.id, cc.fnum, cc.status, cr.rank, cr.locked, cr.user_id as ranker_id')
						->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
						->leftJoin($this->db->quoteName('#__emundus_users', 'applicant') . ' ON ' . $this->db->quoteName('cc.applicant_id') . ' = ' . $this->db->quoteName('applicant.user_id'))
						->leftJoin($this->db->quoteName('#__emundus_ranking', 'cr') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('cr.ccid'))
						->where('cc.id IN (' . implode(',', $ids) . ')')
						->andWhere($this->db->quoteName('cr.hierarchy_id') . ' = ' . $hierarchy['id']);

					try {
						$this->db->setQuery($query);
						$data['files'] = $this->db->loadAssocList();
					} catch (Exception $e) {
						Log::add('getOtherRankingsRankerCanSee ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
						throw new Exception('An error occurred while fetching the files.' . $e->getMessage());
					}

					$query->clear()
						->select('CONCAT(u.firstname, " ", u.lastname) AS name, r.user_id')
						->from($this->db->quoteName('#__emundus_ranking', 'r'))
						->leftJoin($this->db->quoteName('#__emundus_users', 'u') . ' ON ' . $this->db->quoteName('r.user_id') . ' = ' . $this->db->quoteName('u.user_id'))
						->where('r.ccid IN (' . implode(',', $ids) . ')')
						->andWhere($this->db->quoteName('r.hierarchy_id') . ' = ' . $hierarchy['id']);

					try {
						$this->db->setQuery($query);
						$data['rankers'] = $this->db->loadAssocList('user_id');
					} catch (Exception $e) {
						Log::add('getOtherRankingsRankerCanSee ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
						throw new Exception('An error occurred while fetching the files.');
					}

					foreach($data['files'] as $key => $file) {
						if ($file['status'] !== $hierarchy['status']) {
							$data['files'][$key]['locked'] = 1;
						}

						// todo: also handle potential hierarchy end date
					}

					$rankings[] = $data;
				}
			}
		}

		return $rankings;
	}


    /**
     * @param $ids array of hierarchy ids, if empty, it will return all the hierarchies
     */
    public function getHierarchies(array $ids = []): array
    {
        $hierarchies = [];

        $query = $this->db->getQuery(true);
        $query->clear()
            ->select('erh.*, GROUP_CONCAT(DISTINCT erhp.profile_id) as profiles, GROUP_CONCAT(DISTINCT erhv.visible_hierarchy_id) as visible_hierarchy_ids, GROUP_CONCAT(DISTINCT erhvs.status) as visible_status, GROUP_CONCAT(DISTINCT erhes.status) as editable_status')
            ->from($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh'))
            ->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_profiles', 'erhp') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhp.hierarchy_id'))
            ->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_view', 'erhv') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhv.hierarchy_id'))
			->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_visible_status', 'erhvs') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhvs.hierarchy_id'))
			->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_editable_status', 'erhes') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhes.hierarchy_id'));

        if (!empty($ids)) {
            $query->where($this->db->quoteName('id') . ' IN (' . implode(',', $ids) . ')');
        }

        $query->group($this->db->quoteName('erh.id'));
        try {
            $this->db->setQuery($query);
            $hierarchies = $this->db->loadAssocList();
        } catch (Exception $e) {
            Log::add('getHierarchies ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
        }

        foreach($hierarchies as $key => $hierarchy) {
            $hierarchies[$key]['profiles'] = !empty($hierarchy['profiles']) ? explode(',', $hierarchy['profiles']) : [];
            $hierarchies[$key]['visible_hierarchy_ids'] =  !empty($hierarchy['visible_hierarchy_ids']) ? explode(',', $hierarchy['visible_hierarchy_ids']) : [];
            $hierarchies[$key]['visible_status'] = $hierarchy['visible_status'] != '' ?  explode(',', $hierarchy['visible_status']) : [];
			$hierarchies[$key]['editable_status'] = $hierarchy['editable_status'] != '' ?  explode(',', $hierarchy['editable_status']) : [];
        }

        return $hierarchies;
    }

    public function deleteHierarchy($id)
    {
        $deleted = false;

        if (!empty($id)) {
            $query = $this->db->getQuery(true);
            $query->delete('#__emundus_ranking_hierarchy')
                ->where('id = ' . $id);

            try {
                $this->db->setQuery($query);
                $deleted = $this->db->execute();
            } catch(Exception $e) {
                Log::add('Delete ranking hierarchy ' . $id . ' failed ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
            }
        }

        return $deleted;
    }

    /**
     * @param $user_id
     * @return array
     * @throws Exception
     */
    public function getHierarchiesUserCanSee($user_id)
    {
        $hierarchies = [];
        $user_hierarchy = $this->getUserHierarchy($user_id);

        if (!empty($user_hierarchy)) {
            $query = $this->db->getQuery(true);

            $query->clear()
                ->select('DISTINCT ' . $this->db->quoteName('erh.id') . ', ' . $this->db->quoteName('erh.label'))
                ->from($this->db->quoteName('#__emundus_ranking_hierarchy_view', 'erhv'))
                ->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh') . ' ON ' . $this->db->quoteName('erhv.visible_hierarchy_id') . ' = ' . $this->db->quoteName('erh.id'))
                ->where($this->db->quoteName('erhv.hierarchy_id') . ' = ' . $this->db->quote($user_hierarchy))
                ->order($this->db->quoteName('erhv.ordering'));

            if (!empty($this->filters)) {
                // check if there is a filter on hierarchy_id and if so, add it to the query
                $subquery = $this->db->getQuery(true);
                foreach ($this->filters as $filter) {
                    $subquery->clear()
                        ->select('name')
                        ->from($this->db->quoteName('#__fabrik_elements'))
                        ->where($this->db->quoteName('id') . ' = ' . $this->db->quote($filter['id']));

                    $this->db->setQuery($subquery);
                    $element = $this->db->loadResult();

                    if ($element == 'hierarchy_id' && !empty($filter['value']) && $filter['value'] != 'all' && $filter['value'] != ['all']) {
                        $query->where($this->h_files->writeQueryWithOperator('erh.id', $filter['value'], $filter['operator']));
                    }
                }
            }

            try {
                $this->db->setQuery($query);
                $hierarchies = $this->db->loadAssocList();
            } catch (Exception $e) {
                Log::add('getHierarchiesUserCanSee ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
                throw new Exception('An error occurred while fetching the hierarchies.');
            }
        }

        return $hierarchies;
    }


    /**
     * @param $id
     * @param $user_id
     * @param $hierarchy_id
     * @return int
     */
    public function getFileRanking($id, $user_id, $hierarchy_id)
    {
        $rank = -1;

        if (!empty($id) && !empty($user_id) && !empty($hierarchy_id)) {
            $query = $this->db->getQuery(true);
            $query->clear()
                ->select($this->db->quoteName('rank'))
                ->from($this->db->quoteName('#__emundus_ranking'))
                ->where($this->db->quoteName('ccid') . ' = ' . $this->db->quote($id))
                ->andWhere($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id))
                ->andWhere($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id));

            try {
                $this->db->setQuery($query);
                $rank = (int)$this->db->loadResult();

                if ($rank < 1) {
                    $rank = -1;
                }
            } catch (Exception $e) {
                Log::add('getFileRanking ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
            }
        }

        return $rank;
    }

    /**
     * @param $user_id
     * @param $hierarchy_id
     * @return array
     */
    public function getAllRankings($user_id, $hierarchy_id = null)
    {
        $rankings = [];

        if (!empty($user_id)) {
            if (empty($hierarchy_id)) {
                $hierarchy_id = $this->getUserHierarchy($user_id);
            }

            $query = $this->db->getQuery(true);
            $query->select('*')
                ->from($this->db->quoteName('#__emundus_ranking'))
                ->where($this->db->quoteName('user_id') . ' = ' . $user_id)
                ->andWhere($this->db->quoteName('hierarchy_id') . ' = ' . $hierarchy_id);

            try {
                $this->db->setQuery($query);
                $rankings = $this->db->loadAssocList();
            } catch (Exception $e) {
                Log::add('Failed to get user rankings ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
            }
        }

        return $rankings;
    }

    /**
     * @param $id
     * @param $user_id
     * @param $new_rank
     * @param $hierarchy_id
     * @return false
     * @throws Exception
     */
    public function updateFileRanking($id, $user_id, $new_rank, $hierarchy_id, $package_id = 0)
    {
        $updated = false;

        if (!empty($id) && !empty($user_id) && !empty($new_rank) && !empty($hierarchy_id)) {
            $query = $this->db->getQuery(true);
            $query->clear()
                ->select($this->db->quoteName('applicant_id'))
                ->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
                ->where($this->db->quoteName('cc.id') . ' = ' . $this->db->quote($id));

            $this->db->setQuery($query);
            $applicant_id = $this->db->loadResult();

            if ($applicant_id == $user_id && !$this->can_user_rank_himself) {
                throw new Exception(Text::_('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_RANK_OWN_FILE'));
            }

            $all_mighty_user = JFactory::getSession()->get('emundusUser')->profile == $this->all_rights_profile;

            $this->helpDispatchEvent('onBeforeUpdateFileRanking', ['id' => $id, 'user_id' => $user_id, 'new_rank' => $new_rank, 'hierarchy_id' => $hierarchy_id, 'package_id' => $package_id]);

            $query->clear()
                ->select($this->db->quoteName('cc.status'))
                ->from($this->db->quoteName('#__emundus_campaign_candidature', 'cc'))
                ->where($this->db->quoteName('cc.id') . ' = ' . $this->db->quote($id));
            $file_status = $this->db->setQuery($query)->loadResult();
            $status_user_can_rank = $this->getStatusUserCanRank($user_id, $hierarchy_id);

            if (in_array($file_status, $status_user_can_rank) || $all_mighty_user) {
                $query->clear()
                    ->select($this->db->quoteName('id') . ', ' . $this->db->quoteName('rank') . ', ' . $this->db->quoteName('locked'))
                    ->from($this->db->quoteName('#__emundus_ranking'))
                    ->where($this->db->quoteName('ccid') . ' = ' . $this->db->quote($id))
                    ->andWhere($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id));

                if (!empty($package_id)) {
                    $query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
                }

                $this->db->setQuery($query);
                $ranking = $this->db->loadAssoc();

                if (!empty($ranking) && $ranking['locked'] == 1 && !$all_mighty_user) {
                    throw new Exception(Text::_('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_LOCKED'));
                }

                $old_rank = !empty($ranking) && !empty($ranking['rank']) && $ranking['rank'] > 0 ? $ranking['rank'] : -1;

                if ($old_rank == $new_rank) {
                    $updated = true;
                } else {
                    // different people can rank same files, so we dont get them by user but by their accessibility, hierarchy and package
                    $ids_user_can_rank = $this->getAllFilesRankerCanAccessTo($user_id, $hierarchy_id, $package_id);

                    // if the new rank is -1, we need to decrease all ranks above the old rank by 1, unless they are locked
                    if ($new_rank != -1) {
                        // does the rank i want to reach already taken by another file and locked ?
                        $query->clear()
                            ->select($this->db->quoteName('er.ccid') . ', ' . $this->db->quoteName('er.locked') . ', ' . $this->db->quoteName('cc.status') . ', ' . $this->db->quoteName('cc.fnum'))
                            ->from($this->db->quoteName('#__emundus_ranking', 'er'))
                            ->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid'))
                            ->where($this->db->quoteName('er.rank') . ' = ' . $this->db->quote($new_rank))
                            ->andWhere($this->db->quoteName('er.hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id))
                            ->andWhere($this->db->quoteName('er.ccid') . ' IN (' . implode(',', $ids_user_can_rank) . ')');

                        if (!empty($package_id)) {
                            $query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
                        }

                        $this->db->setQuery($query);
                        $same_rank_data = $this->db->loadAssoc();

                        if (!empty($same_rank_data) && ($same_rank_data['locked'] == 1 || !in_array($same_rank_data['status'], $status_user_can_rank)) && !$all_mighty_user) {
                            throw new Exception(sprintf(Text::_('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_RANK_UNREACHABLE'), $new_rank, $same_rank_data['fnum']));
                        }

                        if (!empty($ranking) && !empty($ranking['id'])) {
                            $max_rank = $this->getMaxRankAvailable($hierarchy_id, $user_id, $ranking['id'], $package_id);
                        } else {
                            $max_rank = $this->getMaxRankAvailable($hierarchy_id, $user_id, null, $package_id);
                        }

                        if ($new_rank > $max_rank) {
                            throw new Exception(Text::_('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_NEW_RANK_UNREACHABLE'));
                        }
                    }

                    $re_arranged_ranking = [];
                    $locked_rank_positions = [];
                    if ($new_rank == -1) {
                        // all ranks superior or equal to old rank should be decreased by 1 unless they are locked
                        $query->clear()
                            ->select($this->db->quoteName('er.rank') . ', ' . $this->db->quoteName('er.id') . ', ' . $this->db->quoteName('er.locked') . ', ' . $this->db->quoteName('cc.status'))
                            ->from($this->db->quoteName('#__emundus_ranking', 'er'))
                            ->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid'))
                            ->where($this->db->quoteName('er.ccid') . ' IN (' . implode(',', $ids_user_can_rank) . ')')
                            ->andWhere($this->db->quoteName('er.hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id))
                            ->andWhere($this->db->quoteName('er.rank') . ' > ' . $this->db->quote($ranking['rank']))
                            ->order($this->db->quoteName('er.rank') . ' ASC');

                        if (!empty($package_id)) {
                            $query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
                        }

                        $this->db->setQuery($query);
                        $ranks = $this->db->loadAssocList();
                        $rank_to_apply = (int)$old_rank;

                        $locked_rank_positions = $all_mighty_user ? [] : array_filter(array_map(function ($rank) use ($status_user_can_rank) {
                            return $rank['locked'] == 1 || !in_array($rank['status'], $status_user_can_rank) ? $rank['rank'] : null;
                        }, $ranks));

                        foreach ($ranks as $rank) {
                            if (($rank['locked'] != 1 && in_array($rank['status'], $status_user_can_rank)) || $all_mighty_user) {
                                $re_arranged_ranking[$rank['id']] = $rank_to_apply;
                                $rank_to_apply++;

                                while (in_array($rank_to_apply, $locked_rank_positions)) {
                                    $rank_to_apply++;
                                }
                            }
                        }
                    } else if ($old_rank == -1) {
                        // all ranks superior or equal to new rank should be increased by 1 unless they are locked
                        $query->clear()
                            ->select($this->db->quoteName('er.rank') . ', ' . $this->db->quoteName('er.id') . ', ' . $this->db->quoteName('er.locked') . ', ' . $this->db->quoteName('cc.status'))
                            ->from($this->db->quoteName('#__emundus_ranking', 'er'))
                            ->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid'))
                            ->where($this->db->quoteName('er.ccid') . ' IN (' . implode(',', $ids_user_can_rank) . ')')
                            ->andWhere($this->db->quoteName('er.hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id))
                            ->andWhere($this->db->quoteName('er.rank') . ' >= ' . $this->db->quote($new_rank))
                            ->order($this->db->quoteName('er.rank') . ' ASC');

                        if (!empty($package_id)) {
                            $query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
                        }

                        $this->db->setQuery($query);
                        $ranks = $this->db->loadAssocList();
                        $rank_to_apply = $new_rank + 1;

                        $locked_rank_positions = $all_mighty_user ? [] : array_filter(array_map(function ($rank) use ($status_user_can_rank) {
                            return $rank['locked'] == 1 || !in_array($rank['status'], $status_user_can_rank) ? $rank['rank'] : null;
                        }, $ranks));

                        while (in_array($rank_to_apply, $locked_rank_positions)) {
                            $rank_to_apply++;
                        }

                        foreach ($ranks as $rank) {
                            if (($rank['locked'] != 1 && in_array($rank['status'], $status_user_can_rank)) || $all_mighty_user) {
                                $re_arranged_ranking[$rank['id']] = $rank_to_apply;
                                $rank_to_apply++;

                                while (in_array($rank_to_apply, $locked_rank_positions)) {
                                    $rank_to_apply++;
                                }
                            }
                        }
                    } else if ($old_rank > $new_rank) {
                        // all ranks between new rank and old rank should be increased by 1 unless they are locked
                        $query->clear()
                            ->select($this->db->quoteName('er.rank') . ', ' . $this->db->quoteName('er.id') . ', ' . $this->db->quoteName('er.locked') . ', ' . $this->db->quoteName('cc.status'))
                            ->from($this->db->quoteName('#__emundus_ranking', 'er'))
                            ->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid'))
                            ->where($this->db->quoteName('er.ccid') . ' IN (' . implode(',', $ids_user_can_rank) . ')')
                            ->andWhere($this->db->quoteName('er.hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id))
                            ->andWhere($this->db->quoteName('er.rank') . ' >= ' . $this->db->quote($new_rank))
                            ->andWhere($this->db->quoteName('er.rank') . ' < ' . $this->db->quote($old_rank))
                            ->order($this->db->quoteName('er.rank') . ' DESC');

                        if (!empty($package_id)) {
                            $query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
                        }

                        $this->db->setQuery($query);
                        $ranks = $this->db->loadAssocList();
                        $rank_to_apply = (int)$old_rank;

                        $locked_rank_positions = $all_mighty_user ? [] :  array_filter(array_map(function ($rank) use ($status_user_can_rank) {
                            return $rank['locked'] == 1 || !in_array($rank['status'], $status_user_can_rank) ? $rank['rank'] : null;
                        }, $ranks));

                        foreach ($ranks as $rank) {
                            if (($rank['locked'] != 1 && in_array($rank['status'], $status_user_can_rank)) || $all_mighty_user) {
                                $re_arranged_ranking[$rank['id']] = $rank_to_apply;
                                $rank_to_apply--;

                                while (in_array($rank_to_apply, $locked_rank_positions)) {
                                    $rank_to_apply--;
                                }
                            }
                        }
                    } else if ($old_rank < $new_rank) {
                        // all ranks between old rank and new rank should be decreased by 1 unless they are locked
                        $query->clear()
                            ->select($this->db->quoteName('er.rank') . ', ' . $this->db->quoteName('er.id') . ', ' . $this->db->quoteName('er.locked') . ', ' . $this->db->quoteName('cc.status'))
                            ->from($this->db->quoteName('#__emundus_ranking', 'er'))
                            ->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'cc') . ' ON ' . $this->db->quoteName('cc.id') . ' = ' . $this->db->quoteName('er.ccid'))
                            ->where($this->db->quoteName('er.ccid') . ' IN (' . implode(',', $ids_user_can_rank) . ')')
                            ->andWhere($this->db->quoteName('er.hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id))
                            ->andWhere($this->db->quoteName('er.rank') . ' > ' . $this->db->quote($old_rank))
                            ->andWhere($this->db->quoteName('er.rank') . ' <= ' . $this->db->quote($new_rank))
                            ->order($this->db->quoteName('er.rank') . ' ASC');

                        if (!empty($package_id)) {
                            $query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
                        }

                        $this->db->setQuery($query);
                        $ranks = $this->db->loadAssocList();
                        $rank_to_apply = (int)$old_rank;

                        $locked_rank_positions = $all_mighty_user ? [] : array_filter(array_map(function ($rank) use ($status_user_can_rank) {
                            return $rank['locked'] == 1 || !in_array($rank['status'], $status_user_can_rank) ? $rank['rank'] : null;
                        }, $ranks));

                        foreach ($ranks as $rank) {
                            if (($rank['locked'] != 1 && in_array($rank['status'], $status_user_can_rank)) || $all_mighty_user) {
                                $re_arranged_ranking[$rank['id']] = $rank_to_apply;
                                $rank_to_apply++;

                                while (in_array($rank_to_apply, $locked_rank_positions)) {
                                    $rank_to_apply++;
                                }
                            }
                        }
                    }

	                foreach ($re_arranged_ranking as $rank_row_id => $new_rank_for_row) {
		                $query->clear()
			                ->update($this->db->quoteName('#__emundus_ranking'))
			                ->set($this->db->quoteName('rank') . ' = ' . $this->db->quote($new_rank_for_row))
			                ->where($this->db->quoteName('id') . ' = ' . $this->db->quote($rank_row_id))
			                ->andWhere($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id));

		                try {
			                $this->db->setQuery($query);
			                $updated = $this->db->execute();

			                if ($updated) {
				                Log::add('User ' . $user_id . ' updated rank row ' . $rank_row_id . ' to rank ' . $new_rank_for_row . ' for hierarchy ' . $hierarchy_id, Log::INFO, 'com_emundus.ranking.php');

				                $query->clear()
					                ->select('ecc.fnum, ecc.id')
					                ->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
					                ->leftJoin($this->db->quoteName('#__emundus_ranking', 'er') . ' ON ' . $this->db->quoteName('ecc.id') . ' = ' . $this->db->quoteName('er.ccid'))
					                ->where($this->db->quoteName('er.id') . ' = ' . $this->db->quote($rank_row_id));

				                $this->db->setQuery($query);
				                $re_arranged_file_data = $this->db->loadAssoc();

				                $user_to = EmundusHelperFiles::getApplicantIdFromFileId($re_arranged_file_data['id']);
				                $action_id = $this->logger->getActionId('ranking');
				                $this->logger->log($user_id, $user_to, $re_arranged_file_data['fnum'], $action_id, 'u', 'COM_EMUNDUS_RANKING_UPDATE_RANKING', json_encode(['new_rank' => $new_rank_for_row, 'hierarchy' => $hierarchy_id, 'user_id' => $user_id, 'context' => 'rearrange']));
				                $this->helpDispatchEvent('onAfterUpdateFileRanking', ['id' => $id, 'user_id' => $user_id, 'new_rank' => $new_rank_for_row, 'old_rank' => '', 'hierarchy_id' => $hierarchy_id, 'package_id' => $package_id]);


			                } else {
				                Log::add('User ' . $user_id . ' failed to update rank row ' . $rank_row_id . ' to rank ' . $new_rank_for_row . ' for hierarchy ' . $hierarchy_id, Log::WARNING, 'com_emundus.ranking.php');
			                }


		                } catch (Exception $e) {
			                Log::add('updateFileRanking ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
			                throw new Exception('An error occurred while updating files ranking.');
		                }
	                }
                }

                $query->clear()
                    ->select('id')
                    ->from($this->db->quoteName('#__emundus_ranking'))
                    ->where($this->db->quoteName('ccid') . ' = ' . $this->db->quote($id))
                    ->andWhere($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id));

                $this->db->setQuery($query);
                $ranking_id = $this->db->loadResult();

                if (!empty($ranking_id)) {
                    $query->clear()
                        ->update($this->db->quoteName('#__emundus_ranking'))
                        ->set($this->db->quoteName('rank') . ' = ' . $this->db->quote($new_rank))
                        ->where($this->db->quoteName('id') . ' = ' . $this->db->quote($ranking_id));

                    $this->db->setQuery($query);
                    $updated = $this->db->execute();
                } else {
                    $columns = ['ccid', 'user_id', 'rank', 'hierarchy_id'];
                    $values = [$id, $user_id, $new_rank, $hierarchy_id];

                    if (!empty($package_id)) {
                        $columns[] = 'package';
                        $values[] = $package_id;
                    }

                    $query->clear()
                        ->insert($this->db->quoteName('#__emundus_ranking'))
                        ->columns($this->db->quoteName($columns))
                        ->values(implode(',', $values));

                    $this->db->setQuery($query);
                    $updated = $this->db->execute();
                }

                if ($updated) {
                    $fnum = EmundusHelperFiles::getFnumFromId($id);
                    $user_to = EmundusHelperFiles::getApplicantIdFromFileId($id);
                    $action_id = $this->logger->getActionId('ranking');
                    $this->logger->log($user_id, $user_to, $fnum, $action_id, 'u', 'COM_EMUNDUS_RANKING_UPDATE_RANKING', json_encode(['old_rank' => $old_rank, 'new_rank' => $new_rank]));
                    $this->helpDispatchEvent('onAfterUpdateFileRanking', ['id' => $id, 'user_id' => $user_id, 'new_rank' => $new_rank, 'old_rank' => $old_rank, 'hierarchy_id' => $hierarchy_id, 'package_id' => $package_id]);

					Log::add('User ' . $user_id . ' updated file ranking ' . $fnum . ' to rank ' . $new_rank . ' old rank was ' . $old_rank . ' for hierarchy ' . $hierarchy_id, Log::INFO, 'com_emundus.ranking.php');
				}
            } else {
                throw new Exception(Text::_('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_RANK_NOT_ALLOWED_STATUS'));
            }
        }

        return $updated;
    }

    /**
     * Returns the max next position reachable.
     * If current max rank is x, then return x+1 (unless max is -1, return 1)
     * @param $hierarchy_id
     * @param $user_id
     * @param null $rank_row_id if we want to exclude a rank row from the calculation
     * @param null $package_id if we want to filter by package
     * @return int
     */
    public function getMaxRankAvailable($hierarchy_id, $user_id, $rank_row_id = null, $package_id = null)
    {
        $max_value_reachable = 1;

        if (!empty($hierarchy_id) && !empty($user_id)) {
            $rankable_ids = $this->getAllFilesRankerCanAccessTo($user_id, $hierarchy_id, $package_id);

            $query = $this->db->getQuery(true);
            $query->clear()
                ->select('MAX(' . $this->db->quoteName('rank') . ') as max')
                ->from($this->db->quoteName('#__emundus_ranking'))
                ->where($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id))
                ->andWhere($this->db->quoteName('ccid') . ' IN (' . implode(',', $rankable_ids) . ')');

            if (!empty($rank_row_id)) {
                $query->andWhere($this->db->quoteName('id') . ' != ' . $rank_row_id);
            }

            if (!empty($package_id)) {
                $query->andWhere($this->db->quoteName('package') . ' = ' . $this->db->quote($package_id));
            }

            $this->db->setQuery($query);
            $current_max_value = $this->db->loadResult();

            if ($current_max_value > 0) {
                $max_value_reachable = $current_max_value + 1;
            }

            if ($max_value_reachable > sizeof($rankable_ids)) {
                $max_value_reachable =  sizeof($rankable_ids);
            }
        }

        return $max_value_reachable;
    }

    /**
     * @param $hierarchy_id
     * @param $user_id
     * @param $locked
     * @return boolean
     */
    public function toggleLockFilesOfHierarchyRanking($hierarchy_id, $user_id, $locked = 1): bool
    {
        $toggled = false;

        if (!empty($hierarchy_id) && !empty($user_id)) {
            $query = $this->db->getQuery(true);

            $query->clear()
                ->update($this->db->quoteName('#__emundus_ranking'))
                ->set($this->db->quoteName('locked') . ' = ' . $this->db->quote($locked))
                ->where($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id))
                ->andWhere($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));

            $this->db->setQuery($query);
            $toggled = $this->db->execute();

            if ($locked == 1 && $toggled) {
                /**
                 * Send email to parent id hierarchy to inform that the ranking has been locked
                 */
                $query->clear()
                    ->select('erh.parent_id, erh.label')
                    ->from($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh'))
                    ->where($this->db->quoteName('erh.id') . ' = ' . $this->db->quote($hierarchy_id));

                $this->db->setQuery($query);
                $hierarchy_infos = $this->db->loadAssoc();

                if (!empty($hierarchy_infos['parent_id'])) {
                    $query->clear()
                        ->select('erhp.profile_id')
                        ->from($this->db->quoteName('#__emundus_ranking_hierarchy', 'erh'))
                        ->leftJoin($this->db->quoteName('#__emundus_ranking_hierarchy_profiles', 'erhp') . ' ON ' . $this->db->quoteName('erh.id') . ' = ' . $this->db->quoteName('erhp.hierarchy_id'))
                        ->where($this->db->quoteName('erh.id') . ' = ' . $this->db->quote($hierarchy_infos['parent_id']));
                    $this->db->setQuery($query);
                    $profile_ids = $this->db->loadColumn();

                    $query->clear()
                        ->select('DISTINCT u.email')
                        ->from($this->db->quoteName('#__users', 'u'))
                        ->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('eu.user_id'))
                        ->leftJoin($this->db->quoteName('#__emundus_users_profiles', 'eup') . ' ON ' . $this->db->quoteName('eup.user_id') . ' = ' . $this->db->quoteName('eu.user_id'))
                        ->where('(' . $this->db->quoteName('eu.profile') . ' IN (' . implode(',' , $profile_ids) .  ') OR ' . $this->db->quoteName('eup.profile_id') . ' IN (' . implode(',' , $profile_ids) .  '))')
                        ->andWhere('u.block = 0');

                    $this->db->setQuery($query);
                    $emails = $this->db->loadColumn();

                    if (!empty($emails)) {
                        require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
                        $m_emails = new EmundusModelEmails();
                        $email_to_send = 'ranking_locked';

                        $query->clear()
                            ->select('firstname, lastname')
                            ->from($this->db->quoteName('#__emundus_users'))
                            ->where($this->db->quoteName('user_id') . ' = ' . $this->db->quote($user_id));

                        $this->db->setQuery($query);
                        $user = $this->db->loadAssoc();

                        $post = [
                            'RANKER_NAME' => $user['firstname'] . ' ' . $user['lastname'],
                            'RANKER_HIERARCHY' => $hierarchy_infos['label'],
                        ];
                        foreach ($emails as $email) {
                            $m_emails->sendEmailNoFnum($email, $email_to_send, $post, $user_id);
                        }
                    }
                }
            }
        }

        return $toggled;
    }

    /**
     * @param $user_asking ,
     * @param $users , I can specify the users to ask to lock their rankings
     * @param $hierarchies , I can specify all users of a hierarchy to lock their rankings
     * @return array
     * @throws Exception if I try to ask rankings to be locked for a user or a hierarchy I am not allowed to
     */
    public function askUsersToLockRankings($user_asking, $users, $hierarchies): array
    {
        $response = [
            'asked' => false,
            'asked_to' => []
        ];

        if (empty($user_asking)) {
            throw new Exception(Text::_('USER_ASKING_TO_LOCK_MUST_BE_DEFINED'));
        }

        $ccids = $this->getAllFilesRankerCanAccessTo($user_asking);
        if (empty($ccids)) {
            throw new Exception(Text::_('USER_ASKING_TO_LOCK_FILES_BUT_HAS_NO_ACCESS'));
        }

        if (!empty($users) || !empty($hierarchies)) {
            $query = $this->db->getQuery(true);

            if (!empty($hierarchies)) {
                $hierarchies_user_as_access_to = $this->getHierarchiesUserCanSee($user_asking);
                $hierarchy_ids_user_as_access_to = array_map(function ($hierarchy) {
                    return $hierarchy['id'];
                }, $hierarchies_user_as_access_to);

                foreach ($hierarchies as $key => $hierarchy) {
                    if (!in_array($hierarchy, $hierarchy_ids_user_as_access_to)) {
                        unset($hierarchies[$key]);
                        // could log attempt to ask wrong hierarchy
                    }
                }

                if (!empty($hierarchies)) {
                    $query->clear()
                        ->select('DISTINCT user_id')
                        ->from($this->db->quoteName('#__emundus_ranking'))
                        ->where($this->db->quoteName('ccid') . ' IN (' . implode(',', $ccids) . ')')
                        ->andWhere($this->db->quoteName('hierarchy_id') . ' IN (' . implode(',', $hierarchies) . ')')
                        ->andWhere($this->db->quoteName('user_id') . ' != ' . $user_asking);

                    try {
                        $this->db->setQuery($query);
                        $hierarchy_users = $this->db->loadColumn();

                        $users = array_merge($users, $hierarchy_users);
                        $users = array_unique($users);
                    } catch (Exception $e) {
                        Log::add('Failed to get users of hierarchy ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking');
                    }
                }
            }

            if (!empty($users)) {
                // keep only users I can ask to
                $query->clear()
                    ->select('DISTINCT u.email, u.id')
                    ->from($this->db->quoteName('#__emundus_ranking', 'er'))
                    ->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('er.user_id'))
                    ->where($this->db->quoteName('er.ccid') . ' IN (' . implode(',', $ccids) . ')')
                    ->andWhere($this->db->quoteName('er.user_id') . ' IN (' . implode(',', $users) . ')')
                    ->andWhere($this->db->quoteName('er.locked') . ' = 0');

                try {
                    $this->db->setQuery($query);
                    $user_emails = $this->db->loadAssoclist();
                } catch (Exception $e) {
                    Log::add('Failed to get emails ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking');
                }

                if (!empty($user_emails)) {
                    require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
                    $m_emails = new EmundusModelEmails();
                    $email_to_send = 'ask_lock_ranking';
                    $response['asked'] = true;

                    foreach ($user_emails as $user) {
                        $sent = $m_emails->sendEmailNoFnum($user['email'], $email_to_send, null, $user['id']);

                        if ($sent) {
                            $response['asked_to'][] = $user['email'];
                        }
                    }
                }
            }
        }

        return $response;
    }

    public function prepareDataToExport($user_id, $package_ids, $hierachy_ids, $ordered_columns): array
    {
        $lines = [];

        if (!empty($package_ids))
        {
            $export_array = [];

            $files_by_package = [];
            foreach ($package_ids as $package_id)
            {
                $files_by_package[$package_id] = $this->getFilesUserCanRank($user_id, 1, -1, 'ASC', 'default', $package_id)['data'];
            }

            if (!empty($files_by_package))
            {
                $query = $this->db->getQuery(true);
                $query->select('name')
                    ->from('#__users')
                    ->where('id = ' . $user_id);

                $this->db->setQuery($query);
                $user_name = $this->db->loadResult();

                $user_packages = $this->getUserPackages($user_id);
                $fnums         = [];
                $ccids         = [];

                if (!class_exists('EmundusModelFiles'))
                {
                    require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
                }
                $m_files = new EmundusModelFiles();
                $states  = $m_files->getAllStatus($user_id, 'step');

                $ordered_columns_keys = array_map(function ($column) {
                    return $column['id'];
                }, $ordered_columns);

                foreach ($files_by_package as $package_id => $files)
                {
                    $package_label = '';
                    foreach ($user_packages as $user_package)
                    {
                        if ($user_package['id'] == $package_id)
                        {
                            $package_label = $user_package['label'];
                            break;
                        }
                    }

                    if (!empty($files))
                    {
                        $hierarchy_data = $this->getHierarchyData($this->getUserHierarchy($user_id));
                        if (!empty($hierarchy_data['form_id'])) {

                            $this->addHierarchyFormDataToFiles($files, $hierarchy_data['form_id'], $ordered_columns_keys, $ordered_columns, $user_id);
                        }

                        if (!empty($hierachy_ids))
                        {
                            $other_rankings_values = $this->getOtherRankingsRankerCanSee($user_id, $hierachy_ids, $package_id);
                            foreach ($hierachy_ids as $hierachy_id)
                            {
                                $hierarchy_data = $this->getHierarchyData($hierachy_id);

                                $ordered_columns[] = [
                                    'id' => 'ranking_' . $hierachy_id,
                                    'label' => Text::_('COM_EMUNDUS_RANKING_EXPORT_RANKING') . ' ' . $hierarchy_data['label']
                                ];
                                $ordered_columns[] = [
                                    'id' => 'ranker_' . $hierachy_id,
                                    'label' => Text::_('COM_EMUNDUS_RANKING_EXPORT_RANKER') . ' - ' . $hierarchy_data['label']
                                ];

                                if (!empty($hierarchy_data['form_id'])) {
                                    $query->clear()
                                        ->select('jfe.name, jfl.db_table_name')
                                        ->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
                                        ->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
                                        ->leftJoin($this->db->quoteName('#__fabrik_lists', 'jfl') . ' ON jfl.form_id = jffg.form_id')
                                        ->where('jffg.form_id = ' . $this->db->quote($hierarchy_data['form_id']))
                                        ->andWhere('jfe.published = 1')
                                        ->andWhere('jfe.name NOT IN (' . $this->db->quote('id') . ')');

                                    $this->db->setQuery($query);
                                    $form_elements = $this->db->loadObjectList();

                                    foreach($form_elements as $element) {
                                        $ordered_columns_keys[] = $hierarchy_data['form_id'] . '-' . $element->name;
                                        $ordered_columns[] = [
                                            'id' => $hierarchy_data['form_id'] . '-' . $element->name,
                                            'label' => Text::_($element->name)
                                        ];
                                    }
                                }
                            }
                        }

                        $ordered_columns_id = [];
                        $ordered_columns_unique = [];

                        foreach ($ordered_columns as $item) {
                            if (!in_array($item['id'], $ordered_columns_id)) {
                                $ordered_columns_id[] = $item['id'];
                                $ordered_columns_unique[] = $item;
                            }
                        }

                        foreach ($files as $index => $file)
                        {
                            $file_data = [];
                            $fnums[]   = $file['fnum'];
                            $ccids[]   = $file['id'];

                            if (!empty($hierachy_ids))
                            {
                                foreach ($hierachy_ids as $hierachy_id)
                                {
                                    $hierarchy_data = $this->getHierarchyData($hierachy_id);
                                    $ranker = '';
                                    $rank   = '';

                                    foreach ($other_rankings_values as $other_ranking)
                                    {
                                        if ($other_ranking['fnum'] == $file['fnum'] && $other_ranking['hierarchy_id'] == $hierachy_id)
                                        {
                                            $ranker = $other_ranking['ranker_name'];
                                            $rank   = $other_ranking['rank'];
                                            break;
                                        }
                                    }

                                    $files[$index]['ranking_' . $hierachy_id] = empty($rank) || $rank == -1 ? Text::_('COM_EMUNDUS_RANKING_NOT_RANKED') : $rank;
                                    $files[$index]['ranker_' . $hierachy_id]  = $ranker ?? '';

                                    if (!empty($hierarchy_data['form_id'])) {
                                        $this->addHierarchyFormDataToFiles($files, $hierarchy_data['form_id']);
                                    }
                                }
                            }

                            foreach ($ordered_columns_unique as $column)
                            {

                                switch ($column['id'])
                                {
                                    case 'status':
                                        $file_data[] = $states[$file['status']]['value'];
                                        break;
                                    case 'package':
                                        $file_data[] = $package_label;
                                        break;
                                    case 'ranker':
                                        $file_data[] = $user_name;
                                        break;
                                    case 'rank':
                                        $file_data[] = empty($file[$column['id']]) || $file[$column['id']] == -1 ? Text::_('COM_EMUNDUS_RANKING_NOT_RANKED') : $file[$column['id']];
                                        break;
                                    default:
                                        $file_data[] = $file[$column['id']];
                                        break;
                                }
                            }

                            $export_array[] = $file_data;
                        }
                    }
                }

                if (!empty($export_array))
                {
                    $header = array_map(function ($column) {
                        return Text::_($column['label']);
                    }, $ordered_columns_unique);

                    $this->dispatchEvent('onBeforeExportRanking', ['header' => &$header, 'lines' => &$export_array, 'fnums' => $fnums, 'ccids' => $ccids, 'columns' => $ordered_columns]);
                    $lines = array_merge([$header], $export_array);
                }
            }
        }

        return $lines;
    }


	public function getHierarchyFormElements(int $form_id, $return_type = 'object'): array
	{
		$elements = [];

		if (!empty($form_id)) {
			$query = $this->db->getQuery(true);

			$query->clear()
				->select('jfe.*, jfl.db_table_name')
				->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
				->leftJoin($this->db->quoteName('#__fabrik_lists', 'jfl') . ' ON jfl.form_id = jffg.form_id')
				->where('jffg.form_id = ' . $this->db->quote($form_id))
				->andWhere('jfe.published = 1')
				->andWhere('jfe.name NOT IN (' . implode(',', $this->db->quote(['id', 'fnum', 'user', 'campaign_id'])) . ')');

			$this->db->setQuery($query);

			if ($return_type == 'array') {
				$elements = $this->db->loadAssocList();
			} else {
				$elements = $this->db->loadObjectList();
			}
		}

		return $elements;
	}

	public function addHierarchyFormDataToFiles(array &$files, int $form_id, array &$ordered_columns_keys = [], array &$ordered_columns = []): void
	{
		if (!empty($files) && !empty($form_id)) {
			if (!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/files.php');
			}
			$m_files = new EmundusModelFiles();
			$form_elements = $this->getHierarchyFormElements($form_id);

			if (!empty($form_elements)) {
				$fnums = array_map(function ($file) {return $file['fnum'];}, $files);
				foreach($form_elements as $element) {
					$ordered_columns_keys[] = $form_id . '-' . $element->name;
					$ordered_columns[] = [
						'id' => $form_id . '-' . $element->name,
						'label' => Text::_($element->name)
					];

					$values_by_fnum = $m_files->getFabrikValue($fnums, $element->db_table_name, $element->name);

					foreach ($files as $index => $file) {
						$files[$index][$form_id . '-' . $element->name] = '';

						if (isset($values_by_fnum[$file['fnum']]['val'])) {
							$files[$index][$form_id . '-' . $element->name] = $values_by_fnum[$file['fnum']]['val'];
						}
					}
				}
			}
		}
	}

	/**
	 * Returns a link to a csv file containing the ranking of the files
	 * @param $user_id, the user id
	 * @param $package_ids, the package ids to export
	 * @param $hierachy_ids, the additional hieararchies we want to export
	 * @param $columns, the additional columns we want to export
	 * @return void
	 */
	public function exportRanking($user_id, $package_ids, $hierachy_ids, $ordered_columns): string
	{
		$export_link = '';

		$lines = $this->prepareDataToExport($user_id, $package_ids, $hierachy_ids, $ordered_columns);

		if (!empty($lines)) {
			$today  = date("MdYHis");
			$name   = md5($today.rand(0,10));
			$name   = 'classement-' . $name.'.csv';
			$path = JPATH_SITE . '/tmp/' . $name;

			if (!$csv_file = fopen($path, 'w+')) {
				throw new Exception(Text::_('COM_EMUNDUS_EXPORTS_ERROR_CANNOT_CREATE_CSV_FILE'));
			} else {
				foreach ($lines as $line) {
					fputcsv($csv_file, $line, ';');
				}
				fclose($csv_file);
				$export_link = JUri::root() . 'tmp/' . $name;
			}
		}

		return $export_link;
	}

	public function getAllRankingsSuperAdmin(int $hierarchy_id, int $page = 0, int $limit = 0,  array $programs_filter = [], array $campaigns_filter = [], array $status_filter = [], string $search_file_or_user = '', string $search_ranker = '', string $order_by_column = 'ecc.fnum', string $order_direction = 'ASC', $fnums = []): array
	{
		$all_rankings = [];

		$query = $this->db->getQuery(true);
		$query->select('ecc.id as ccid, ecc.fnum, ecc.applicant_id, u.name as applicant_name, ecc.status, ess.value as status_label, esc.id as campaign_id, esc.label as campaign_label, esp.id as program_id, esp.label as program_label, er.id as rank_row_id, er.rank, er.user_id as ranker_id, ranker_user.name as ranker_name')
			->from($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'))
			->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc') . ' ON ' . $this->db->quoteName('esc.id') . ' = ' . $this->db->quoteName('ecc.campaign_id'))
			->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esp.code') . ' = ' . $this->db->quoteName('esc.training'))
			->leftJoin($this->db->quoteName('#__emundus_setup_status', 'ess') . ' ON ' . $this->db->quoteName('ess.step') . ' = ' . $this->db->quoteName('ecc.status'))
			->leftJoin($this->db->quoteName('#__users', 'u') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('ecc.applicant_id'));

		if (empty($hierarchy_id)) {
			$query->leftJoin($this->db->quoteName('#__emundus_ranking', 'er') . ' ON ' . $this->db->quoteName('er.ccid') . ' = ' . $this->db->quoteName('ecc.id'));
		} else {
			$query->leftJoin($this->db->quoteName('#__emundus_ranking', 'er') . ' ON ' . $this->db->quoteName('er.ccid') . ' = ' . $this->db->quoteName('ecc.id') . ' AND ' . $this->db->quoteName('er.hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id));
		}
		$query->leftJoin($this->db->quoteName('#__users', 'ranker_user') . ' ON ' . $this->db->quoteName('ranker_user.id') . ' = ' . $this->db->quoteName('er.user_id'));

		$query->where('ecc.published = 1')
			->andWhere('esc.published = 1')
			->andWhere('esp.published = 1');


		if (!empty($programs_filter)) {
			$query->andWhere($this->db->quoteName('esp.id') . ' IN  (' . implode(',', $programs_filter) . ')');
		}

		if (!empty($campaigns_filter)) {
			$query->andWhere($this->db->quoteName('ecc.campaign_id') . ' IN (' . implode(',', $campaigns_filter) . ')');
		}

		if (!empty($status_filter)) {
			$query->andWhere($this->db->quoteName('ecc.status') . ' IN (' . implode(',', $status_filter) . ')');
		}

		if (!empty($search_ranker)) {
			$query->andWhere($this->db->quoteName('ranker_user.name') . ' LIKE ' . $this->db->quote('%' . $search_ranker . '%'));
		}

		if (!empty($search_ranker)) {
			$query->andWhere($this->db->quoteName('ranker_user.name') . ' LIKE ' . $this->db->quote('%' . $search_ranker . '%'));
		}

		if (!empty($search_file_or_user)) {
			$query->andWhere('(' . $this->db->quoteName('ecc.fnum') . ' LIKE ' . $this->db->quote('%' . $search_file_or_user . '%') . ' OR ' . $this->db->quoteName('u.name') . ' LIKE ' . $this->db->quote('%' . $search_file_or_user . '%') . ')');
		}

		if (!empty($fnums)) {
			$query->andWhere($this->db->quoteName('ecc.fnum') . ' IN (' . implode(',', $this->db->quote($fnums)) . ')');
		}

		if (!empty($order_by_column)) {
			$allowed_order_by = [
				'ecc.fnum',
				'u.name',
				'ess.value',
				'esp.label',
				'esc.label',
				'er.id',
				'er.rank',
				'er.user_id'
			];

			if (in_array($order_by_column, $allowed_order_by)) {
				$query->order($this->db->quoteName($order_by_column) . ' ' . $order_direction);
			} else {
				$query->order('ecc.fnum ASC');
			}
		}

		try {
			if (!empty($limit)) {
				$this->db->setQuery($query, $page, $limit);
			} else {
				$this->db->setQuery($query);
			}

			$all_rankings = $this->db->loadAssocList();

			require_once(JPATH_ROOT . '/components/com_emundus/helpers/users.php');
			foreach ($all_rankings as $key => $ranking) {
				$all_rankings[$key]['ranker'] = [
					'id' => $ranking['ranker_id'],
					'name' => $ranking['ranker_name']
				];
			}
		} catch (Exception $e) {
			Log::add('getAllRankingsSuperAdmin ' . $e->getMessage(), Log::ERROR, 'com_emundus.ranking.php');
		}

		return $all_rankings;
	}

	public function rawUpdateRank($ranking_row_id, $new_rank, $ccid, $hierarchy_id, $user_id): bool
	{
		$updated = false;

		if (!empty($new_rank) && !empty($ccid) && !empty($hierarchy_id)) {
			$query = $this->db->getQuery(true);

			if (empty($ranking_row_id)) {
				// insert
				$query->insert($this->db->quoteName('#__emundus_ranking'))
					->columns($this->db->quoteName('ccid') . ', ' . $this->db->quoteName('rank') . ', ' . $this->db->quoteName('hierarchy_id') . ', ' . $this->db->quoteName('user_id'))
					->values($this->db->quote($ccid) . ', ' . $this->db->quote($new_rank) . ', ' . $this->db->quote($hierarchy_id) . ', ' . $this->db->quote($user_id));

				$this->db->setQuery($query);
				$updated = $this->db->execute();

			} else {
				// update
				$query->update($this->db->quoteName('#__emundus_ranking'))
					->set($this->db->quoteName('rank') . ' = ' . $this->db->quote($new_rank))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($ranking_row_id))
					->andWhere($this->db->quoteName('hierarchy_id') . ' = ' . $this->db->quote($hierarchy_id));

				$this->db->setQuery($query);
				$updated = $this->db->execute();
			}

			if ($updated) {
				$fnum = EmundusHelperFiles::getFnumFromId($ccid);
				$action_id = $this->logger->getActionId('ranking');
				$user_to = EmundusHelperFiles::getApplicantIdFromFileId($ccid);
				Log::add('Ranking row ' . $ranking_row_id . ' updated to rank ' . $new_rank, Log::INFO, 'com_emundus.ranking.php');
				$this->logger->log($user_id, $user_to, $fnum, $action_id, 'u', 'COM_EMUNDUS_RANKING_UPDATE_RANKING', json_encode(['new_rank' => $new_rank, 'hierarchy' => $hierarchy_id, 'user_id' => $user_id, 'context' => 'raw_update']));
			} else {
				Log::add('Failed to update ranking row ' . $ranking_row_id . ' to rank ' . $new_rank, Log::WARNING, 'com_emundus.ranking.php');
			}
		}

		return $updated;
	}

	public function rawUpdateRanker($row_id, $new_ranker, $current_user_id)
	{
		$updated = false;

		if (!empty($row_id) && !empty($new_ranker)) {
			$query = $this->db->getQuery(true);
			$query->update($this->db->quoteName('#__emundus_ranking'))
				->set($this->db->quoteName('user_id') . ' = ' . $this->db->quote($new_ranker))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($row_id));

			$this->db->setQuery($query);
			$updated = $this->db->execute();

			if ($updated) {
				$query->clear()
					->select('ccid')
					->from($this->db->quoteName('#__emundus_ranking'))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($row_id));

				$this->db->setQuery($query);
				$ccid = $this->db->loadResult();

				$fnum = EmundusHelperFiles::getFnumFromId($ccid);
				$action_id = $this->logger->getActionId('ranking');
				$user_to = EmundusHelperFiles::getApplicantIdFromFileId($ccid);
				$this->logger->log($current_user_id, $user_to, $fnum, $action_id, 'u', 'COM_EMUNDUS_RANKING_UPDATE_RANKER', json_encode(['new_ranker' => $new_ranker, 'user_id' => $current_user_id]));
				Log::add('Ranker for row ' . $row_id . ' updated to ranker ' . $new_ranker, Log::INFO, 'com_emundus.ranking.php');
			} else {
				Log::add('Failed to update ranker for row ' . $row_id . ' to ranker ' . $new_ranker, Log::WARNING, 'com_emundus.ranking.php');
			}
		}

		return $updated;
	}

	public function getRankersByHierarchy($hierarchy_ids = [])
	{
		$rankers = [];

		$hierarchies = $this->getHierarchies($hierarchy_ids);

		if (!empty($hierarchies)) {
			$query = $this->db->getQuery(true);
			foreach($hierarchies as $hierarchy) {

				$query->clear()
					->select('DISTINCT u.id, u.name')
					->from($this->db->quoteName('#__users', 'u'))
					->leftJoin($this->db->quoteName('#__emundus_users', 'eu') . ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('eu.user_id'))
					->leftJoin($this->db->quoteName('#__emundus_users_profiles', 'eup') . ' ON ' . $this->db->quoteName('eup.user_id') . ' = ' . $this->db->quoteName('eu.user_id'));

				if (!empty($hierarchy['profiles'])) {
					$query->where('(' . $this->db->quoteName('eu.profile') . ' IN (' . implode(',', $hierarchy['profiles']) . ') OR ' . $this->db->quoteName('eup.profile_id') . ' IN (' . implode(',', $hierarchy['profiles']) .  '))');
				} else {
					$sub_query = $this->db->getQuery(true);
					$sub_query->select('id')
						->from($this->db->quoteName('#__emundus_setup_profiles'))
						->where($this->db->quoteName('published') . ' = 0');

					$this->db->setQuery($sub_query);
					$profiles = $this->db->loadColumn();

					if (!empty($profiles))  {
						$query->where('(' . $this->db->quoteName('eu.profile') . ' IN (' . implode(',', $profiles) . ') OR ' . $this->db->quoteName('eup.profile_id') . ' IN (' . implode(',', $profiles) .  '))');
					}
				}

				$this->db->setQuery($query);
				$rankers[$hierarchy['id']] = $this->db->loadAssocList();
			}
		}

		return $rankers;
	}
}