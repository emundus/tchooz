<?php

namespace Joomla\Plugin\Emundus\Ammon\Entities;

class AdressEntity
{
	public bool $postalAdress = true;
	public bool $blackListedMail = false;
	public bool $bouncedEmail = false;
	public bool $internetContact = false;
	public bool $disabled = false;

	public function __construct(
		public int $typeId = 0,
		public string $typeCode = '',
		public string $type = '',
		public string $addressee,
		public string $city,
		public string $countryCode,
		public string $postcode,
		public string $line1,
		public string $line2 = '',
		public string $Email = '',
		public string $Phone = ''
	) {
		if (empty($this->addressee)) {
			throw new \InvalidArgumentException('The addressee cannot be empty');
		}

		if (empty($this->city)) {
			throw new \InvalidArgumentException('The city cannot be empty');
		}

		if (empty($this->countryCode)) {
			throw new \InvalidArgumentException('The countryCode cannot be empty');
		}

		if (empty($this->postcode)) {
			throw new \InvalidArgumentException('The postcode cannot be empty');
		}

		if (empty($this->line1)) {
			throw new \InvalidArgumentException('The line1 cannot be empty');
		}
	}
}