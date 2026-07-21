<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use scripts\ReleaseInstaller;
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_22_2Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$longDescriptionProgrammeColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_programmes', 'long_description', 'TEXT');
			$this->tasks[] = $longDescriptionProgrammeColumn['status'];
			$mustOpenRightsProgrammeColumn = \EmundusHelperUpdate::addColumn('jos_emundus_setup_programmes', 'must_open_rights', 'INT', 11);
			$this->tasks[] = $mustOpenRightsProgrammeColumn['status'];

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
