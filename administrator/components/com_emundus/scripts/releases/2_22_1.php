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

class Release2_22_1Installer extends ReleaseInstaller
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
			// Widen IP columns to 45 chars to store full IPv6 addresses.
			$ipColumns = [
				['table' => 'jos_emundus_vote', 'column' => 'ip'],
				['table' => 'jos_emundus_logs', 'column' => 'ip_from'],
				['table' => 'jos_securitycheckpro_sessions', 'column' => 'ip'],
			];

			foreach ($ipColumns as $ipColumn)
			{
				$currentColumn = $this->db->setQuery("SHOW COLUMNS FROM `{$ipColumn['table']}` LIKE '{$ipColumn['column']}';")->loadObject();

				if ($currentColumn && preg_match('/varchar\((\d+)\)/i', $currentColumn->Type, $matches) && (int) $matches[1] < 45)
				{
					if($ipColumn['table'] === 'jos_securitycheckpro_sessions')
					{
						$this->tasks[] = $this->db->setQuery("ALTER TABLE `{$ipColumn['table']}` MODIFY `{$ipColumn['column']}` VARCHAR(45) NOT NULL DEFAULT '';")->execute();
					}
					else {
						$this->tasks[] = $this->db->setQuery("ALTER TABLE `{$ipColumn['table']}` MODIFY `{$ipColumn['column']}` VARCHAR(45) NULL;")->execute();
					}
				}
			}

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
