<?php

namespace Tchooz\Factories\NumericSign;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\NumericSign\Request;
use Tchooz\Enums\NumericSign\SignConnectorsEnum;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\NumericSign\YousignRequestsRepository;
use Tchooz\Services\NumericSign\YousignService;
use Tchooz\Synchronizers\NumericSign\DocaposteSynchronizer;
use Tchooz\Synchronizers\NumericSign\DocuSignSynchronizer;
use Tchooz\Synchronizers\NumericSign\YousignSynchronizer;

class NumericSignServiceFactory
{
	public static function fromRequest(Request $request): object
	{
		$serviceInstance = null;

		switch($request->getConnector())
		{
			case SignConnectorsEnum::DOCUSIGN:
				$serviceInstance = new DocuSignSynchronizer();
				break;
			case SignConnectorsEnum::YOUSIGN:
				$db = Factory::getContainer()->get('DatabaseDriver');
				$request_repository         = new RequestRepository($db);
				$request_signers_repository = new RequestSignersRepository($db);
				$yousign_repository         = new YousignRequestsRepository($db);
				$yousign_synchronizer       = new YousignSynchronizer();
				$m_files                    = new \EmundusModelFiles();
				$m_application              = new \EmundusModelApplication();
				$em_config = ComponentHelper::getParams('com_emundus');
				$automated_user_id = $em_config->get('automated_task_user', 1);
				$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($automated_user_id);

				$serviceInstance = new YousignService(
					$yousign_synchronizer,
					$yousign_repository,
					$request_repository,
					$request_signers_repository,
					$m_files,
					$m_application,
					$user,
				);

				break;
			case SignConnectorsEnum::DOCAPOSTE:
				$serviceInstance = new DocaposteSynchronizer();
				break;
			// Ajouter d'autres cas pour d'autres services de signature numérique
			default:
				throw new \Exception('Unsupported Numeric Sign service: ' . $request->getConnector()->value);
		}

		return $serviceInstance;
	}
}