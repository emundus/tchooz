<?php

namespace Joomla\Plugin\Task\Ammon\Repository;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\Plugin\Task\Ammon\Entities\CompanyEntity;
use Joomla\Plugin\Task\Ammon\Entities\UserEntity;
use Joomla\Plugin\Task\Ammon\Factory\AmmonFactory;
use Joomla\Plugin\Task\Ammon\Synchronizer\AmmonSynchronizer;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;

require_once(JPATH_SITE . '/components/com_emundus/models/sync.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/fabrik.php');

/**
 * Class AmmonRepository
 * Goal is to synchronise data between Emundus and Ammon, we need to register the file to the session in Ammon
 * @package Joomla\Plugin\Emundus\Ammon\Repository
 */

class AmmonRepository
{
	private string $fnum;
	private int $file_status = 0;
	private int $email_id_to_sales = 0;
	private int $ammon_session_id;
	private \EmundusModelSync $sync_model;
	private ?object $api;
	private array $configurations = [];
	private AmmonFactory $factory;
	private AmmonSynchronizer $synchronizer;
	private DatabaseDriver $db;
	private DispatcherInterface $dispatcher;

	public function __construct(string $fnum, int $ammon_session_id, int $file_status = 0, int $email_id_to_sales = 0)
	{
		$this->fnum = $fnum;
		$this->ammon_session_id = $ammon_session_id;
		$this->file_status = $file_status;
		$this->email_id_to_sales = $email_id_to_sales;
		Log::addLogger(['text_file' => 'plugin.emundus.ammon.php'], Log::ALL, array('plugin.emundus.ammon'));

		if (empty($this->fnum)) {
			throw new \InvalidArgumentException('The fnum cannot be empty');
		}

		if (empty($this->ammon_session_id)) {
			throw new \InvalidArgumentException('The ammon session id cannot be empty, it is needed to register the file to the session');
		}

		$this->sync_model = new \EmundusModelSync();
		$this->api = $this->sync_model->getApi(0, 'ammon');

		if (empty($this->api->id)) {
			Log::add('API not found, please check the configuration', Log::ERROR, 'plugin.emundus.ammon');
			throw new \InvalidArgumentException('API not found, please check the configuration');
		}

		$this->dispatcher = Factory::getApplication()->getDispatcher();
		$configurations = $this->api->params;
		$this->configurations = json_decode($configurations)->configurations;
		$this->factory = new AmmonFactory($this->fnum, $this->configurations);
		$this->synchronizer = new AmmonSynchronizer($this->api);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * We need to register the file to the session in Ammon
	 * A registration contains a user and a session
	 * If user has a company, we need to create it in Ammon
	 * If registration is BtoB, we need to create a user to specify who is the user doing the registration
	 *
	 * @param bool $force_new_user_if_not_found, in case where level distance is too short but not 0, we can decide to force the creation of a new user
	 *
	 * @return bool
	 */
	public function registerFileToSession(bool $force_new_user_if_not_found = false, bool $skip_company = false): bool
	{
		$registered = false;

		try {
			if (!$skip_company) {
				$company = $this->getOrCreateCompany();
				if (!empty($company)) {
					if ($this->companyIsPaying()) {
						Log::add('Company ' . $company->establishmentName . ' is paying a part for ' . $this->fnum, Log::INFO, 'plugin.emundus.ammon');

						$manager = $this->getCompanyManager($company);

						if (empty($manager)) {
							$manager = $this->createCompanyManager($company);

							if (empty($manager)) {
								throw new \Exception('Failed to create company manager in ammon.');
							}
						}

						$different_referee = \EmundusHelperFabrik::getValueByAlias('different_admin', $this->fnum);
						if ($different_referee['raw'] == 1) {
							Log::add('Need to create a different referee for '. $this->fnum, Log::INFO, 'plugin.emundus.ammon');

							$registration_referee = $this->getRegistrationReferee($company);
							if (empty($registration_referee)) {
								$registration_referee = $this->createRegistrationReferee($company);

								if (empty($registration_referee)) {
									throw new \Exception('Failed to create registration different referee in ammon.');
								} else {
									Log::add('Registration referee created successfully for ' . $this->fnum);
								}
							}
						} else {
							Log::add('No need to create a different referee for ' . $this->fnum, Log::INFO, 'plugin.emundus.ammon');
						}
					} else {
						Log::add('Company ' . $company->establishmentName . ' is not paying for ' . $this->fnum, Log::INFO, 'plugin.emundus.ammon');
					}
				}
			} else {
				$company = null;
			}

			$applicant = $this->getOrCreateApplicant($force_new_user_if_not_found);
			if (empty($applicant)) {
				throw new \Exception('Failed to create applicant in ammon.');
			}

			$registration = $this->factory->createRegistrationEntity($applicant, $this->ammon_session_id, $company);
			Log::add('Registration for fnum  ' . $this->fnum . ' ' . json_encode($registration), Log::INFO, 'plugin.emundus.ammon');

			$registered = $this->synchronizer->createRegistration($registration);
			if ($registered)
			{
				Log::add('Registration for fnum ' . $this->fnum . ' created successfully', Log::INFO, 'plugin.emundus.ammon');
			}
			else
			{
				$this->factory->deleteReference($registration->ExternalReference);
				Log::add('Error when trying to create registration for fnum ' . $this->fnum, Log::ERROR, 'plugin.emundus.ammon');
			}
		} catch (\Exception $e) {
			Log::add('Error when trying to create registration for fnum ' . $this->fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			throw new \Exception($e->getMessage());
		}

		return $registered;
	}

	private function companyIsPaying(): bool
	{
		$paying = false;

		$value = \EmundusHelperFabrik::getValueByAlias('registration_company_price', $this->fnum);
		if (!empty($value) && !empty($value['raw'])) {
			$price = str_replace(' ', '', $value['raw']);
			$price = str_replace(',', '.', $price);
			$price = floatval($price);

			if ($price > 0) {
				$paying = true;
			}
		}

		return $paying;
	}

	private function getOrCreateCompany(): ?CompanyEntity
	{
		$company = null;
		$siret = $this->getSiret($this->fnum); // Company is required only if siret is not empty

		if (!empty($siret)) {
			Log::add('Found siret ' . $siret . ' for company for fnum ' . $this->fnum, Log::INFO, 'plugin.emundus.ammon');
			$company = $this->getCompany($this->fnum, $siret);

			if (empty($company)) {
				Log::add('No company found from ammon for ' . $siret . ' for company for fnum ' . $this->fnum . '. Attempt to create one.', Log::INFO, 'plugin.emundus.ammon');

				try {
					$address = $this->factory->createCompanyAdressEntity();
					$company = $this->factory->createCompanyEntity($address);

					$created = $this->synchronizer->createCompany($company);

					if ($created) {
						Log::add('Company ' . $company->establishmentName . ' created successfully for fnum ' . $this->fnum, Log::INFO, 'plugin.emundus.ammon');
					} else {
						Log::add('Error when trying to create company ' . $company->establishmentName . ' for fnum ' . $this->fnum, Log::ERROR, 'plugin.emundus.ammon');
					}
				} catch (\Exception $e) {
					Log::add('Error when trying to create company ' . $e->getMessage() .  ' for fnum ' . $this->fnum , Log::ERROR, 'plugin.emundus.ammon');
				}
			}
		}

		return $company;
	}

	private function getSiret(string $fnum): string
	{
		$siret = '';

		if (!empty($fnum)) {
			$value = \EmundusHelperFabrik::getValueByAlias('company_siret', $fnum);

			if (!empty($value)) {
				$siret = $value['raw'];
			}
		}

		return $siret;
	}

	/**
	 * @param string $fnum
	 * @param string $siret optional
	 */
	private function getCompany(string $fnum, string $siret = ''): ?CompanyEntity
	{
		$company = null;

		if (empty($siret) && !empty($fnum))
		{
			$siret = $this->getSiret($fnum);
		}

		if (!empty($siret))
		{
			Log::add('Trying to find company for siret ' . $siret . ' and fnum ' . $fnum, Log::INFO, 'plugin.emundus.ammon');

			$ammon_company = $this->synchronizer->getCompany($siret);
			if (!empty($ammon_company))
			{
				Log::add('Found company from ammon for siret ' . $siret . ' for fnum ' . $fnum, Log::INFO, 'plugin.emundus.ammon');
				$company = $this->factory->createCompanyEntityFromAmmon($ammon_company);
			}
		}

		return $company;
	}

	private function getCompanyManager(CompanyEntity $company): ?UserEntity
	{
		$user = null;

		try
		{
			$employmentEntity = $this->factory->createEmploymentEntity($company, 'manager');
			$managerEntity    = $this->factory->createManagerEntity($employmentEntity);

			if (!empty($managerEntity->lastName) && !empty($managerEntity->firstName)) {
				$ammon_user = $this->synchronizer->getUserFromName($managerEntity->lastName, $managerEntity->firstName);

				if (!empty($ammon_user))
				{
					$user = $this->factory->createManagerEntityFromAmmon($ammon_user);
					$this->factory->deleteReference($managerEntity->externalReference);
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Failed to get company manager user entity ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $user;
	}

	private function getRegistrationReferee(CompanyEntity $company): ?UserEntity
	{
		$user = null;

		try
		{
			$employmentEntity = $this->factory->createEmploymentEntity($company, 'referee');
			$refereeEntity    = $this->factory->createRefereeEntity($employmentEntity);

			if (!empty($refereeEntity->lastName) && !empty($refereeEntity->firstName)) {
				$ammon_user = $this->synchronizer->getUserFromName($refereeEntity->lastName, $refereeEntity->firstName);

				if (!empty($ammon_user))
				{
					$user = $this->factory->createManagerEntityFromAmmon($ammon_user);
					$this->factory->deleteReference($refereeEntity->externalReference);
				}
			}
		}
		catch (\Exception $e)
		{
			Log::add('Failed to get company manager user entity ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
		}

		return $user;
	}

	private function createCompanyManager(CompanyEntity $company): ?UserEntity
	{
		$user = null;

		$employmentEntity = $this->factory->createEmploymentEntity($company, 'manager');
		$managerEntity = $this->factory->createManagerEntity($employmentEntity);
		$created = $this->synchronizer->createUser($managerEntity);

		if ($created) {
			$user = $managerEntity;
		} else {
			Log::add('Error when trying to create manager for company ' . $company->establishmentName, Log::ERROR, 'plugin.emundus.ammon');
		}

		return $user;
	}

	private function createRegistrationReferee(CompanyEntity $company): ?UserEntity
	{
		$user = null;

		$employmentEntity = $this->factory->createEmploymentEntity($company, 'referee');
		$refereeEntity = $this->factory->createRefereeEntity($employmentEntity);
		$created = $this->synchronizer->createUser($refereeEntity);

		if ($created) {
			$user = $refereeEntity;
		} else {
			Log::add('Error when trying to create referee for company ' . $company->establishmentName, Log::ERROR, 'plugin.emundus.ammon');
		}

		return $user;
	}

	private function getOrCreateApplicant(bool $force_new_user_if_not_found = false): ?UserEntity
	{
		$applicant = $this->getApplicant($this->fnum, $force_new_user_if_not_found);

		if (empty($applicant)) {
			if (!$this->isFileCreatedFromBtoB($this->fnum)) {
				$address = $this->factory->createUserAddressEntity();
			} else {
				$address = $this->factory->createCompanyAdressEntity();
			}
			$company = $this->getCompany($this->fnum);

			$applicantEntity = null;
			if (!empty($company)) {
				$employment = $this->factory->createEmploymentEntity($company);

				if (!empty($employment)) {
					$applicantEntity = $this->factory->createUserEntity($address, $employment);
				}
			} else {
				$applicantEntity = $this->factory->createUserEntity($address);
			}

			if (!empty($applicantEntity)) {
				$created = $this->synchronizer->createUser($applicantEntity);

				$onAfterAmmonApplicantCreate = new GenericEvent('onAfterAmmonApplicantCreate', ['fnum' => $this->fnum, 'session_id' => $this->ammon_session_id, 'ref' => $applicantEntity->externalReference, 'status' => $created]);
				$this->dispatcher->dispatch('onAfterAmmonApplicantCreate', $onAfterAmmonApplicantCreate);

				if ($created) {
					$applicant = $applicantEntity;
					Log::add('User for fnum ' . $this->fnum . ' created successfully', Log::INFO, 'plugin.emundus.ammon');
				} else {
					Log::add('Error when trying to create user for fnum ' . $this->fnum, Log::ERROR, 'plugin.emundus.ammon');
				}
			} else {
				Log::add('Error when trying to create user for fnum ' . $this->fnum, Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $applicant;
	}

	private function getApplicant(string $fnum, bool $force_new_user_if_not_found = false): ?UserEntity
	{
		$user = null;

		if (!empty($fnum)) {
			$firstname = '';
			$lastname = '';

			try {
				$firstname = \EmundusHelperFabrik::getValueByAlias('registration_first_name', $fnum)['raw'];
				$lastname = \EmundusHelperFabrik::getValueByAlias('registration_common_name', $fnum)['raw'];
				$birthdate = \EmundusHelperFabrik::getValueByAlias('registration_date_of_birth', $fnum)['raw'];
				$ammon_user = $this->synchronizer->getUser($lastname, $firstname, $birthdate, $force_new_user_if_not_found);

				if (!empty($ammon_user)) {
					$user = $this->factory->createUserEntityFromAmmon($ammon_user);
				}
			} catch (\Exception $e) {
				if (str_starts_with($e->getMessage(), '[SHORT_LEV_DISTANCE]')) {
					$matches = [];
					preg_match('/\[FOUND_USERNAME="(.*)"\]/', $e->getMessage(), $matches);
					$found_name = explode(' ', $matches[1]);


					PluginHelper::importPlugin('emundus');
					$this->dispatcher->dispatch('onAmmonFoundSimilarName', new GenericEvent('onAmmonFoundSimilarName', [
						'fnum' => $fnum,
						'name' => $lastname . ' ' . $firstname,
						'found_name' => $found_name,
						'message' => $e->getMessage(),
						'retry' => true,
						'retry_event' => 'onAfterStatusChange',
						'retry_event_parameters' => [
							'status' => $this->file_status,
							'fnum' => $fnum,
							'force_new_user_if_not_found' => true
						]
					]));
					$onAmmonFoundSimilarNameEventHandler = new GenericEvent(
						'onCallEventHandler',
						[
							'onAmmonFoundSimilarName', [
								'fnum' => $this->fnum,
								'name' => $lastname . ' ' . $firstname,
								'found_name' => $found_name,
								'message' => $e->getMessage()
							]
						]
					);
					$this->dispatcher->dispatch('onCallEventHandler', $onAmmonFoundSimilarNameEventHandler);

					$sent = $this->sendEmailToSalesReferents($fnum, $lastname . ' ' .$firstname, $found_name);

					if (!$sent) {
						Log::add('Failed to send email to sales referents for ' . $fnum . ' after similar name was found.', Log::WARNING, 'plugin.emundus.ammon');
					}

					throw new \Exception($e->getMessage());
				}

				Log::add('Failed to get applicant user entity ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $user;
	}

	private function isFileCreatedFromBtoB(string $fnum): bool
	{
		$is_BtoB = false;

		if (!empty($fnum)) {
			$query = $this->db->getQuery(true);

			$query->select('id')
				->from('#__emundus_btob_1237_repeat')
				->where('fnum = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$result = $this->db->loadResult();

				if (!empty($result)) {
					$is_BtoB = true;
					Log::add('File ' . $fnum . ' is created from BtoB', Log::INFO, 'plugin.emundus.ammon');
				}
			} catch (\Exception $e) {
				Log::add('Failed to check if file is created from BtoB ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $is_BtoB;
	}

	private function sendEmailToSalesReferents(string $fnum, string $file_name, $found_name): bool
	{
		$sent = false;

		if (!empty($this->email_id_to_sales) && !empty($fnum) && !empty($found_name)) {
			$query = $this->db->createQuery();
			$query->select('u.email')
				->from($this->db->quoteName('#__users', 'u'))
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes_repeat_sales_referents', 'sales'), 'sales.sales_referents = u.id')
				->leftJoin($this->db->quoteName('#__emundus_setup_programmes', 'esp'), 'esp.id = sales.parent_id')
				->leftJoin($this->db->quoteName('#__emundus_setup_campaigns', 'esc'), 'esc.training = esp.code')
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc'), 'ecc.campaign_id = esc.id')
				->where('ecc.fnum = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$email_addresses = $this->db->loadColumn();

				if (!empty($email_addresses)) {
					if (!class_exists('EmundusModelEmails')) {
						require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
					}

					$m_emails = new \EmundusModelEmails();

					$sending_status = [];
					foreach ($email_addresses as $email_address) {
						$sending_status[] = $m_emails->sendEmailNoFnum($email_address, $this->email_id_to_sales, [
							'FNUM' => $fnum,
							'APPLICANT_NAME' => $file_name,
							'FOUND_NAME' => $found_name
						]);
					}
					$sent = !in_array(false, $sending_status);
				}
			} catch (\Exception $e) {
				Log::add('Failed to get sales referents email addresses for ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $sent;
	}
}