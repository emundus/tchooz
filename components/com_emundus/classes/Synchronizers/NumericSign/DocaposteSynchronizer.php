<?php

namespace Tchooz\Synchronizers\NumericSign;

use EmundusModelEmails;
use Exception;
use JFactory;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Tchooz\api\Api;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\NumericSign\Request as Request;
use Tchooz\Entities\NumericSign\RequestSigners;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\NumericSign\DocaposteEmailTypeEnum;
use Tchooz\Enums\NumericSign\SignStatusEnum;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Traits\TraitDispatcher;

class DocaposteSynchronizer extends Api
{
	use TraitDispatcher;

	protected $auth = [];

	protected $client;
	private array $config = [];
	private const BASE_URL = 'https://test.contralia.fr:443/Contralia/api/v2';

	/**
	 * DocuSignSynchronizer constructor.
	 *
	 * @throws Exception
	 */
	public function __construct()
	{
		parent::__construct();

		Log::addLogger(['text_file' => 'com_emundus.docaposte.php',], Log::ALL, ['com_emundus.docaposte']);

		try
		{
			$infos = $this->getAuthenticationAndConfigurationInfos();

			$this->auth   = $infos['auth'];
			$this->config = $infos['conf'];

			$this->setBaseUrl(self::BASE_URL);
			$headers = array(
				'Authorization' => 'Basic ' . base64_encode($this->auth['identifier'] . ':' . $this->auth['password']),
			);

			$this->setHeaders($headers);
			$this->setClient();

		}
		catch (Exception $e)
		{
			Log::add('Error on Docaposte api connection : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docaposte');

			throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_CONNECTION_ERROR'));
		}
	}

	private function getAuthenticationAndConfigurationInfos(): array
	{
		$auth = [];
		$conf = [];

		$syncRepository = new SynchronizerRepository();
		$syncEntity     = $syncRepository->getByType('docaposte');

		$params                         = $syncEntity->getConfig() ?? [];
		$auth['identifier']             = $params['authentication']['identifier'];
		$auth['password']               = !empty($params['authentication']['password'])
			? \EmundusHelperFabrik::decryptDatas($params['authentication']['password'])
			: '';
		$auth['offerCode']              = $params['authentication']['offerCode'];
		$auth['organizationalUnitCode'] = $params['authentication']['organizationalUnitCode'];
		$infos['auth']                  = $auth;

		$conf['senderEmail']       = $params['configuration']['senderEmail'];
		$conf['emailInit']         = $params['configuration']['emailInit'];
		$conf['emailReminder']     = $params['configuration']['emailReminder'];
		$conf['emailCancellation'] = $params['configuration']['emailCancellation'];
		$conf['emailCompletion']   = $params['configuration']['emailCompletion'];
		$conf['mode']              = $params['configuration']['mode'];
		$infos['conf']             = $conf;

		return $infos;
	}

