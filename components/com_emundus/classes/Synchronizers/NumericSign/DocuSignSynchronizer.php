<?php

namespace Tchooz\Synchronizers\NumericSign;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\Envelope;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\EnvelopeEvent;
use DocuSign\eSign\Model\EventNotification;
use DocuSign\eSign\Model\RecipientEvent;
use DocuSign\eSign\Model\RecipientIdentityVerification;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use JUri;
use Tchooz\Entities\NumericSign\Request as Request;
use Tchooz\Enums\NumericSign\SignAuthenticationLevelEnum;
use Tchooz\Enums\NumericSign\SignStatusEnum;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Services\NumericSign\DocuSignJwtAuthenticator;

class DocuSignSynchronizer
{
	private array $auth = [];

	private ApiClient $client;

	private string $accountId;

	/**
	 * DocuSignSynchronizer constructor.
	 *
	 * @throws \Exception
	 */
	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.docusign.php',], Log::ALL, ['com_emundus.docusign']);

		try
		{
			$this->auth = $this->getAuthenticationInfos();
			$jwt = new DocuSignJwtAuthenticator([
				'integration_key' => $this->auth['integration_key'],
				'user_id'         => $this->auth['user_id'],
				'private_key'     => $this->auth['private_key'],
				'secret_key' 	  => $this->auth['secret_key'],
				'mode'            => $this->auth['mode'],
			]);

			$this->client = $jwt->getAuthenticatedClient();
		}
		catch (\Exception $e)
		{
			Log::add('Error on Yousign api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');

			throw new \Exception(Text::_('DOCUSIGN_SYNCHRONIZER_CONNECTION_ERROR'));
		}
	}

	/**
	 * @param   string  $docusignSignature
	 * @param   string  $content
	 *
	 * @return bool
	 */
	public function verifySignature(string $docusignSignature, string $content): bool
	{
		$verified = false;

		if (!empty($docusignSignature) && !empty($content))
		{
			// DocuSign signatures are base64-encoded SHA256 hashes of the document content
			$calculatedSignature = base64_encode(hash_hmac('sha256', $content, $this->auth['secret_key'], true));
			$verified = hash_equals($calculatedSignature, $docusignSignature);
		}

		return $verified;
	}

	/**
	 * @param   string  $accountId
	 *
	 * @return void
	 */
	public function setAccountId(string $accountId): void
	{
		$this->accountId = $accountId;
	}

	/**
	 * @return string
	 */
	public function getAccountId(): string
	{
		return $this->accountId;
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	private function getAuthenticationInfos(): array
	{
		$auth = [];

		$syncRepository = new SynchronizerRepository();
		$syncEntity = $syncRepository->getByType('docusign');

		$params                  = $syncEntity->getConfig() ?? [];
		$auth['integration_key'] = !empty($params['authentication']['integration_key'])
			? \EmundusHelperFabrik::decryptDatas($params['authentication']['integration_key'])
			: '';

		$auth['secret_key'] = !empty($params['authentication']['secret_key'])
			? \EmundusHelperFabrik::decryptDatas($params['authentication']['secret_key'])
			: '';

		// GUID utilisateur
		$auth['user_id'] = $params['authentication']['user_guid'];

		// ID numérique du compte
		$auth['account_id'] = $params['authentication']['account_id'];

		$private_key = !empty($params['authentication']['rsa_private_key'])
			? \EmundusHelperFabrik::decryptDatas($params['authentication']['rsa_private_key'])
			: '';

		$clean               = preg_replace('/-----.*?-----/', '', $private_key);         // remove markers
		$clean               = preg_replace('/\s+/', '', $clean);                   // remove whitespace
		$body                = chunk_split($clean, 64, "\n");                       // wrap
		$auth['private_key'] = "-----BEGIN RSA PRIVATE KEY-----\n" .
			$body .
			"-----END RSA PRIVATE KEY-----\n";
		$auth['mode']        = !empty($params['authentication']['mode'])
			? $params['authentication']['mode']
			: 'TEST';

		$this->setAccountId($auth['account_id']);

		return $auth;
	}

	/**
	 * @param   Request  $request
	 *
	 * @return EnvelopeDefinition
	 * @throws \Exception
	 */
	public function makeEnvelope(Request $request): EnvelopeDefinition
	{
		$envelopeDefinition = new EnvelopeDefinition([
			'email_subject' => $request->getSubject() ?? Text::_('COM_EMUNDUS_DOCUSIGN_EMAIL_SUBJECT'),
		]);

		$eventNotification = new EventNotification([
			'url' => JUri::base() . DS . 'index.php?option=com_emundus&controller=sign&task=docusigncallback&format=raw',
			'logging_enabled' => 'true',
		    'require_acknowledgment' => 'true',
		    'envelope_events' => [
		        new EnvelopeEvent([
		            'envelope_event_status_code' => 'completed', // envelope.completed
		            'include_documents' => true
		        ])
		    ],
		    'recipient_events' => [
		        new RecipientEvent([
		            'recipient_event_status_code' => 'completed', // recipient.completed
		            'include_documents' => true
		        ])
		    ]
		]);

		$envelopeDefinition->setEventNotification($eventNotification);

		$uploadRepository = new UploadRepository();
		$upload = $uploadRepository->getById($request->getUploadId());
		$content = $upload->getContent();

		// File to sign
		$document = new Document([
			'document_base64' => base64_encode($content),
			'name' => $request->getAttachment()->getName(),
			'file_extension' => $upload->getExtension(),
			'document_id' => $upload->getId(),
		]);
		$envelopeDefinition->setDocuments([$document]);

		// Signers
		$docusignSigners = [];
		$requestSignersRepository = new RequestSignersRepository();
		$contactRepository = new ContactRepository();
		foreach ($request->getSigners() as $index => $signer)
		{
			$contact = $contactRepository->getByEmail($signer->email);

			$requestSigner =  $requestSignersRepository->loadSignerByRequestAndContact($request, $contact);

			if (!empty($requestSigner->getAnchor()))
			{
				$signHere = new SignHere([
					'document_id' => $upload->getId(),
					'page_number' => !empty($requestSigner->getPage()) ? $requestSigner->getPage() : 1,
					'anchor_string' => $requestSigner->getAnchor(),
					'anchor_units' => 'inches',
					'anchor_x_offset' => '0',
					'anchor_y_offset' => $index * 0.2,
					'anchor_allow_white_space_in_characters' => 'true',
					'anchor_ignore_if_not_present' => 'false',
				]);
			}
			else
			{
				$signHere = new SignHere([
					'document_id' => $upload->getId(),
					'page_number' => !empty($requestSigner->getPage()) ? $requestSigner->getPage() : 1,
					'x_position' => $this->getCoordinates($requestSigner->getPosition())['x'],
					'y_position' => $this->getCoordinates($requestSigner->getPosition())['y'] + $index * 20,
				]);
			}

			$signerTabs = new Tabs([
				'sign_here_tabs' => [$signHere],
			]);

			$newSigner = new Signer([
				'email' => $requestSigner->getContact()->getEmail(),
				'name' => $requestSigner->getContact()->getFullName(),
				'recipient_id' => $index + 1,
				'routing_order' => '1',
				'tabs' => $signerTabs,
			]);

			if ($request->isOrdered())
			{
				$newSigner->setRoutingOrder($requestSigner->getOrder() ?? ($index + 1));
			}

			if ($requestSigner->getAuthenticationLevel() !== SignAuthenticationLevelEnum::STANDARD) {
				// TODO: Advanced or Qualified Electronic Signature - to be implemented use RecipientIdentityVerification
			}

			$docusignSigners[] = $newSigner;
		}

		$recipients = new Recipients([
			'signers' => $docusignSigners,
		]);

		$envelopeDefinition->setRecipients($recipients);
		$envelopeDefinition->setStatus('sent');

		return $envelopeDefinition;
	}

	/**
	 * @param   Request  $request
	 * @return bool
	 */
	public function sendRequest(Request $request): bool
	{
		$sent = false;

		try {
			$envelopeDefinition = $this->makeEnvelope($request);
			$envelopeApi = new EnvelopesApi($this->client);
			$envelopeSummary = $envelopeApi->createEnvelope($this->getAccountId(), $envelopeDefinition);

			if (!empty($envelopeSummary->getEnvelopeId())) {
				$request->setExternalReference($envelopeSummary->getEnvelopeId());

				$requestRepository = new RequestRepository();
				$sent = $requestRepository->flush($request);
			} else {
				Log::add('DocuSign envelope creation failed, no envelope ID returned.', Log::ERROR, 'com_emundus.docusign');
			}
		} catch (ApiException $e) {
			$sent = false;
			Log::add('Api Error on sending DocuSign request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
		}
		catch (\Exception $e)
		{
			$sent = false;
			Log::add('General error on sending DocuSign request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
		}

		return $sent;
	}

	/**
	 * @param   Request  $request
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function managesRequest(Request $request): bool
	{
		$managed = false;

		if (empty($request->getExternalReference()))
		{
			$managed = $this->sendRequest($request);
		}
		else
		{
			$envelope = $this->getEnvelope($request);
			if (!empty($envelope))
			{
				$requestSignerRepository = new RequestSignersRepository();
				assert($envelope instanceof Envelope);

				/// get recipients to check their status
				$envelopeApi = new EnvelopesApi($this->client);
				$recipients = $envelopeApi->listRecipients($this->getAccountId(), $envelope->getEnvelopeId());

				$requestSigners = $requestSignerRepository->getByRequest($request);

				foreach ($recipients->getSigners() as $docusignSigner)
				{
					foreach($requestSigners as $requestSigner)
					{
						if ($docusignSigner->getEmail() == $requestSigner->getContact()->getEmail())
						{
							switch($docusignSigner->getStatus())
							{
								case 'sent':
								case 'delivered':
									$requestSigner->setStatus(SignStatusEnum::TO_SIGN);
									break;
								case 'declined':
									$requestSigner->setStatus(SignStatusEnum::DECLINED);
									break;
								case 'signed':
								case 'completed':
									$requestSigner->setStatus(SignStatusEnum::SIGNED);

									$date = date('Y-m-d H:i:s', strtotime($docusignSigner->getSignedDateTime()));
									$requestSigner->setSignedAt($date);
								break;
							}

							try {
								$requestSignerRepository->flush($requestSigner);
							}
							catch (\Exception $e)
							{
								Log::add('General error on updating DocuSign request signer status : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
							}
						}
					}
				}

				switch($envelope->getStatus())
				{
					case 'declined':
						$request->setStatus(SignStatusEnum::DECLINED);
						break;
					case 'completed':
					case 'signed':
						$request->setStatus(SignStatusEnum::SIGNED);
						$this->onAfterRequestCompleted($request);
						break;
					case 'voided':
						$request->setStatus(SignStatusEnum::CANCELLED);
						break;
					case 'sent':
					case 'delivered':
						$request->setStatus(SignStatusEnum::TO_SIGN);
						break;
				}

				if (!in_array($request->getStatus(), [SignStatusEnum::SIGNED, SignStatusEnum::CANCELLED, SignStatusEnum::DECLINED]))
				{
					if ($request->getSendReminder() === 1)
					{
						try {
							$options = new EnvelopesApi\UpdateOptions();
							$options->setResendEnvelope('true');
							$response = $envelopeApi->update($this->getAccountId(), $envelope->getEnvelopeId(), '{}', $options);

							if ($response->getEnvelopeId() === $envelope->getEnvelopeId())
							{
								Log::add('Reminder sent for DocuSign request with envelope ID : ' . $envelope->getEnvelopeId(), Log::INFO, 'com_emundus.docusign');
								$request->setLastReminderAt(date('Y-m-d H:i:s'));
								$request->setSendReminder(0);
								$request->setStatus(SignStatusEnum::REMINDER_SENT);
							}
						}
						catch (\Exception $e)
						{
							Log::add('Error on sending reminder for DocuSign request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
						}
					}
				}

				$requestRepository = new RequestRepository();
				$managed = $requestRepository->flush($request);
			}
		}

		return $managed;
	}

	/**
	 * @param   Request  $request
	 *
	 * @return void
	 * @throws ApiException
	 */
	public function onAfterRequestCompleted(Request $request): void
	{
		// get signed document and save it locally
		$envelope = $this->getEnvelope($request);
		$envelopeApi   = new EnvelopesApi($this->client);
		$documentsList = $envelopeApi->listDocuments($this->getAccountId(), $envelope->getEnvelopeId());

		foreach ($documentsList->getEnvelopeDocuments() as $docInfo)
		{
			if ($docInfo->getDocumentId() != 'certificate')
			{
				$documentBytes = $envelopeApi->getDocument($this->getAccountId(), $docInfo->getDocumentId(), $envelope->getEnvelopeId());
				if (!empty($request->getUploadId()))
				{
					$content = '';
					while (!$documentBytes->eof()) {
						$content .= $documentBytes->fgets();
					}

					$uploadRepository = new UploadRepository();
					$upload           = $uploadRepository->getById($request->getUploadId());

					if (file_put_contents($upload->getFileInternalPath(), $content) === false) {
						throw new \RuntimeException("Failed to write signed document to file.");
					} else {
						$upload->setIsSigned(true);

						if (!$uploadRepository->flush($upload))
						{
							throw new \RuntimeException("Failed to update upload as signed.");
						} else
						{
							$request->setSignedUploadId($upload->getId());
							$requestRepository = new RequestRepository();

							if (!$requestRepository->flush($request))
							{
								throw new \RuntimeException("Failed to update request with signed upload ID.");
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param   Request  $request
	 * @param   string   $reason
	 *
	 * @return bool
	 */
	public function cancelRequest(Request $request, string $reason): bool
	{
		$cancelled = false;

		try
		{
			$envelopeApi = new EnvelopesApi($this->client);
			$envelope = new Envelope();
			$envelope->setStatus('voided');
			$envelope->setVoidedReason($reason);

			if ($envelopeApi->update($this->getAccountId(), $request->getExternalReference(), $envelope))
			{
				$requestRepository = new RequestRepository();
				$request->setStatus(SignStatusEnum::CANCELLED);
				$cancelled = $requestRepository->flush($request);
			}
		}
		catch (ApiException $e)
		{
			Log::add('Api Error on cancelling DocuSign request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
		}
		catch (\Exception $e)
		{
			Log::add('General error on cancelling DocuSign request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
		}

		return $cancelled;
	}

	/**
	 * @param   Request  $request
	 *
	 * @return Envelope|null
	 */
	public function getEnvelope(Request $request): ?Envelope
	{
		$envelope = null;

		try {
			$envelopeApi = new EnvelopesApi($this->client);
			$envelope = $envelopeApi->getEnvelope($this->getAccountId(), $request->getExternalReference());
		} catch (ApiException $e) {
			Log::add('Api Error on retrieving DocuSign envelope : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
		}
		catch (\Exception $e)
		{
			Log::add('General error on retrieving DocuSign envelope : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
		}

		return $envelope;
	}


	/**
	 * @param   string  $position
	 *
	 * @return array
	 */
	private function getCoordinates(string $position): array
	{
		$Xunit = 100;
		$yUnit = 100;

		$coordinate = [
			'x' => $Xunit*3,
			'y' => $yUnit
		];

		switch ($position)
		{
			case 'A1';
				$coordinate['x'] = $Xunit;
				$coordinate['y'] = $yUnit;
				break;
			case 'A2';
				$coordinate['x'] = $Xunit * 3;
				$coordinate['y'] = $yUnit;
				break;
			case 'A3';
				$coordinate['x'] = $Xunit * 5;
				$coordinate['y'] = $yUnit;
				break;
			case 'B1';
				$coordinate['x'] = $Xunit;
				$coordinate['y'] = $yUnit * 2;
				break;
			case 'B2';
				$coordinate['x'] = $Xunit * 3;
				$coordinate['y'] = $yUnit * 2;
				break;
			case 'B3';
				$coordinate['x'] = $Xunit * 5;
				$coordinate['y'] = $yUnit * 2;
				break;
			case 'C1';
				$coordinate['x'] = $Xunit;
				$coordinate['y'] = $yUnit * 3;
				break;
			case 'C2';
				$coordinate['x'] = $Xunit * 3;
				$coordinate['y'] = $yUnit * 3;
				break;
			case 'C3';
				$coordinate['x'] = $Xunit * 5;
				$coordinate['y'] = $yUnit * 3;
				break;
		}

		return $coordinate;
	}

	/**
	 * @param   string  $content
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function processCallback(string $content): void
	{
		$data = json_decode($content, true);
		$envelopeId = $data['envelopeId'] ?? null;

		if (!empty($envelopeId))
		{
			$requestRepository = new RequestRepository();
			$request = $requestRepository->getByExternalReference($envelopeId);

			if (!empty($request))
			{
				try
				{
					$this->managesRequest($request);
				}
				catch (\Exception $e)
				{
					Log::add('Error processing DocuSign callback for envelope ID ' . $envelopeId . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docusign');
				}
			}
		}
	}
}