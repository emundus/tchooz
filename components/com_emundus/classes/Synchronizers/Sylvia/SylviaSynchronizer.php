<?php

namespace Tchooz\Synchronizers\Sylvia;

use Joomla\CMS\Log\Log;
use Tchooz\api\Api;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Entities\Mapping\SynchronizerMappingObjectDefinition;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Enums\Api\ApiMethodEnum;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\Configurations\SylviaIntegrationConfiguration;
use Tchooz\Services\Mapping\ApiMapDataInterface;
use Tchooz\Services\Mapping\MappingService;

/**
 * Synchronizer for the "Sylvia" student-identification service.
 *
 * The PLAP sends student identity (nom, prénom, date de naissance) to Sylvia and
 * receives back a registration number ("no_immat") when the student is found.
 * Field mapping (which eMundus field feeds nom/prenom/date_naissance, and where the
 * returned number/status is written back) is handled by the Mapping + Automation layers.
 */
class SylviaSynchronizer extends Api
{
	public function __construct()
	{
		parent::__construct();

		Log::addLogger(['text_file' => 'com_emundus.sylvia.php'], Log::ALL, ['com_emundus.sylvia']);

		try
		{
			$auth = $this->getAuthenticationInfos();

			$this->setBaseUrl($auth['base_url']);
			$headers = array(
				'Content-Type' => 'application/json',
				'api-key'      => $auth['api_key'],
				'Accept'       => 'application/json'
			);
			$this->setHeaders($headers);
			$this->setClient();
			$this->setAuth($auth['api_key']);
		}
		catch (\Exception $e)
		{
			Log::add('Error on Sylvia api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sylvia');
		}
	}

	private function getAuthenticationInfos(): array
	{
		$auth = [];

		$syncRepository = new SynchronizerRepository();
		$syncEntity     = $syncRepository->getByType('sylvia');
		$params         = !empty($syncEntity) ? ($syncEntity->getConfig() ?? []) : [];

		$auth['api_key'] = !empty($params['authentication']['api_key']) ? \EmundusHelperFabrik::decryptDatas($params['authentication']['api_key']) : '';

		$baseUrl = !empty($params['configuration']['base_url']) ? rtrim($params['configuration']['base_url'], '/') : '';
		$apiPath = !empty($params['configuration']['api_path']) ? trim($params['configuration']['api_path'], '/') : '';

		$auth['base_url'] = !empty($apiPath) ? $baseUrl . '/' . $apiPath : $baseUrl;

		return $auth;
	}

	/**
	 * Directly ask Sylvia to identify a student from their identity fields.
	 * Used by the daily task plugin (no mapping involved).
	 *
	 * @param   string       $lastname   Nom
	 * @param   string       $firstname  Prénom
	 * @param   string|null  $birthDate  Date de naissance, formatted as expected by Sylvia (DD.MM.YYYY)
	 *
	 * @return array{found: bool, no_immat: ?string, transmissible_expert: bool, raw: mixed}
	 */
	public function identifyStudent(string $lastname, string $firstname, ?string $birthDate): array
	{
		$result = ['found' => false, 'no_immat' => null, 'transmissible_expert' => false, 'homonyms' => [], 'raw' => null];

		$payload = [
			'nom'            => $lastname,
			'prenom'         => $firstname,
			'date_naissance' => $birthDate,
		];

		$response = $this->post(
			$this->getBaseUrl() . '/api-students/student-identification',
			json_encode($payload),
			['Content-Type' => 'application/json', 'Accept' => 'application/json']
		);

		if (empty($response) || !in_array($response['status'], [200, 201]))
		{
			Log::add('Error on Sylvia student-identification request : ' . json_encode($response), Log::ERROR, 'com_emundus.sylvia');

			return $result;
		}

		$data = $response['data'];

		$result['raw'] = $data;

		// The API returns a single object for a unique match, or an array of objects when several homonyms are found.
		$candidates = is_array($data) ? array_values($data) : [$data];

		if (count($candidates) > 1)
		{
			// Several homonyms: no unique identification possible, expose the candidates for manual resolution.
			$result['homonyms'] = $candidates;

			return $result;
		}

		$student = $candidates[0] ?? null;

		if (empty($student))
		{
			return $result;
		}

		$result['no_immat']             = !empty($student->no_immat) ? (string) $student->no_immat : null;
		$result['transmissible_expert'] = !empty($student->transmissible_expert);
		$result['found']                = !empty($result['no_immat']);

		return $result;
	}

	/**
	 * The status "steps" configured under the "Statuts à synchroniser" parameter.
	 *
	 * @return int[]
	 */
	public function getStatusesToSync(): array
	{
		$syncEntity = (new SynchronizerRepository())->getByType('sylvia');
		$config     = !empty($syncEntity) ? ($syncEntity->getConfig() ?? []) : [];
		$statuses   = $config['configuration'][SylviaIntegrationConfiguration::STATUSES_TO_SYNC_PARAMETER] ?? [];

		if (!is_array($statuses))
		{
			$statuses = ($statuses === null || $statuses === '') ? [] : [$statuses];
		}

		$statusesValues = [];
		foreach ($statuses as $status)
		{
			$statusesValues[] = $status['value'];
		}

		return array_values(array_map('intval', $statusesValues));
	}

	public function getTagNotFound(): int
	{
		$syncEntity = (new SynchronizerRepository())->getByType('sylvia');
		$config     = !empty($syncEntity) ? ($syncEntity->getConfig() ?? []) : [];
		$tag   = $config['configuration'][SylviaIntegrationConfiguration::TAG_NOT_FOUND] ?? [];

		if(empty($tag))
		{
			return 0;
		}

		return (int)$tag;
	}

	public function getTagDoublon(): int
	{
		$syncEntity = (new SynchronizerRepository())->getByType('sylvia');
		$config     = !empty($syncEntity) ? ($syncEntity->getConfig() ?? []) : [];
		$tag   = $config['configuration'][SylviaIntegrationConfiguration::TAG_DUPLICATE] ?? [];

		if(empty($tag))
		{
			return 0;
		}

		return (int)$tag;
	}
}
