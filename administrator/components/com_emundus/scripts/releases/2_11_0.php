<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;

class Release2_11_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$this->initShareFilters();

			$this->tasks[] = EmundusHelperUpdate::addColumn('jos_emundus_sign_requests', 'ordered', 'TINYINT', 3, 1, 0);

			$result['status']  = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function initShareFilters(): void
	{
		$columns      = [
			[
				'name'   => 'filter_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name'   => 'user_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 1,
			],
			[
				'name'   => 'group_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 1,
			],
			[
				'name'   => 'shared_by',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name' => 'shared_date',
				'type' => 'DATETIME',
				'null' => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'emundus_filters_assoc_filter_fk',
				'from_column'    => 'filter_id',
				'ref_table'      => '#__emundus_filters',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'emundus_filters_assoc_user_fk',
				'from_column'    => 'user_id',
				'ref_table'      => '#__users',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'emundus_filters_assoc_group_fk',
				'from_column'    => 'group_id',
				'ref_table'      => '#__emundus_setup_groups',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true

			],
			[
				'name'           => 'emundus_filters_assoc_shared_by_fk',
				'from_column'    => 'shared_by',
				'ref_table'      => '#__users',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => false
			]
		];
		$created      = EmundusHelperUpdate::createTable('jos_emundus_filters_assoc', $columns, $foreign_keys);
		$this->tasks[]      = $created['status'];

		$columns      = [
			[
				'name'   => 'filter_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			],
			[
				'name'   => 'user_id',
				'type'   => 'INT',
				'length' => 11,
				'null'   => 0,
			]
		];
		$foreign_keys = [
			[
				'name'           => 'emundus_filters_favorites_filter_fk',
				'from_column'    => 'filter_id',
				'ref_table'      => '#__emundus_filters',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			],
			[
				'name'           => 'emundus_filters_favorites_user_fk',
				'from_column'    => 'user_id',
				'ref_table'      => '#__users',
				'ref_column'     => 'id',
				'update_cascade' => true,
				'delete_cascade' => true
			]
		];
		$created      = EmundusHelperUpdate::createTable('jos_emundus_filters_favorites', $columns, $foreign_keys);
		$this->tasks[]      = $created['status'];

		$created = EmundusHelperUpdate::createTable('jos_emundus_filters_user_default_filter',
			[
				[
					'name'   => 'filter_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0,
				],
				[
					'name'   => 'user_id',
					'type'   => 'INT',
					'length' => 11,
					'null'   => 0,
				]
			],
			[
				[
					'name'           => 'emundus_filters_user_default_filter_filter_fk',
					'from_column'    => 'filter_id',
					'ref_table'      => '#__emundus_filters',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				],
				[
					'name'           => 'emundus_filters_user_default_filter_user_fk',
					'from_column'    => 'user_id',
					'ref_table'      => '#__users',
					'ref_column'     => 'id',
					'update_cascade' => true,
					'delete_cascade' => true
				]
			]);

		$this->tasks[] = $created['status'];

		$this->tasks[] = !empty(EmundusHelperUpdate::createNewAction('share_filters', ['multi' => 0, 'c' => 1, 'r' => 1, 'u' => 1, 'd' => 0], 'COM_EMUNDUS_SHARE_FILTERS'));
	}
}