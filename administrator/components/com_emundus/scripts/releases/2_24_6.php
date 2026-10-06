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

class Release2_24_6Installer extends ReleaseInstaller
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
			$synchronizerRepository = new SynchronizerRepository();
			$ixparapheurSynchronizer = $synchronizerRepository->getByType('ixparapheur');
			if (empty($ixparapheurSynchronizer))
			{
				$ixparapheurSynchronizer = new SynchronizerEntity(
					0,
					'ixparapheur',
					'IxParapheur',
					'Signature électronique via IxParapheur',
					[],
					[
						'authentication' => [
							'base_url'     => '',
							'token' => '',
						],
						'configuration' => [
							'nature' => '',
							'default_signer_email' => '',
							'default_attachment_id' => ''
						]
					],
					false,
					false,
					'ixparapheur.png'
				);

				$this->tasks[] = $synchronizerRepository->flush($ixparapheurSynchronizer);
			}

			$result['status'] = !in_array(false, $this->tasks);

			if (!$result['status'])
			{
				$result['message'] = 'Failed to register the transaction email tags.';
			}
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
