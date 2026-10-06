<?php

namespace Tchooz\Synchronizers\Hubspot;

use Joomla\CMS\Log\Log;
use Tchooz\api\Api;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Synchronizers\FileUploadInterface;
use Tchooz\Synchronizers\MappingTransportInterface;
use Tchooz\Synchronizers\ObjectSearchInterface;

/**
 * HubSpot transport: authenticates against the HubSpot API and carries requests (GET/POST/PATCH)
 * plus the object-search and file-upload capabilities. It is ignorant of business objects and
 * routes — that knowledge lives in the HubSpot mapping objects (ContactObject, DealObject) and the
 * orchestration in MappingExecutor.
 */
class HubspotSynchronizer extends Api implements MappingTransportInterface, ObjectSearchInterface, FileUploadInterface
{
	public function __construct()
	{
		parent::__construct();

		Log::addLogger(['text_file' => 'com_emundus.hubspot.php',], Log::ALL, ['com_emundus.hubspot']);

		try
		{
			$auth = $this->getAuthenticationInfos();

			$this->setBaseUrl($auth['base_url']);
			$headers = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $auth['token'],
				'Accept'        => 'application/json'
			);
			$this->setHeaders($headers);
			$this->setClient();
			$this->setAuth($auth['token']);
		}
		catch (\Exception $e)
		{
			Log::add('Error on Hubspot api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.hubspot');
		}
	}

	private function getAuthenticationInfos(): array
	{
		$auth = [];

		$syncRepository   = new SynchronizerRepository();
		$syncEntity       = $syncRepository->getByType('hubspot');
		$params           = $syncEntity->getConfig() ?? [];
		$auth['token']    = !empty($params['authentication']['token']) ? \EmundusHelperFabrik::decryptDatas($params['authentication']['token']) : '';
		$auth['base_url'] = 'https://api.hubapi.com';

		return $auth;
	}

	/**
	 * @param   UploadEntity  $upload
	 * @param   int           $synchronizerId
	 *
	 * @return string|null
	 */
	public function uploadFile(UploadEntity $upload, int $synchronizerId): ?string
	{
		$fileUrl = null;

		try {
			$filePath = $upload->getFileInternalPath();

			if (!file_exists($filePath)) {
				throw new \Exception('File not found: ' . $filePath);
			}

			$body = [
				[
					'name'     => 'file',
					'contents' => fopen($filePath, 'r'),
					'filename' => $upload->getFilename(),
				],
				[
					'name'     => 'fileName',
					'contents' => $upload->getFilename(),
				],
				[
					'name'     => 'options',
					'contents' => json_encode([
						'access' => 'PRIVATE'
					]),
				],
				[
					'name'     => 'folderPath',
					'contents' => $this->getFolderPath($upload->getFnum()),
				]
			];

			$response = $this->post(
				'files/v3/files',
				$body,
				['Accept' => 'application/json'],
				true
			);

			if (
				!empty($response)
				&& in_array($response['status'], [200, 201])
				&& !empty($response['data']->url)
			) {
				$fileUrl = $response['data']->url;

				$externalReference = new ExternalReferenceEntity(
					0,
					'jos_emundus_uploads.id',
					(string) $upload->getId(),
					(string) $response['data']->id,
					$synchronizerId,
					'files',
					'id'
				);
				$externalReferenceRepository = new ExternalReferenceRepository();

				if (!$externalReferenceRepository->flush($externalReference))
				{
					Log::add('Error saving external reference for uploaded file with ID : ' . $upload->getId() . ' Hubspot Id : ' . $response['data']->id, Log::ERROR, 'com_emundus.hubspot');
				}
			}
			else
			{
				Log::add('Error uploading file to Hubspot : ' . json_encode($response), Log::ERROR, 'com_emundus.hubspot');

				if (!empty($response['error'])) {
					$hubspotError = json_decode($response['error'], true);

					if (!empty($hubspotError['message'])) {
						throw new \Exception($hubspotError['message']);
					}
				}
			}
		} catch (\Exception $e) {
			Log::add(
				'Error uploading file to Hubspot : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.hubspot'
			);
		}

		return $fileUrl;
	}

	/**
	 * TODO: make dynamic folder path based on settings configuration
	 * @param   string  $fnum
	 *
	 * @return string
	 */
	public function getFolderPath(string $fnum): string
	{
		$folderPath = 'Emundus';

		if (!empty($fnum))
		{
			$applicationRepository = new ApplicationFileRepository();
			$applicationFile = $applicationRepository->getByFnum($fnum);

			$folderPath .= '/' . $applicationFile->getCampaign()->getLabel() . '/' . $applicationFile->getUser()->name . ' - ' . $applicationFile->getUser()->id;
		}

		return $folderPath;
	}

	/**
	 * @param   string  $searchedObject
	 * @param   array   $filters
	 * @param   int     $limit
	 * @param   array   $returnedProperties
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function searchObjects(string $searchedObject, array $filters, int $limit = 1, array $returnedProperties = ['hs_object_id']): array
	{
		$hubspotObjects = [];

		if (!empty($searchedObject))
		{
			$hubspotFilters = [];
			foreach ($filters as $attribute => $value)
			{
				$hubspotFilters[] = [
					'operator'     => 'EQ',
					'propertyName' => $attribute,
					'value'        => $value,
				];
			}

			$body = [
				'filterGroups' => [
					[
						'filters' => $hubspotFilters
					]
				],
				'limit' => $limit,
				'properties'   => $returnedProperties
			];

			$response = $this->post($this->getBaseUrl() . '/crm/v3/objects/' . $searchedObject . '/search', json_encode($body), ['Content-Type' => 'application/json', 'Accept' => 'application/json']);
			if (!empty($response) && in_array($response['status'], [200, 201]))
			{
				$hubspotObjects = $response['data']->results;
				Log::add('Found ' . count($hubspotObjects) . ' ' . $searchedObject . '(s) in Hubspot with filters ' . json_encode($hubspotFilters), Log::INFO, 'com_emundus.hubspot');
			}
			else
			{
				Log::add('Error searching contact in Hubspot : ' . json_encode($response) . ' with search ' . json_encode($hubspotFilters), Log::ERROR, 'com_emundus.hubspot');
				throw new \Exception('Error searching contact in Hubspot with filters ' . json_encode($hubspotFilters));
			}
		}

		return $hubspotObjects;
	}
}
