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
use Tchooz\Enums\Synchronizer\SynchronizerContextEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_12_0Installer extends ReleaseInstaller
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
			$synchronizerRepository = new SynchronizerRepository();
			$docusignSynchronizer = $synchronizerRepository->getByType('docusign');
			if (empty($docusignSynchronizer))
			{
				$docusignSynchronizer = new SynchronizerEntity(
					0,
					'docusign',
					'Docusign',
					'Signature électronique via Docusign',
					[],
					[
						'authentication' => [
							'user_guid'     => '',
							'account_id' => '',
							'secret_key' => '',
							'rsa_private_key' => '',
							'integration_key' => ''
						],
						'configuration' => [
							'mode' => 'TEST',
						]
					],
					false,
					false,
					'docusign.svg',
					null,
					SynchronizerContextEnum::NUMERIC_SIGN
				);
			}

			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_sign_requests', 'subject', 'VARCHAR', 255, 1, '')['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_sign_requests_signers', 'order', 'INT', 11, 1, null)['status'];
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_sign_requests_signers', 'anchor', 'VARCHAR', 255, 1, null)['status'];

			$this->tasks[] = $synchronizerRepository->flush($docusignSynchronizer);

			$manifest = '{"name":"plg_emundus_signature_requests","type":"plugin","creationDate":"April 2025","author":"eMundus","copyright":"","authorEmail":"","authorUrl":"","version":"1.0.0","description":"Plugin to integrate Signature Requests Management on some events","group":"","changelogurl":"","namespace":"Joomla\\Plugin\\Emundus\\SignatureRequests","filename":"signature_requests"}';
			$this->tasks[] = \EmundusHelperUpdate::installExtension(
				'plg_emundus_signature_requests',
				'signature_requests',
				$manifest,
				'plugin',
				1,
				'emundus'
			);

			$manifest = '{"name":"plg_task_signature_requests","type":"plugin","creationDate":"2025-04","author":"eMundus","copyright":"(C) 2025 Open Source Matters, Inc.","authorEmail":"dev@emundus.fr","authorUrl":"www.emundus.fr","version":"2.12.0","description":"","group":"","changelogurl":"","namespace":"Joomla\\Plugin\\Task\\SignatureRequests","filename":"signature_requests"}';
			$this->tasks[] = \EmundusHelperUpdate::installExtension(
				'plg_task_signature_requests',
				'signature_requests',
				$manifest,
				'plugin',
				1,
				'task'
			);

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