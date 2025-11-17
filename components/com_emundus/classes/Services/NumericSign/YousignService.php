<?php
/**
 * @package     Tchooz\Services\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\NumericSign;

use Joomla\CMS\Log\Log;
use Joomla\CMS\User\User;
use Smalot\PdfParser\Parser;
use Tchooz\Entities\NumericSign\Request;
use Tchooz\Entities\NumericSign\YousignRequests;
use Tchooz\Enums\ApiStatusEnum;
use Tchooz\Enums\NumericSign\SignStatusEnum;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\NumericSign\YousignRequestsRepository;
use Tchooz\Synchronizers\NumericSign\YousignSynchronizer;
use Tchooz\Traits\TraitDispatcher;

class YousignService
{
	use TraitDispatcher;

	private string $global_signature_level = 'electronic_signature';

	private string $global_signature_authentication_mode = 'otp_email';

	public function __construct(
		private readonly YousignSynchronizer       $yousign_synchronizer,
		private readonly YousignRequestsRepository $yousign_repository,
		private readonly RequestRepository         $request_repository,
		private readonly RequestSignersRepository  $request_signers_repository,
		private readonly \EmundusModelFiles        $m_files,
		private readonly \EmundusModelApplication  $m_application,
		private readonly User                      $user
	)
	{
	}

	public function manageRequest(int $request_id, array $yousign_requests = [], object $yousign_api = null): bool
	{
		if (empty($request_id))
		{
			throw new \Exception('Request ID is empty.', 400);
		}

		if(empty($yousign_api))
		{
			// Get yousign setup
			if (!class_exists('EmundusModelSync'))
			{
				require_once JPATH_SITE . '/components/com_emundus/models/sync.php';
			}
			$m_sync = new \EmundusModelSync();
			$api    = $m_sync->getApi(0, 'yousign');
		}

		$config = (!empty($api) && !empty($api->config)) ? json_decode($api->config) : null;
		if(!empty($config) && !empty($config->signature_level))
		{
			$this->global_signature_level = $config->signature_level;
		}
		if(!empty($config) && !empty($config->signature_authentication_mode))
		{
			$this->global_signature_authentication_mode = $config->signature_authentication_mode;
		}

		try
		{
			$request = $this->request_repository->loadRequestById($request_id);
			if (empty($request))
			{
				throw new \Exception('Request not found.', 404);
			}

			$application_file = $this->m_files->getFnumInfos($request->getFnum());
			if (empty($application_file))
			{
				throw new \Exception('Application file not found.', 404);
			}

			$yousign_request = $this->yousign_repository->loadYousignRequestByRequestId($request);
			if (empty($yousign_request))
			{
				$expiration_date = (!empty($config) && !empty($config->expiration_date)) ? $config->expiration_date : '';
				$yousign_request = $this->flushYousignRequest($application_file, $request, $this->user, $expiration_date);
			}

			if (!empty($yousign_request->getId()))
			{
				if (empty($yousign_request->getProcedureId()))
				{
					$yousign_request = $this->initiateYousignRequest($yousign_request, $request->isOrdered());
					if (!empty($yousign_request->getResponsePayload()))
					{
						$api_request = json_decode($yousign_request->getResponsePayload());
					}

					$this->dispatchJoomlaEvent('onYousignRequestInitiated', [
						'status'           => 'success',
						'yousign_request'  => $yousign_request,
						'request'          => $request,
						'application_file' => $application_file
					]);
				}

				if (!empty($yousign_request->getProcedureId()))
				{
					if (empty($api_request))
					{
						if (!empty($yousign_requests))
						{
							$api_request = array_filter($yousign_requests, function ($item) use ($yousign_request) {
								return $item->id === $yousign_request->getProcedureId();
							});
						}

						if (empty($api_request))
						{
							$this->yousign_synchronizer->getRequest($yousign_request->getProcedureId());
						}
					}

					if (!empty($api_request))
					{
						if (is_array($api_request))
						{
							$api_request = reset($api_request);
						}

						if ($request->getStatus() === SignStatusEnum::CANCELLED && $api_request->status !== 'canceled')
						{
							$this->yousign_repository->updateApiStatus($yousign_request->getId(), ApiStatusEnum::PROCESSING);
							if ($api_request->status === 'draft')
							{
								$this->yousign_synchronizer->deleteRequest($yousign_request->getProcedureId());
							}
							else
							{
								$this->yousign_synchronizer->cancelRequest($yousign_request->getProcedureId());

								$this->dispatchJoomlaEvent('onYousignRequestCancelled', [
									'status'           => 'success',
									'yousign_request'  => $yousign_request,
									'request'          => $request,
									'application_file' => $application_file
								]);
							}
							$this->yousign_repository->updateApiStatus($yousign_request->getId(), ApiStatusEnum::CANCELLED);

							$api_request->status = 'canceled';
						}

						if (!empty($api_request))
						{
							switch ($api_request->status)
							{
								case 'draft':
								case 'approval':
								case 'ongoing':
									try
									{
										$this->yousign_repository->updateApiStatus($yousign_request->getId(), ApiStatusEnum::PROCESSING);

										// Check if we have documents to sign
										if (empty($yousign_request->getDocumentId()) && !empty($request->getAttachment()))
										{
											$yousign_request = $this->addDocument($yousign_request, $application_file, $request);

											$this->dispatchJoomlaEvent('onYousignDocumentAdded', [
												'status'           => 'success',
												'yousign_request'  => $yousign_request,
												'request'          => $request,
												'application_file' => $application_file
											]);
										}

										if (!empty($yousign_request->getDocumentId()))
										{
											// Check if we have signers in DB and add them to request
											if (empty($api_request->signers) && !empty($request->getSigners()))
											{
												$this->addSigners($request, $yousign_request, $config);

												$this->dispatchJoomlaEvent('onYousignSignersUpdated', [
													'status'           => 'success',
													'yousign_request'  => $yousign_request,
													'request'          => $request,
													'application_file' => $application_file
												]);
											}
											elseif (!empty($api_request->signers) && !empty($request->getSigners()))
											{
												$this->manageSigners($api_request->signers, $yousign_request, $request, $application_file);
											}

											if ($api_request->status === 'draft')
											{
												$this->yousign_synchronizer->activateRequest($yousign_request->getProcedureId());

												$this->dispatchJoomlaEvent('onYousignRequestActivated', [
													'status'           => 'success',
													'yousign_request'  => $yousign_request,
													'request'          => $request,
													'application_file' => $application_file
												]);
											}
										}

										$this->yousign_repository->updateApiStatus($yousign_request->getId(), ApiStatusEnum::COMPLETED);
									}
									catch (\Exception $e)
									{
										$this->yousign_repository->updateApiStatus($yousign_request->getId(), ApiStatusEnum::FAILED);
										throw $e;
									}

									break;
								case 'done':
									foreach ($request->getSigners() as $signer)
									{
										foreach ($yousign_request->getSigners() as $yousign_signer)
										{
											if ($signer->id === $yousign_signer->request_signer_id)
											{
												$api_audit_trails = $this->yousign_synchronizer->getAuditTrails($yousign_request->getProcedureId(), $yousign_signer->signer_id);
												if ($api_audit_trails['status'] === 200 && !empty($api_audit_trails['data']->signer->signature_process_completed_at))
												{
													$this->request_signers_repository->updateSignedAt($signer->id, $api_audit_trails['data']->signer->signature_process_completed_at);
												}
											}
										}
									}

									// Download documents of the request
									$this->downloadDocuments($yousign_request, $request, $application_file);
									break;
								default:
									break;
							}
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Yousign manageRequest error: ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			throw $e;
		}

		return true;
	}

	private function flushYousignRequest(array $application_file, Request $request, User $user, string $expiration_date = ''): YousignRequests
	{
		$yousign_request = new YousignRequests($user->id);

		try
		{
			$yousign_request_name = $application_file['name'] . ' - ' . $request->getAttachment()->getName();
			$yousign_request->setName($yousign_request_name);
			$yousign_request->setRequest($request);
			$yousign_request->setApiStatus(ApiStatusEnum::PENDING);
			$yousign_request->setId($this->yousign_repository->flush($yousign_request));
			if(!empty($expiration_date))
			{
				$yousign_request->setExpirationDate($expiration_date);
			}

			return $yousign_request;
		}
		catch (\Exception)
		{
			Log::add('Failed to flush yousign request.', Log::ERROR, 'com_emundus.yousign');
		}

		return $yousign_request;
	}

	private function initiateYousignRequest(YousignRequests $yousign_request, bool $is_ordered = false): YousignRequests
	{
		try
		{
			$api_request = $this->yousign_synchronizer->initRequest($yousign_request->getName(), 'email', $yousign_request->getExpirationDate(), $is_ordered);
			if ($api_request['status'] === 201)
			{
				$yousign_request->setProcedureId($api_request['data']->id);
				$yousign_request->setExpirationDate($api_request['data']->expiration_date);
				$yousign_request->setResponsePayload(json_encode($api_request['data']));

				$this->yousign_repository->flush($yousign_request);
			}
		}
		catch (\Exception $e)
		{
			Log::add('Failed to initiate yousign request: ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
		}

		return $yousign_request;
	}

	private function addDocument(YousignRequests $yousign_request, array $application_file, Request $request): YousignRequests
	{
		try
		{
			$file_to_sign = '';
			$attachments  = $this->m_application->getAttachmentsByFnum($request->getFnum());
			foreach ($attachments as $attachment)
			{
				if ($attachment->attachment_id == $request->getAttachment()->getId() && (empty($request->getUploadId()) || $attachment->id === $request->getUploadId()))
				{
					// Get file via filename
					$file_to_sign = JPATH_SITE . '/images/emundus/files/' . $application_file['applicant_id'] . '/' . $attachment->filename;
				}
			}

			if (!empty($file_to_sign) && file_exists($file_to_sign))
			{
				$api_document = $this->yousign_synchronizer->addDocument($yousign_request->getProcedureId(), $file_to_sign);
				if ($api_document['status'] == 201)
				{
					// Calculate payload of signature position
					$parser    = new Parser();
					$pdf       = $parser->parseFile($file_to_sign);
					$last_page = count($pdf->getPages());
					$text      = $pdf->getText();

					// If we found following pattern : "{{s*|signature**}}" don't need to add signature field
					preg_match_all('/\{\{s\d+\|signature(?:\|[^}|]+)*\}\}/', $text, $matches);
					if (!empty($matches[0]))
					{
						$signature_field = null;
					}
					else
					{
						$signature_field = [
							'page'   => $last_page,
							'width'  => 150,
							'height' => 42,
							'x'      => 20,
							'y'      => 750
						];
						$signature_field = json_encode($signature_field);
					}

					$yousign_request->setSignatureField($signature_field);
					$yousign_request->setDocumentId($api_document['data']->id);
					$yousign_request->setResponsePayload(json_encode($api_document));
					$this->yousign_repository->flush($yousign_request);
				}
				else
				{
					Log::add('Failed to add document to yousign request: ' . $api_document['message'], Log::ERROR, 'com_emundus.yousign');
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Failed to add document to yousign request: ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			throw $e;
		}

		return $yousign_request;
	}

	private function addSigners(Request $request, YousignRequests $yousign_request, object $config = null): YousignRequests
	{
		$api_signers = $this->yousign_synchronizer->getSigners($yousign_request->getProcedureId());

		foreach ($request->getSigners() as $key => $signer)
		{
			$signer_found = false;
			if ($api_signers['status'] === 200 && !empty($api_signers['data']))
			{
				foreach ($api_signers['data'] as $api_signer)
				{
					if ($signer->email === $api_signer->info->email)
					{
						$signer_found = true;
						break;
					}
				}
			}

			if (!$signer_found)
			{
				if (!empty($signer->page) && !empty($signer->position))
				{
					$signature_coords   = $this->mapPosition($signer->position);
					$signature_position = [
						'page'   => $signer->page,
						'width'  => 150,
						'height' => 42,
						'x'      => $signature_coords['x'],
						'y'      => $signature_coords['y']
					];
					$signature_position = (object) $signature_position;
				}
				else
				{
					$signature_position = $yousign_request->getSignatureField();
					if (!empty($signature_position))
					{
						$signature_position = json_decode($signature_position);
						if ($key !== 0)
						{
							$signature_position->x += 200;
						}
					}
				}

				$signature_level = !empty($signer->authentication_level) ? $signer->authentication_level : $this->global_signature_level;
				$signature_authentication_mode = !empty($signer->authentication_mode) ? $signer->authentication_mode : $this->global_signature_authentication_mode;

				$api_signer = $this->yousign_synchronizer->addSigner($yousign_request->getProcedureId(), $signer, $yousign_request->getDocumentId(), $signature_position, $signature_level, $signature_authentication_mode);
				if ($api_signer['status'] == 201)
				{
					$yousign_request->addSigner($api_signer['data']->id, $signer->id, $api_signer['data']->signature_link, !empty($signature_position) ? json_encode($signature_position) : null);
					$this->yousign_repository->addSigner($yousign_request->getId(), $api_signer['data']->id, $signer->id, $api_signer['data']->signature_link, !empty($signature_position) ? json_encode($signature_position) : null);
				}
			}
		}

		return $yousign_request;
	}

	private function manageSigners(array $api_signers, YousignRequests $yousign_request, Request $request, array $application_file): YousignRequests
	{
		foreach ($api_signers as $api_signer)
		{
			foreach ($yousign_request->getSigners() as $signer)
			{
				if ($signer->signer_id === $api_signer->id)
				{
					foreach ($request->getSigners() as $r_signer)
					{
						if ($r_signer->id === $signer->request_signer_id)
						{
							$request_signer = $r_signer;
							break;
						}
					}

					if (!empty($request_signer))
					{
						switch ($api_signer->status)
						{
							case 'notified':
								if (!in_array($request_signer->status, ['to_sign', 'reminder_sent']))
								{
									$this->request_signers_repository->updateStatus($request_signer->id, SignStatusEnum::TO_SIGN);
								}

								if ($request->getSendReminder() === 1)
								{
									$this->yousign_synchronizer->sendReminder($yousign_request->getProcedureId(), $api_signer->id);
									$this->request_signers_repository->updateStatus($request_signer->id, SignStatusEnum::REMINDER_SENT);

									$this->dispatchJoomlaEvent('onYousignSendReminder', [
										'status'           => 'success',
										'yousign_request'  => $yousign_request,
										'request'          => $request,
										'application_file' => $application_file
									]);
								}
								break;
							case 'signed':
								if ($request_signer->status !== 'signed')
								{
									$this->request_signers_repository->updateStatus($request_signer->id, SignStatusEnum::SIGNED);
								}
								break;
							default:
								break;
						}
					}

					break;
				}
			}
		}

		if ($request->getSendReminder() === 1)
		{
			$this->request_repository->updateSendReminder($request->getId(), 0);
			$this->request_repository->updateLastReminderAt($request->getId(), date('Y-m-d H:i:s'));
		}

		return $yousign_request;
	}

	public function downloadDocuments(YousignRequests $yousign_request, Request $request, array $application_file): YousignRequests
	{
		try
		{
			$api_documents = $this->yousign_synchronizer->downloadDocuments($yousign_request->getProcedureId(), $yousign_request->getDocumentId());

			if ($api_documents['status'] === 200 && !empty($api_documents['data']))
			{
				$filename    = '';
				$attachments = $this->m_application->getAttachmentsByFnum($request->getFnum());
				foreach ($attachments as $attachment)
				{
					if ($attachment->attachment_id == $request->getAttachment()->getId())
					{
						$filename = pathinfo($attachment->filename, PATHINFO_FILENAME);
						$filename .= '_signed_' . date('Y-m-d_H-i-s') . '.pdf';
						break;
					}
				}

				if (!empty($filename))
				{
					$applicant_path = JPATH_SITE . '/images/emundus/files/' . $application_file['applicant_id'] . '/' . $filename;
					$fp             = fopen($applicant_path, 'w+');
					fwrite($fp, $api_documents['data']);
					fclose($fp);

					// Check that file uploaded is not empty
					if (file_exists($applicant_path) && filesize($applicant_path) > 0)
					{
						// Create upload row
						$upload_id = $this->request_repository->uploadFile($filename, $request, $application_file, $this->user, filesize($applicant_path));
						if ($this->request_repository->updateStatus($request->getId(), SignStatusEnum::SIGNED))
						{
							// Update status of each signers not signed
							foreach ($request->getSigners() as $signer)
							{
								if ($signer->status !== 'signed')
								{
									$this->request_signers_repository->updateStatus($signer->id, SignStatusEnum::SIGNED);
								}
							}

							$this->dispatchJoomlaEvent('onYousignRequestCompleted', [
								'status'           => 'success',
								'yousign_request'  => $yousign_request,
								'request'          => $request,
								'application_file' => $application_file,
								'fnum'             => $application_file['fnum']
							]);

							$this->dispatchJoomlaEvent('onAfterSignRequestCompleted', [
								'api_request'      => $yousign_request,
								'request'          => $request,
								'application_file' => $application_file,
								'fnum'             => $application_file['fnum']
							]);

							//TODO: Create link between upload_id and signed_upload_id in a reference table
						}
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Failed to download documents: ' . $e->getMessage(), Log::ERROR, 'com_emundus.yousign');
			throw $e;
		}

		return $yousign_request;
	}

	private function mapPosition(string $position): array
	{
		$coordinate = [
			'x' => 20,
			'y' => 750
		];

		switch ($position)
		{
			case 'A1';
				$coordinate['x'] = 20;
				$coordinate['y'] = 50;
				break;
			case 'A2';
				$coordinate['x'] = 230;
				$coordinate['y'] = 50;
				break;
			case 'A3';
				$coordinate['x'] = 420;
				$coordinate['y'] = 50;
				break;
			case 'B1';
				$coordinate['x'] = 20;
				$coordinate['y'] = 350;
				break;
			case 'B2';
				$coordinate['x'] = 230;
				$coordinate['y'] = 350;
				break;
			case 'B3';
				$coordinate['x'] = 420;
				$coordinate['y'] = 350;
				break;
			case 'C1';
				$coordinate['x'] = 20;
				$coordinate['y'] = 750;
				break;
			case 'C2';
				$coordinate['x'] = 230;
				$coordinate['y'] = 750;
				break;
			case 'C3';
				$coordinate['x'] = 420;
				$coordinate['y'] = 750;
				break;
		}

		return $coordinate;
	}
}