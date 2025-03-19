<?php

namespace Joomla\Plugin\Task\Ammon\Entities;

class CompanyEntity
{
	public function __construct(
		public string $establishmentName,
		public string $establishmentType,
		public string $registrationSIRET,
		/** @var AdressEntity[] */
		public array $addresses,
		public string $categoriesCodes = '',
		public bool $Headquarter = false,
		public string $externalReference = ''
	)
	{
		if (empty($this->establishmentName)) {
			throw new \InvalidArgumentException('The establishmentName cannot be empty');
		}

		if (empty($this->addresses)) {
			throw new \InvalidArgumentException('The addresses cannot be empty');
		}

		if (empty($this->externalReference)) {
			throw new \InvalidArgumentException('The externalReference cannot be empty');
		}
	}
}