	/**
	 * @throws Exception
	 */
	public function managesRequest(Request $request): bool
	{
		$managed = false;

		if (empty($request->getExternalReference()))
		{

			if (!$this->initiateTransaction($request))
			{
				return false;
			}

			$requestSignerRepository = new RequestSignersRepository();
			$signers                 = $requestSignerRepository->getByRequest($request);

			foreach ($signers as $index => $signer)
			{
				if (!$this->addSignatory($request->getExternalReference(), $signer, $index + 1))
				{
					return false;
				}
			}

			if ($request->getAttachment() !== null)
			{
				$uploadRepository = new UploadRepository();
				$upload           = $uploadRepository->getById($request->getUploadId());
				assert($upload instanceof UploadEntity);

				if (!$this->uploadDocument($request, $upload))
				{
					return false;
				}

				if (!$this->sendEmail($request, DocaposteEmailTypeEnum::INITIATE_TRANSACTION))
				{
					return false;
				}
			}

			$managed = true;
		}
		else
		{
			$transaction = $this->getTransactionStateAndSignatories($request);
			if (!empty($transaction))
			{
				$requestSignerRepository = new RequestSignersRepository();
				$docaposteSigners        = $transaction['signatorySignatures'];

				$requestSigners            = $requestSignerRepository->getByRequest($request);
				$requestSignersById        = [];
				$requestSignersSignedCount = 0;

				foreach ($requestSigners as $requestSigner)
				{
					$requestSignersById[$requestSigner->getId()] = $requestSigner;
				}

				foreach ($docaposteSigners as $docaposteSigner)
				{
					$externalId = $docaposteSigner['signatory']['externalId'];

					if (!isset($requestSignersById[$externalId]))
					{
						continue;
					}

					$requestSigner = $requestSignersById[$externalId];

					if (!$docaposteSigner['signed'])
					{
						if (!empty($docaposteSigner['abortReason']) && !empty($docaposteSigner['refuseReason']))
						{
							$requestSigner->setStatus(SignStatusEnum::DECLINED);
						}
						else
						{
							switch ($docaposteSigner['state'])
							{
								case 'PENDING':
									$requestSigner->setStatus(SignStatusEnum::TO_SIGN);
									break;
								default:
									break;
							}
						}
					}
					else
					{
						$requestSigner->setStatus(SignStatusEnum::SIGNED);
						$date = date('Y-m-d H:i:s', strtotime($docaposteSigner['date']));
						$requestSigner->setSignedAt($date);
						$requestSignersSignedCount++;
					}

					try
					{
						$requestSignerRepository->flush($requestSigner);
					}
					catch (Exception $e)
					{
						Log::add('Error while updating Docaposte request signer status : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docaposte');
					}
				}

				if ($requestSignersSignedCount === count($request->getSigners()) && $request->getStatus() !== SignStatusEnum::SIGNED)
				{
					$this->terminateTransaction($request);
					$request->setStatus(SignStatusEnum::SIGNED);
					if ($this->config['emailCompletion'])
					{
						$this->sendEmail($request, DocaposteEmailTypeEnum::COMPLETE_TRANSACTION);
					}
					$this->onAfterRequestCompleted($request);
				}
				else
				{
					if ($request->getStatus() === SignStatusEnum::TO_SIGN)
					{
						switch ($transaction['state'])
						{
							case 'CLOSED':
							case 'ARCHIVED':
								$request->setStatus(SignStatusEnum::SIGNED);
								$this->sendEmail($request, DocaposteEmailTypeEnum::COMPLETE_TRANSACTION);
								$this->onAfterRequestCompleted($request);
								break;
							case 'ABANDONED':
								$request->setStatus(SignStatusEnum::CANCELLED);
								$this->sendEmail($request, DocaposteEmailTypeEnum::CANCEL_TRANSACTION);
								PluginHelper::importPlugin('emundus');

								// Declare the event
								$onAfterSignRequestCancelledEventHandler = new GenericEvent(
									'onCallEventHandler',
									['onAfterSignRequestCancelled',
										// Datas to pass to the event
										['context' => new EventContextEntity(
											Factory::getApplication()->getIdentity(),
											[$request->getFnum()],
											[],
											[
												'request_id' => $request->getId(),
											]
										)]

									]
								);

								$dispatcher = Factory::getApplication()->getDispatcher();
								// Dispatch the event
								$dispatcher->dispatch('onCallEventHandler', $onAfterSignRequestCancelledEventHandler);
								break;
							case 'OPEN':
								$request->setStatus(SignStatusEnum::TO_SIGN);
								break;
							default:
								break;
						}
					}
				}

				if (!in_array($request->getStatus(), [SignStatusEnum::SIGNED, SignStatusEnum::CANCELLED, SignStatusEnum::DECLINED]))
				{
					if ($request->getSendReminder() === 1)
					{
						try
						{
							$this->sendEmail($request, DocaposteEmailTypeEnum::SEND_REMINDER);
						}
						catch (Exception $e)
						{
							Log::add('Error while sending reminder for Docaposte request : ' . $e->getMessage(), Log::ERROR, 'com_emundus.docaposte');
						}
					}
				}

				$requestRepository = new RequestRepository();
				$managed           = $requestRepository->flush($request);
			}
		}

		return $managed;
	}


