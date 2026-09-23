<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_25_0Installer extends ReleaseInstaller
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
			$repository = new SynchronizerRepository();
			$sofis      = $repository->getByType('sofis');

			if (empty($sofis))
			{
				$config = [
					'authentication' => [
						'idp_token_url'       => '',
						'idp_client_id'       => '',
						'idp_secret'          => '',
						'token_endpoint'      => '',
						'entra_client_id'     => '',
						'scope'               => 'default',
						'resource'            => '',
					],
				];

				$sofis = new SynchronizerEntity(
					0,
					'sofis',
					'Sofis',
					'Interconnexion Sofis (Microsoft Dynamics 365)',
					[],
					$config,
					false,
					false,
					'sofis.svg'
				);

				$this->tasks[] = $repository->flush($sofis);

			}

			$this->tasks[] = \EmundusHelperUpdate::alterColumn('jos_emundus_external_reference', 'intern_id', 'VARCHAR', 255, 0);

			$result['status'] = !in_array(false, $this->tasks, true);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}