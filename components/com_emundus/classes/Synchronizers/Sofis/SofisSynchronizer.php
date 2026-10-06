<?php

namespace Tchooz\Synchronizers\Sofis;

use Joomla\CMS\Language\Text;
use Tchooz\api\Api;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Sofis\SofisAuthenticator;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Services\Sofis\SofisLogger;

/**
 * Sofis (Microsoft Dynamics 365 F&O) transport: authenticates through the EntraId OAuth2 token
 * proxy (via SofisAuthenticator) and carries requests (GET/POST/PATCH) to the Sofis APIs exposed
 * by the MARIO API gateway.
 *
 * Like every mapping transport it is ignorant of business objects and routes; the Sofis mapping
 * objects (to be added once the functional Dynamics mapping is provided) will carry that knowledge.
 */
class SofisSynchronizer extends Api implements MappingTransportInterface
{
	public function __construct()
	{
		parent::__construct();

		SofisLogger::register();

		$syncEntity = (new SynchronizerRepository())->getByType('sofis');

		if (empty($syncEntity))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SOFIS_MISSING_CONFIGURATION'));
		}

		$authConfig = $syncEntity->getConfig()['authentication'] ?? [];

		// No fallback on `resource`: it is the token audience, calling it directly would bypass the MARIO gateway.
		$baseUrl = rtrim($authConfig['base_url'] ?? '', '/');

		if (empty($baseUrl))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_SOFIS_MISSING_CONFIGURATION'));
		}

		// Auth failures propagate (no silent catch) so the real cause surfaces to the action log.
		$token = SofisAuthenticator::fromSynchronizer($syncEntity)->getAccessToken();

		$this->setBaseUrl($baseUrl);
		$this->setHeaders([
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $token,
			'Accept'        => 'application/json',
		]);
		$this->setClient();
		$this->setAuth($token);
	}
}
