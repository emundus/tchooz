<?php
namespace Joomla\Plugin\Task\Ammon\Entities;

class UserEntity
{
	public string $birthDepartmentCode = '';
	public string $healthCareReference = '';

	public function __construct(
		public string $firstName,
		public string $lastName,
		public string $birthDate,
		public string $CivilStatusCode, // valeurs possibles M. ou Mme
		public string $GenderCode, // valeurs possibles M ou F
		public string $NationalityCode,
		public string $BirthCountryCode,
		public string $BirthCity,
		public string $categoriesCodes,
		public string $externalReference,

		/** @var AdressEntity[] */
		public array $addresses,
		/** @var EmploymentEntity[] */
		public array $employments,
		public string $maidenName = '',

	) {
		if (!empty($this->birthDate)) {
			$this->birthDate = date('Y-m-d\TH:i:s\Z', strtotime(str_replace('/', '-', $this->birthDate)));
		}
		$this->maidenName = !empty($maidenName) ? $maidenName : $this->lastName;
	}
}