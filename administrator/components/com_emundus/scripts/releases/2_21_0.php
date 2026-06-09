<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\PublicAccess\PublicApplicationGuard;

class Release2_21_0Installer extends ReleaseInstaller
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
			// Seed the public-application rate-limit windows on the public-session
			// addon with the guard's defaults. Only missing keys are written, so an
			// admin who already tuned a window keeps their value. Defaults come from
			// the guard constants, which stay the single source of truth.
			$addonRepository    = new AddonRepository();
			$publicSessionAddon = $addonRepository->getByName(AddonEnum::PUBLIC_SESSION->value);

			if (!empty($publicSessionAddon))
			{
				$defaults = [
					'rate_limit_cooldown'             => PublicApplicationGuard::DEFAULT_RATE_LIMIT_WINDOW,
					'rate_limit_per_minute'           => PublicApplicationGuard::DEFAULT_RATE_LIMIT_GLOBAL_PER_MINUTE,
					'rate_limit_per_hour'             => PublicApplicationGuard::DEFAULT_RATE_LIMIT_GLOBAL_PER_HOUR,
					'rate_limit_per_day'              => PublicApplicationGuard::DEFAULT_RATE_LIMIT_GLOBAL_PER_DAY,
					'rate_limit_per_campaign_per_day' => PublicApplicationGuard::DEFAULT_RATE_LIMIT_PER_CAMPAIGN_PER_DAY,
				];

				$params  = $publicSessionAddon->getParams();
				$changed = false;
				foreach ($defaults as $key => $value)
				{
					if (!array_key_exists($key, $params))
					{
						$params[$key] = $value;
						$changed      = true;
					}
				}

				if ($changed)
				{
					$publicSessionAddon->setParams($params);
					$this->tasks[] = $addonRepository->flush($publicSessionAddon);
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
