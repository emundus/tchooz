<?php

namespace Joomla\Plugin\Emundus\Ammon\Factory;

use Joomla\CMS\Log\Log;
use Joomla\Plugin\Emundus\Ammon\Entities\AdressEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\CompanyEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\EmploymentEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\UserEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\RegistrationEntity;
use Joomla\CMS\Factory;

require_once(JPATH_SITE . '/components/com_emundus/mapper/ApiMapper.php');

class AmmonFactory
{
	public function __construct(private readonly string $fnum, private readonly array $configurations)
	{
		if (empty($this->fnum))
		{
			throw new \InvalidArgumentException('The fnum cannot be empty');
		}
		Log::addLogger(['text_file' => 'plugin.emundus.ammon.php'], Log::ALL, array('plugin.emundus.ammon'));
	}

	public function createCompanyAdressEntity(): AdressEntity
	{
		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'companyaddress';
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('Company address configuration not found');
		}

		$mapper         = new \ApiMapper(current($configurations), $this->fnum);
		$values         = $mapper->setMappingFromFnum();

		return new AdressEntity(
			974,
			'PR',
			'Principale',
			$values['addressee'],
			$values['city'],
			$values['countryCode'],
			$values['postcode'],
			$values['line1'],
			$values['line2']
		);
	}

	public function createCompanyEntity(AdressEntity $address): CompanyEntity
	{
		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'company';
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('Company configuration not found');
		}

		$mapper         = new \ApiMapper(current($configurations), $this->fnum);
		$values         = $mapper->setMappingFromFnum();

		if (empty($values['establishmentName']) || empty($values['registrationSIRET']))
		{
			throw new \InvalidArgumentException('Company fields cannot be empty');
		}

		return new CompanyEntity(
			$values['establishmentName'],
			"E",
			$values['registrationSIRET'],
			[$address],
			"SGE,CLI",
			true,
			$this->generateExternalReference('EMUNDUS_COMPANY', $values['establishmentName'])
		);
	}

	public function createCompanyEntityFromAmmon($ammon_company): CompanyEntity
	{
		$company = null;

		if (!empty($ammon_company))
		{
			$address = new AdressEntity(
				974,
				'PR',
				'Principale',
				$ammon_company->cdesti,
				$ammon_company->cville,
				$ammon_company->cpays,
				$ammon_company->ccp,
				$ammon_company->cadR1
			);

			$company = new CompanyEntity(
				$ammon_company->cnome,
				"E",
				$ammon_company->csiret,
				[$address],
				"SGE,CLI",
				true,
				$ammon_company->centext
			);
		}

		return $company;
	}

	public function createUserAddressEntity(): ?AdressEntity
	{
		$address = null;

		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'useraddress';
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('User address configuration not found');
		}

		$mapper = new \ApiMapper(current($configurations), $this->fnum);
		$values = $mapper->setMappingFromFnum();

		if (!empty($values)) {
			$address = new AdressEntity(
				0,
				'PERS',
				'Personnelle',
				$values['addressee'],
				$values['city'],
				$values['countryCode'],
				$values['postcode'],
				$values['line1'],
				$values['line2'],
				$values['Email'] ?? '',
				$values['Phone'] ?? ''
			);
		}

		return $address;
	}

	public function createEmploymentEntity(CompanyEntity $company, string $collection = 'user'): ?EmploymentEntity
	{
		$employment = null;

		$configurations = array_filter($this->configurations, function ($configuration) use ($collection) {
			return $configuration->action === 'create' && $configuration->name === 'employment' && $configuration->collectionname === $collection;
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('Employment configuration not found');
		}

		$mapper = new \ApiMapper(current($configurations), $this->fnum);
		$values = $mapper->setMappingFromFnum();

		if (!empty($values)) {
			$employment = new EmploymentEntity(
				$company->externalReference,
				$values['professionalEmail'] ?? '',
				$values['professionalPhoneNumber'] ?? '',
				$values['professionalPostNumber'] ?? '',
				$collection
			);
		}

		return $employment;
	}

	public function createManagerEntity(EmploymentEntity $employmentEntity): UserEntity
	{
		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'manager' && $configuration->collectionname === 'user';
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('User configuration not found');
		}

		$mapper = new \ApiMapper(current($configurations), $this->fnum);
		$values = $mapper->setMappingFromFnum();

		return new UserEntity(
			$values['firstName'],
			$values['lastName'],
			'',
			$values['CivilStatusCode'],
			$values['GenderCode'],
			'',
			'',
			'',
			'INT',
			$this->generateExternalReference('EMUNDUS_USER', $values['email']),
			[],
			[$employmentEntity]
		);
	}

	public function createRefereeEntity(EmploymentEntity $employmentEntity): UserEntity
	{
		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'referee' && $configuration->collectionname === 'user';
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('User configuration not found');
		}

		$mapper = new \ApiMapper(current($configurations), $this->fnum);
		$values = $mapper->setMappingFromFnum();

		return new UserEntity(
			$values['firstName'],
			$values['lastName'],
			'',
			$values['CivilStatusCode'],
			$values['GenderCode'],
			'',
			'',
			'',
			'INT',
			$this->generateExternalReference('EMUNDUS_USER', $values['email']),
			[],
			[$employmentEntity]
		);
	}

	public function createManagerEntityFromAmmon($ammon_user): ?UserEntity
	{
		$user_entity = null;

		if (!empty($ammon_user)) {
			$configurations = array_filter($this->configurations, function ($configuration) {
				return $configuration->action === 'create' && $configuration->name === 'manager';
			});

			if (empty($configurations))
			{
				throw new \InvalidArgumentException('User configuration not found');
			}

			$mapper = new \ApiMapper(current($configurations), $this->fnum);
			$values = $mapper->setMappingFromFnum();

			$user_entity = new UserEntity(
				$ammon_user->cprenom,
				$ammon_user->cnom,
				!empty($ammon_user->xnaiss) ? $ammon_user->xnaiss : '',
				!empty($ammon_user->csexe) ? $ammon_user->csexe : $values['CivilStatusCode'],
				$values['GenderCode'],
				'',
				'',
				'',
				'INT',
				$ammon_user->centext,
				[],
				[]
			);
		}

		return $user_entity;
	}

	public function createUserEntity(AdressEntity $adressEntity, ?EmploymentEntity $employmentEntity = null): UserEntity
	{
		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'user';
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('User configuration not found');
		}

		$mapper = new \ApiMapper(current($configurations), $this->fnum);
		$values = $mapper->setMappingFromFnum();

		return new UserEntity(
			$values['firstName'],
			$values['lastName'],
			$values['birthDate'],
			$values['CivilStatusCode'],
			$values['GenderCode'],
			$values['NationalityCode'],
			$values['BirthCountryCode'],
			$values['BirthCity'],
			'PAR,INT,PARTM',
			$this->generateExternalReference('EMUNDUS_USER',  $values['user_id']),
			[$adressEntity],
			!empty($employmentEntity) ? [$employmentEntity] : [],
			$values['maidenName'] ?? ''
		);
	}

	public function createUserEntityFromAmmon($ammon_user): ?UserEntity
	{
		$user_entity = null;

		if (!empty($ammon_user)) {
			$configurations = array_filter($this->configurations, function ($configuration) {
				return $configuration->action === 'create' && $configuration->name === 'user';
			});

			if (empty($configurations))
			{
				throw new \InvalidArgumentException('User configuration not found');
			}

			$mapper = new \ApiMapper(current($configurations), $this->fnum);
			$values = $mapper->setMappingFromFnum();

			$user_entity = new UserEntity(
				$ammon_user->cprenom,
				$ammon_user->cnom,
				!empty($ammon_user->xnaiss) ? $ammon_user->xnaiss : '',
				!empty($ammon_user->csexe) ? $ammon_user->csexe : $values['CivilStatusCode'],
				$values['GenderCode'],
				$values['NationalityCode'],
				$values['BirthCountryCode'],
				$values['BirthCity'],
				'PAR,INT,PARTM',
				$values['user_id'],
				[],
				[]
			);
		}

		return $user_entity;
	}

	/**
	 * @param   UserEntity  $applicant
	 * @param   int         $session_id
	 * @param   mixed       $company
	 *
	 * @return RegistrationEntity
	 * @throws \Exception
	 */
	public function createRegistrationEntity(UserEntity $applicant, int $session_id, ?CompanyEntity $company = null): RegistrationEntity
	{
		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'registration';
		});

		if (empty($configurations))
		{
			throw new \InvalidArgumentException('Registration configuration not found');
		}

		$mapper = new \ApiMapper(current($configurations), $this->fnum);
		$values = $mapper->setMappingFromFnum();

		return new RegistrationEntity(
			$applicant->externalReference,
			$this->fnum,
			$session_id,
			3,
			!empty($values['HistoryLog1Content']) ? 'AMENAGEMENT' : '',
			$values['HistoryLog1Content'] ?? '',
			!empty($company) ? $company->externalReference : '',
			$this->generateExternalReference('EMUNDUS_REGISTRATION', $this->fnum)
		);
	}

	/**
	 * @param   string  $prefix
	 * @param $internal_reference
	 *
	 * @return string
	 */
	private function generateExternalReference(string $prefix, $internal_reference): string
	{
		$externalReference = '';

		if (!empty($internal_reference) && !empty($prefix)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->insert($db->quoteName('#__emundus_ammon_external_references'))
				->columns($db->quoteName('internal_reference') . ', ' . $db->quoteName('type'). ', ' . $db->quoteName('created'))
				->values($db->quote($internal_reference). ', ' . $db->quote($prefix) . ', ' . $db->quote(date('Y-m-d H:i:s')));

			try {
				$db->setQuery($query);
				$db->execute();
				$row_id = $db->insertid();

				switch($prefix) {
					case 'EMUNDUS_USER':
					case 'EMUNDUS_COMPANY':
						$externalReference = $prefix . '_' . $row_id;
						break;
					case 'EMUNDUS_REGISTRATION':
						$externalReference = $prefix . '_' . $this->fnum;
						break;
				}

				$query->clear()
					->update($db->quoteName('#__emundus_ammon_external_references'))
					->set($db->quoteName('external_reference') . ' = ' . $db->quote($externalReference))
					->where($db->quoteName('id') . ' = ' . $db->quote($row_id));

				$db->setQuery($query);
				$db->execute();
			} catch (\Exception $e) {
				Log::add('Failed to generate external reference : ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
				throw new \InvalidArgumentException('Error while generating external reference');
			}
		}

		return $externalReference;
	}

	/**
	 * @param $reference
	 *
	 * @return bool
	 */
	public function deleteReference($reference): bool
	{
		$deleted = false;

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->delete($db->quoteName('#__emundus_ammon_external_references'))
			->where($db->quoteName('external_reference') . ' = ' . $db->quote($reference));

		try {
			$db->setQuery($query);
			$deleted = $db->execute();
		} catch (\Exception $e) {
			Log::add('Failed to delete external reference : ' . $e->getMessage(), Log::ERROR, 'plugin.emundus.ammon');
		}

		return $deleted;
	}
}