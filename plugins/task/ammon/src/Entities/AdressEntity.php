<?php

namespace Joomla\Plugin\Task\Ammon\Entities;

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
		public string $addressee = '',
		public string $city = '',
		public string $countryCode = '',
		public string $postcode = '',
		public string $line1 = '',
		public string $line2 = '',
		public string $Email = '',
		public string $Phone = ''
	) {
		if (empty($this->addressee)) {
			throw new \InvalidArgumentException('The addressee cannot be empty');
		}

		if (!empty($this->postcode)) {
			$this->postcode = preg_replace('/[^0-9]/', '', $this->postcode);

			if (strlen($this->postcode) < 5) {
				$this->postcode = str_pad($this->postcode, 5, '0', STR_PAD_LEFT);
			}
		}
	}
}