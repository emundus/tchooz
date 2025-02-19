<?php

namespace Joomla\Plugin\Emundus\Ammon\Factory;

use Joomla\Plugin\Emundus\Ammon\Entities\AdressEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\CompanyEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\EmploymentEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\UserEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\RegistrationEntity;

require_once(JPATH_SITE . '/components/com_emundus/mapper/ApiMapper.php');

class AmmonFactory
{
	public function __construct(private readonly string $fnum, private readonly array $configurations)
	{
		if (empty($this->fnum))
		{
			throw new \InvalidArgumentException('The fnum cannot be empty');
		}
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
			'company_' . $this->fnum
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
				$values['line2']
			);
		}

		return $address;
	}

	public function createEmploymentEntity(CompanyEntity $company): ?EmploymentEntity
	{
		$employment = null;

		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'employment';
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
				$values['professionalEmail'],
				$values['professionalPhoneNumber']
			);
		}

		return $employment;
	}

	public function createManagerEntity(EmploymentEntity $employmentEntity): UserEntity
	{
		$configurations = array_filter($this->configurations, function ($configuration) {
			return $configuration->action === 'create' && $configuration->name === 'manager';
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
			'manager_' . $this->fnum,
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
				$values['email'],
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
			$values['user_id'],
			[$adressEntity],
			$employmentEntity ? [$employmentEntity] : []
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

	public function createRegistrationEntity(UserEntity $applicant, $session_id, $company = null): RegistrationEntity
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
			$values['HistoryLog1Content'],
			!empty($company) ? $company->externalReference : null,
			$applicant->externalReference
		);
	}
}