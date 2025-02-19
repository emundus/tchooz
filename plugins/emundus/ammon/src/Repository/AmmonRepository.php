<?php

namespace Joomla\Plugin\Emundus\Ammon\Repository;

use Google\Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\Plugin\Emundus\Ammon\Entities\CompanyEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\UserEntity;
use Joomla\Plugin\Emundus\Ammon\Factory\AmmonFactory;
use Joomla\Plugin\Emundus\Ammon\Synchronizer\AmmonSynchronizer;
use Joomla\CMS\Event\GenericEvent;

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

	private int $ammon_session_id;
	private $sync_model = null;

	private $api;

	private array $configurations = [];

	private AmmonFactory $factory;
	private AmmonSynchronizer $synchronizer;

	private DatabaseDriver $db;

	public function __construct(string $fnum, int $ammon_session_id, int $file_status = 0)
	{
		$this->fnum = $fnum;
		$this->ammon_session_id = $ammon_session_id;
		$this->file_status = $file_status;
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
	public function registerFileToSession(bool $force_new_user_if_not_found = false): bool
	{
		$registered = false;

		$company = $this->getOrCreateCompany();
		if (!empty($company)) {
			$manager = $this->getCompanyManager($this->fnum);

			if (empty($manager)) {
				$manager = $this->createCompanyManager($company);
			}
		}

		try {
			$applicant = $this->getOrCreateApplicant($force_new_user_if_not_found);
			$registration = $this->factory->createRegistrationEntity($applicant, $this->ammon_session_id, $company);
			$registered = $this->synchronizer->createRegistration($registration);
			if ($registered)
			{
				Log::add('Registration for fnum ' . $this->fnum . ' created successfully', Log::INFO, 'plugin.emundus.ammon');
			}
			else
			{
				Log::add('Error when trying to create registration for fnum ' . $this->fnum, Log::ERROR, 'plugin.emundus.ammon');
			}
		} catch (\Exception $e) {
			Log::add('Error when trying to create registration for fnum ' . $this->fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
		}

		return $registered;
	}

	private function getOrCreateCompany(): ?CompanyEntity
	{
		$company = null;
		$siret = $this->getSiret($this->fnum); // Company is required only if siret is not empty

		if (!empty($siret)) {
			$company = $this->getCompany($this->fnum, $siret);

			if (empty($company)) {
				try {
					$address = $this->factory->createCompanyAdressEntity();
					$company = $this->factory->createCompanyEntity($address);

					$created = $this->synchronizer->createCompany($company);

					if ($created) {
						Log::add('Company ' . $company->establishmentName . ' created successfully for fnum ' . $this->fnum, Log::INFO, 'plugin.emundus.ammon');
					} else {
						Log::add('Error when trying to create company ' . $company->establishmentName . ' for fnum ' . $this->fnum, Log::ERROR, 'plugin.emundus.ammon');
					}
				} catch (Exception $e) {
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
			$ammon_company = $this->synchronizer->getCompany($siret);
			if (!empty($ammon_company))
			{
				$company = $this->factory->createCompanyEntityFromAmmon($ammon_company);
			}
		}

		return $company;
	}

	private function getCompanyManager($fnum): ?UserEntity
	{
		$user = null;

		if (!empty($fnum)) {
			$query = $this->db->createQuery();
			$query->select('e_859_8215 as lastname, e_859_8216 as firstname')
				->from($this->db->quoteName('#__emundus_1011_03'))
				->where('fnum = ' . $this->db->quote($fnum));

			try {
				$this->db->setQuery($query);
				$user_infos = $this->db->loadAssoc();

				if (!empty($user_infos)) {
					$ammon_user = $this->synchronizer->getUserFromName($user_infos['lastname'], $user_infos['firstname']);

					if (!empty($ammon_user)) {
						$user = $this->factory->createManagerEntityFromAmmon($ammon_user);
					}
				}
			} catch (Exception $e) {
				Log::add('Failed to get company manager user entity ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $user;
	}

	private function createCompanyManager(CompanyEntity $company): ?UserEntity
	{
		$user = null;

		$employmentEntity = $this->factory->createEmploymentEntity($company);
		$managerEntity = $this->factory->createManagerEntity($employmentEntity);
		$created = $this->synchronizer->createUser($managerEntity);

		if ($created) {
			$user = $managerEntity;
		} else {
			Log::add('Error when trying to create manager for company ' . $company->establishmentName, Log::ERROR, 'plugin.emundus.ammon');
		}

		return $user;
	}

	private function getOrCreateApplicant(bool $force_new_user_if_not_found = false): UserEntity
	{
		$applicant = $this->getApplicant($this->fnum, $force_new_user_if_not_found);
		if (empty($applicant)) {
			$address = $this->factory->createUserAddressEntity();
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

				if ($created) {
					$onAfterAmmonApplicantCreate = new GenericEvent('onAfterAmmonApplicantCreate', ['fnum' => $this->fnum, 'session_id' => $this->ammon_session_id, 'ref' => $applicantEntity->externalReference]);
					$this->dispatcher->dispatch('onAfterAmmonApplicantCreate', $onAfterAmmonApplicantCreate);

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
			$query = $this->db->createQuery();

			$query->select('eu.firstname, eu.lastname')
				->from($this->db->quoteName('#__emundus_users', 'eu'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ecc.applicant_id = eu.user_id')
				->where('ecc.fnum = ' . $this->db->quote($fnum));
			try {
				$this->db->setQuery($query);
				$user_infos = $this->db->loadObject();

				$birthdate = \EmundusHelperFabrik::getValueByAlias('registration_date_of_birth', $fnum)['raw'];
				$ammon_user = $this->synchronizer->getUser($user_infos->lastname, $user_infos->firstname, $birthdate, $force_new_user_if_not_found);

				if (!empty($ammon_user)) {
					$user = $this->factory->createUserEntityFromAmmon($ammon_user);
				}
			} catch (\Exception $e) {
				if (str_starts_with($e->getMessage(), '[SHORT_LEV_DISTANCE]')) {
					// todo: send an email to sales referent

					$this->dispatcher->dispatch('onAmmonFoundSimilarName', new GenericEvent('onAmmonFoundSimilarName', [
						'fnum' => $fnum,
						'name' => $user_infos->lastname . ' ' . $user_infos->firstname,
						'message' => $e->getMessage(),
						'retry' => true,
						'retry_event' => 'onAfterStatusChange',
						'retry_event_parameters' => [
							'status' => $this->file_status,
							'fnum' => $fnum,
							'force_new_user_if_not_found' => true
						]
					]));

					throw new \Exception($e->getMessage());
				}

				Log::add('Failed to get applicant user entity ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $user;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return bool
	 */
	public function saveAmmonRegistration(string $fnum): bool
	{
		$saved = false;

		if (!empty($fnum)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->update($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->set($db->quoteName('ecc.registered_in_ammon') . ' = 1')
				->where($db->quoteName('ecc.fnum') . ' = ' . $db->quote($fnum));

			try {
				$db->setQuery($query);
				$saved = $db->execute();
			} catch (Exception $e) {
				Log::add('Failed to update registration for fnum ' . $fnum . ' ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
			}
		}

		return $saved;
	}
}