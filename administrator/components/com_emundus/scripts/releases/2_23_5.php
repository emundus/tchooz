<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_23_5Installer extends ReleaseInstaller
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
			$this->docaposteRetrieveProofDocument();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Docaposte proof document retrieval became optional, keep it enabled on already configured integrations.
	 *
	 * @throws \Exception
	 */
	private function docaposteRetrieveProofDocument(): void
	{
		$synchronizerRepository = new SynchronizerRepository();
		$docaposteSynchronizer  = $synchronizerRepository->getByType('docaposte');

		if (empty($docaposteSynchronizer))
		{
			return;
		}

		$config = $docaposteSynchronizer->getConfig();

		if (isset($config['configuration']['retrieveProofDocument']) && $config['configuration']['retrieveProofDocument'] !== '')
		{
			return;
		}

		$config['configuration']['retrieveProofDocument'] = 1;
		$docaposteSynchronizer->setConfig($config);

		$this->tasks[] = $synchronizerRepository->flush($docaposteSynchronizer);
	}
}