	/**
	 * @throws Exception
	 */
	private function initiateTransaction(Request $request): bool
	{
		$initiated = false;
		try
		{
			$testMode = ($this->config['mode'] ?? 'TEST') === 'TEST';

			$title = "Request #{$request->getId()} - {$request->getAttachment()->getName()}";
			$title = mb_strlen($title) > 200 ? mb_substr($title, 0, 197) . '...' : $title;

			$body = [
				'organizationalUnitCode' => $this->auth['organizationalUnitCode'],
				'offerCode'              => $this->auth['offerCode'],
				'customRef'              => $request->getId(),
				'testMode'               => $testMode ? 'true' : 'false',
				'title'                  => $title,
				'signatoriesCount'       => count($request->getSigners()),
			];

			$response = $this->post(
				$this->auth['offerCode'] . '/transactions',
				$body,
				$this->getHeaders()
			);

			if ($this->hasRequestFailed($response['status']))
			{
				throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_TRANSACTION_INITIATION_ERROR'));
			}
			$transaction_id = $this->extractTransactionId($response['data']);
			$request->setExternalReference($transaction_id);

			$requestRepository = new RequestRepository();
			$initiated         = $requestRepository->flush($request);

			if ($initiated)
			{
				PluginHelper::importPlugin('emundus');

				// Declare the event
				$onAfterSignRequestCreatedEventHandler = new GenericEvent(
					'onCallEventHandler',
					['onAfterSignRequestCreated',
						// Datas to pass to the event
						['context' => new EventContextEntity(
							Factory::getApplication()->getIdentity(),
							[$request->getFnum()],
							[],
							[
								'request_id' => $request->getId(),
							]
						)]

					]
				);

				$dispatcher = Factory::getApplication()->getDispatcher();
				// Dispatch the event
				$dispatcher->dispatch('onCallEventHandler', $onAfterSignRequestCreatedEventHandler);
			}
		}
		catch (Exception $e)
		{
			Log::add(
				'Error during Docaposte init transaction : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $initiated;
	}

	private function uploadDocument(Request $request, UploadEntity $document): bool
	{
		$uploaded = false;

		try
		{
			$fieldsXml = '<fields xmlns="http://www.contralia.fr/champsPdf">';

			$signatureWidth  = 100;
			$signatureHeight = 50;

			$contactRepository        = new ContactRepository();
			$requestSignersRepository = new RequestSignersRepository();

			foreach ($request->getSigners() as $index => $signer)
			{
				$number = $index + 1;

				$contact       = $contactRepository->getByEmail($signer->email);
				$requestSigner = $requestSignersRepository->loadSignerByRequestAndContact($request, $contact);

				if ($requestSigner->getAnchor() !== null)
				{
					$anchor  = $requestSigner->getAnchor();
					$offsetX = 0;
					$offsetY = 0;

					$xy = '{' . $anchor . '}' . $offsetX . ';' . $offsetY;

					$fieldsXml .= <<<XML
						<signatorySignature number="{$number}">
						    <box xy="{$xy}"
						         width="{$signatureWidth}"
						         height="{$signatureHeight}"/>
						</signatorySignature>
						XML;
				}
				else
				{
					$pdf = new Fpdi('P', 'pt');
					$pdf->setSourceFile($document->getFileInternalPath());
					$tpl = $pdf->importPage($signer->page === 0 ? 1 : $signer->page);

					$size = $pdf->getTemplateSize($tpl);

					$width  = $size['width'];
					$height = $size['height'];

					$position = $this->resolvePosition($signer->position ?? null, $index);

					$coordinates = $this->getCoordinates(
						$position,
						$width,
						$height,
						$signatureWidth,
						$signatureHeight
					);

					$x = $coordinates['x'];
					$y = $coordinates['y'];

					$page = $signer->page;

					$fieldsXml .= <<<XML
				    <signatorySignature number="{$number}">
				        <box x="{$x}"
				             y="{$y}"
				             width="{$signatureWidth}"
				             height="{$signatureHeight}"
				             page="{$page}" />
				    </signatorySignature>
				XML;
				}
			}

			$fieldsXml .= '</fields>';

			$multipart = [
				[
					'name'     => 'file',
					'contents' => fopen($document->getFileInternalPath(), 'r'),
					'filename' => basename($document->getFileInternalPath()),
					'headers'  => [
						'Content-Type' => 'application/pdf'
					]
				],
				[
					'name'     => 'name',
					'contents' => $request->getAttachment()->getName(),
				],
				[
					'name'     => 'fields',
					'contents' => $fieldsXml,
				],
			];

			$response = $this->post(
				'transactions/' . $request->getExternalReference() . '/document',
				$multipart,
				[],
				true
			);

			if ($this->hasRequestFailed($response['status']))
			{
				throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_DOCUMENT_UPLOADING_ERROR'));
			}

			$uploaded = true;

		}
		catch (Exception $e)
		{
			Log::add(
				'Error while uploading document on Docaposte : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $uploaded;
	}

	private function resolvePosition(?string $position, int $index): string
	{
		if (is_string($position) && preg_match('/^[ABC][1-9][0-9]*$/', $position))
		{
			return $position;
		}

		$columns = ['A', 'B', 'C'];

		$col = $columns[$index % 3];
		$row = intdiv($index, 3) + 1;

		return $col . $row;
	}

	private function getCoordinates(
		string $position,
		float  $pageWidth,
		float  $pageHeight,
		int    $signatureWidth,
		int    $signatureHeight,
		float  $marginRatio = 0.1
	): array
	{
		$marginX = $pageWidth * $marginRatio;
		$marginY = $pageHeight * $marginRatio;

		$xRatios = [
			'A' => 0.0,
			'B' => 0.5,
			'C' => 1.0,
		];

		$yRatios = [
			1 => 1.0,
			2 => 0.5,
			3 => 0.0,
		];

		$col = $position[0];
		$row = (int) $position[1];

		if (!isset($xRatios[$col], $yRatios[$row]))
		{
			$col = 'A';
			$row = 1;
		}

		$x = ($pageWidth - $signatureWidth) * $xRatios[$col];
		$y = ($pageHeight - $signatureHeight) * $yRatios[$row];

		$x = max($marginX, min($x, $pageWidth - $signatureWidth - $marginX));
		$y = max($marginY, min($y, $pageHeight - $signatureHeight - $marginY));

		return [
			'x' => (int) round($x),
			'y' => (int) round($y),
		];
	}

	public function addSignatory(
		string         $transaction_id,
		RequestSigners $signer,
		int            $position = 1
	): bool
	{
		$added = false;
		try
		{
			$contact = $signer->getContact();
			$body    = [
				'firstname'  => $contact->getFirstname(),
				'lastname'   => $contact->getLastname(),
				'email'      => $contact->getEmail(),
				'externalId' => $signer->getId(),
			];

			$response = $this->post(
				'transactions/' . $transaction_id . '/signatory/' . $position, $body, $this->getHeaders()
			);

			if ($this->hasRequestFailed($response['status']))
			{
				throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_SIGNATORY_ADDITION_ERROR'));
			}

			$added = true;
		}
		catch (Exception $e)
		{
			Log::add(
				'Error while adding Docaposte signatory : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $added;
	}

	/**
	 * @throws Exception
	 */
	public function cancelRequest(Request $request, string $reason): bool
	{
		$cancelled = false;
		try
		{
			$url  = '/transactions/' . $request->getExternalReference() . '/abort';
			$body = [];

			if (!empty($reason))
			{
				$body = [
					'abortReason' => $reason
				];
			}

			$response = $this->post(
				$url,
				$body,
				$this->getHeaders()
			);

			if ($this->hasRequestFailed($response['status']))
			{
				throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_REQUEST_CANCELLATION_ERROR'));
			}

			$request->setStatus(SignStatusEnum::CANCELLED);
			if ($this->config['emailCancellation'])
			{
				$this->sendEmail($request, DocaposteEmailTypeEnum::CANCEL_TRANSACTION);
			}

			$requestRepository = new RequestRepository();

			$cancelled = $requestRepository->flush($request);

			if ($cancelled)
			{
				PluginHelper::importPlugin('emundus');

				// Declare the event
				$onAfterSignRequestCancelledEventHandler = new GenericEvent(
					'onCallEventHandler',
					['onAfterSignRequestCancelled',
						// Datas to pass to the event
						['context' => new EventContextEntity(
							Factory::getApplication()->getIdentity(),
							[$request->getFnum()],
							[],
							[
								'request_id' => $request->getId(),
							]
						)]

					]
				);

				$dispatcher = Factory::getApplication()->getDispatcher();
				// Dispatch the event
				$dispatcher->dispatch('onCallEventHandler', $onAfterSignRequestCancelledEventHandler);
			}
		}

		catch (Exception $e)
		{
			Log::add(
				'Error while cancelling Docaposte transaction : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $cancelled;
	}


	/**
	 * @throws Exception
	 */
	private function terminateTransaction(Request $request): bool
	{
		$terminated = false;
		try
		{
			$url = '/transactions/' . $request->getExternalReference() . '/terminate';

			$response = $this->post(
				$url,
				[],
				$this->getHeaders()
			);

			if ($this->hasRequestFailed($response['status']))
			{
				throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_TRANSACTION_COMPLETION_ERROR'));
			}

			$request->setStatus(SignStatusEnum::SIGNED);

			$requestRepository = new RequestRepository();

			$terminated = $requestRepository->flush($request);

			if ($terminated)
			{
				PluginHelper::importPlugin('emundus');

				// Declare the event
				$onAfterSignRequestCompletedEventHandler = new GenericEvent(
					'onCallEventHandler',
					['onAfterSignRequestCompleted',
						// Datas to pass to the event
						['context' => new EventContextEntity(
							Factory::getApplication()->getIdentity(),
							[$request->getFnum()],
							[],
							[
								'request_id' => $request->getId(),
							]
						)]

					]
				);

				$dispatcher = Factory::getApplication()->getDispatcher();
				// Dispatch the event
				$dispatcher->dispatch('onCallEventHandler', $onAfterSignRequestCompletedEventHandler);
			}
		}
		catch (Exception $e)
		{
			Log::add(
				'Error while terminating Docaposte transaction : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $terminated;
	}

	private function onAfterRequestCompleted(Request $request): void
	{
		$pdfContent = $this->getFinalDocument($request);

		if (empty($request->getUploadId()))
		{
			return;
		}

		$uploadRepository = new UploadRepository();
		$upload           = $uploadRepository->getById($request->getUploadId());

		if (!$upload)
		{
			throw new \RuntimeException(Text::_('DOCAPOSTE_SYNCHRONIZER_UPLOAD_NOT_FOUND'));
		}

		if (file_put_contents($upload->getFileInternalPath(), $pdfContent) === false)
		{
			throw new \RuntimeException(Text::_('DOCAPOSTE_SYNCHRONIZER_FAILED_TO_WRITE_DOCUMENT'));
		}

		$upload->setIsSigned(true);

		if (!$uploadRepository->flush($upload))
		{
			throw new \RuntimeException(Text::_('DOCAPOSTE_SYNCHRONIZER_FAILED_TO_UPDATE_DOCUMENT'));
		}

		$request->setSignedUploadId($upload->getId());
		$requestRepository = new RequestRepository();

		if (!$requestRepository->flush($request))
		{
			throw new \RuntimeException(Text::_('DOCAPOSTE_SYNCHRONIZER_FAILED_TO_UPDATE_REQUEST'));
		}
	}

	private function getFinalDocument(Request $request): string
	{
		$url  = '/transactions/' . $request->getExternalReference() . '/finalDoc';
		$body = [];

		if ($request->getAttachment()->getName())
		{
			$body = [
				'name' => $request->getAttachment()->getName()
			];
		}

		$response = $this->get(
			$url,
			$body,
			$this->getHeaders()
		);

		if (!isset($response['is_file']) || $response['is_file'] !== true)
		{
			throw new RuntimeException(Text::_('DOCAPOSTE_SYNCHRONIZER_FINAL_DOCUMENT_NOT_A_FILE'));
		}

		if (empty($response['data']))
		{
			throw new RuntimeException(Text::_('DOCAPOSTE_SYNCHRONIZER_FINAL_DOCUMENT_IS_EMPTY'));
		}

		return $response['data'];
	}

	private function sendEmail(Request $request, DocaposteEmailTypeEnum $emailType): bool
	{
		$sent = false;
		try
		{
			$config = $this->getEmailConfig($emailType);
			if (isset($config['templateId']))
			{
				$signers = (new RequestSignersRepository())->getByRequest($request);

				if ($config['signerFilter'])
				{
					$signers = array_filter($signers, $config['signerFilter']);
				}

				$transactionData = $this->getTransactionStateAndSignatories($request);
				$signatureMap    = $this->buildSignatureMap($transactionData);

				if (!class_exists('EmundusModelEmails'))
				{
					require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
				}

				$emailModel = new EmundusModelEmails();

				foreach ($signers as $signer)
				{
					$this->sendConfiguredEmail(
						$request,
						$signer,
						$signatureMap,
						$emailModel,
						$config
					);
				}

				if ($config['after'])
				{
					($config['after'])($request);
				}

				$sent = true;
			}
		}
		catch (Exception $e)
		{
			Log::add(
				'Error while sending email with Docaposte : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $sent;
	}

	/**
	 * @throws Exception
	 */
	private function sendConfiguredEmail(
		Request            $request,
		RequestSigners     $signer,
		array              $signatureMap,
		EmundusModelEmails $emailModel,
		array              $config
	): void
	{
		try
		{
			$signerId = $signer->getId();

			if (!isset($signatureMap[$signerId]))
			{
				throw new \RuntimeException(Text::_("DOCAPOSTE_SYNCHRONIZER_NO_SIGNER_INFORMATION") . $signerId);
			}

			$message = $emailModel->getEmailById($config['templateId'])->message;

			if ($config['needsSignUrl'])
			{
				$signUrl = $this->createSignURL($request, $signatureMap[$signerId]);
				$message = str_replace('[DOCAPOSTE_URL_SIGN]', $signUrl, $message);
			}

			$message = str_replace(
				'[DOCAPOSTE_DOCUMENT_NAME]',
				$request->getAttachment()->getName(),
				$message
			);

			$user    = JFactory::getSession()->get('emundusUser');
			$subject = $emailModel->setBody($user, $emailModel->getEmailById($config['templateId'])->subject);
			$message = $emailModel->setBody($user, $message);

			$this->sendEmailToSigner($request, $signer, $subject, $message);
		}
		catch (Exception $e)
		{
			Log::add(
				'Error while configuring email with Docaposte : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

	}

	private function sendEmailToSigner(
		Request        $request,
		RequestSigners $signer,
		string         $subject,
		string         $message
	): void
	{
		$body = [
			'to'          => $signer->getContact()->getEmail(),
			'from'        => $this->config['senderEmail'],
			'mailSubject' => $subject,
			'mailBody'    => $message,
		];

		$response = $this->post(
			'/transactions/' . $request->getExternalReference() . '/sendMail',
			$body,
			$this->getHeaders()
		);

		if ($this->hasRequestFailed($response['status']))
		{
			throw new RuntimeException(Text::_('DOCAPOSTE_SYNCHRONIZER_SENDING_EMAIL_ERROR'));
		}
	}

	/**
	 * @throws Exception
	 */
	private function createSignURL(Request $request, int $signature_id): string
	{
		$signUrl = '';
		try
		{
			$this->setBaseUrl('https://test.contralia.fr:443/eDoc/api');

			$body = [
				'transactionId' => $request->getExternalReference(),
				'signaturesIds' => $signature_id
			];

			$response = $this->post(
				'/document/signUrl',
				$body,
				$this->getHeaders()
			);

			if ($this->hasRequestFailed($response['status']))
			{
				throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_SIGN_URL_CREATION_ERROR'));
			}

			$signUrl = $response['data']->{'0'} ?? '';

			$this->setBaseUrl(self::BASE_URL);
		}
		catch (Exception $e)
		{
			Log::add(
				'Error while creating Docaposte sign URL : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $signUrl;
	}

	private function getTransactionStateAndSignatories(Request $request): array
	{
		$transactionStateAndSignatories = [];
		try
		{
			$response = $this->get('transactions/' . $request->getExternalReference());

			if ($this->hasRequestFailed($response['status']))
			{
				throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_TRANSACTION_STATE_ERROR'));
			}

			$transactionStateAndSignatories = $this->extractTransactionStateAndSignatories($response['data']);
		}
		catch (Exception $e)
		{
			Log::add(
				'Error while trying to get Docaposte transaction state : ' . $e->getMessage(),
				Log::ERROR,
				'com_emundus.docaposte'
			);
		}

		return $transactionStateAndSignatories;
	}

	private function buildSignatureMap(array $transactionData): array
	{
		$map = [];

		foreach ($transactionData['signatorySignatures'] as $signature)
		{
			$externalId  = $signature['signatory']['externalId'] ?? null;
			$signatureId = $signature['id'] ?? null;

			if ($externalId && $signatureId)
			{
				$map[$externalId] = $signatureId;
			}
		}

		return $map;
	}

	private function getEmailConfig(DocaposteEmailTypeEnum $type): array
	{
		return match ($type)
		{
			DocaposteEmailTypeEnum::INITIATE_TRANSACTION => [
				'templateId'   => $this->config['emailInit'],
				'needsSignUrl' => true,
				'signerFilter' => null,
				'after'        => null,
			],

			DocaposteEmailTypeEnum::SEND_REMINDER => [
				'templateId'   => $this->config['emailReminder'],
				'needsSignUrl' => true,
				'signerFilter' => fn($s) => $s->getStatus() === SignStatusEnum::TO_SIGN,
				'after'        => fn(Request $r) => $this->afterReminder($r),
			],

			DocaposteEmailTypeEnum::CANCEL_TRANSACTION => [
				'templateId'   => $this->config['emailCancellation'],
				'needsSignUrl' => false,
				'signerFilter' => null,
				'after'        => null,
			],

			DocaposteEmailTypeEnum::COMPLETE_TRANSACTION => [
				'templateId'   => $this->config['emailCompletion'],
				'needsSignUrl' => false,
				'signerFilter' => null,
				'after'        => null,
			],
		};
	}

	private function afterReminder(Request $request): void
	{
		$request->setLastReminderAt(date('Y-m-d H:i:s'));
		$request->setSendReminder(0);
		$request->setStatus(SignStatusEnum::REMINDER_SENT);
	}

	/**
	 * @throws Exception
	 */
	private function extractTransactionId(object $data): string
	{
		if (isset($data->{'@attributes'}->id))
		{
			return (string) $data->{'@attributes'}->id;
		}

		throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_TRANSACTION_INITIATION_INVALID_RESPONSE_FORMAT'));
	}

	/**
	 * @throws Exception
	 */
	private function extractTransactionStateAndSignatories($data): array
	{
		$attributes = $data->{'@attributes'};

		if (!isset($attributes->state))
		{
			throw new Exception(Text::_('DOCAPOSTE_SYNCHRONIZER_TRANSACTION_STATE_NOT_FOUND'));
		}

		$result = [
			'state'               => (string) $attributes->state,
			'signatorySignatures' => [],
		];

		if (isset($data->signatorySignatures->signatorySignature))
		{
			$signatures = $data->signatorySignatures->signatorySignature;

			if (!is_array($signatures))
			{
				$signatures = [$signatures];
			}

			foreach ($signatures as $sig)
			{
				$attr = $sig->{'@attributes'} ?? null;

				if (!$attr)
				{
					continue;
				}

				$signatoryAttr = $sig->signatory->{'@attributes'} ?? null;

				$result['signatorySignatures'][] = [
					'id'                   => (string) ($attr->id ?? ''),
					'position'             => (int) ($attr->position ?? 0),
					'signatureType'        => (string) ($attr->signatureType ?? ''),
					'state'                => (string) ($attr->state ?? ''),
					'signed'               => filter_var((string) ($attr->signed ?? 'false'), FILTER_VALIDATE_BOOLEAN),
					'date'                 => (string) ($attr->date ?? ''),
					'otpExpected'          => filter_var((string) ($attr->otpExpected ?? 'false'), FILTER_VALIDATE_BOOLEAN),
					'hasAssignedDocuments' => filter_var(
						(string) ($attr->hasAssignedDocuments ?? 'false'),
						FILTER_VALIDATE_BOOLEAN
					),
					'abortReason'          => (string) ($attr->abortReason ?? ''),
					'refuseReason'         => (string) ($attr->refuseReason ?? ''),

					'signatory' => [
						'id'               => (string) ($signatoryAttr->id ?? ''),
						'lastname'         => (string) ($signatoryAttr->lastname ?? ''),
						'firstname'        => (string) ($signatoryAttr->firstname ?? ''),
						'email'            => (string) ($signatoryAttr->email ?? ''),
						'phone'            => (string) ($signatoryAttr->phone ?? ''),
						'phoneNumberValid' => filter_var(
							(string) ($signatoryAttr->phoneNumberValid ?? 'false'),
							FILTER_VALIDATE_BOOLEAN
						),
						'externalId'       => (string) ($signatoryAttr->externalId ?? ''),
					],
				];
			}
		}

		return $result;
	}
}