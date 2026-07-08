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
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

class Release2_21_4Installer extends ReleaseInstaller
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
			// Migrate Yousign config JSON to grouped structure (authentication / configuration)
			$synchronizerRepository = new SynchronizerRepository();
			$yousignSynchronizer = $synchronizerRepository->getByType('yousign');

			if (!empty($yousignSynchronizer))
			{
				$config = $yousignSynchronizer->getConfig();

				if (!empty($config) && !(isset($config['authentication']) && isset($config['configuration'])))
				{
					$existingAuth   = is_array($config['authentication'] ?? null) ? $config['authentication'] : [];
					$existingConfig = is_array($config['configuration'] ?? null) ? $config['configuration'] : [];

					$config['authentication'] = array_merge([
						'mode'           => $config['mode'] ?? 1,
						'create_webhook' => $config['create_webhook'] ?? 0,
						'token'          => $existingAuth['token'] ?? $config['token'] ?? '',
					], $existingAuth);
					$config['configuration'] = array_merge([
						'expiration_date'               => $config['expiration_date'] ?? null,
						'signature_level'               => $config['signature_level'] ?? 'electronic_signature',
						'signature_authentication_mode' => $config['signature_authentication_mode'] ?? 'otp_email',
						'request_name'                  => $config['request_name'] ?? '',
						'signature_display_mode'        => $config['signature_display_mode'] ?? 'minimal',
					], $existingConfig);

					$yousignSynchronizer->setConfig($config);
					$this->tasks[] = $synchronizerRepository->flush($yousignSynchronizer);
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
