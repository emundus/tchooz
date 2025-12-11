<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;


use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Entities\Addons\AddonValue;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Workflow\StepTypeRepository;

class Release2_12_1Installer extends ReleaseInstaller
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
			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_fabrik_element_action', 'action', null, 'plugin', 1, 'fabrik_element');

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