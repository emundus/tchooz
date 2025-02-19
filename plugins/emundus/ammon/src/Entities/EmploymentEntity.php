<?php

namespace Joomla\Plugin\Emundus\Ammon\Entities;

class EmploymentEntity {
	public string $professionalPostNumber = '';
	public bool $disabled = false;

	public function __construct(
		public string $company, // Company internal reference
		public string $professionalEmail,
		public string $professionalPhoneNumber,
	) {
		if (empty($this->company)) {
			throw new \InvalidArgumentException('The employment company cannot be empty');
		}

		if (empty($this->professionalEmail)) {
			throw new \InvalidArgumentException('The professionalEmail cannot be empty');
		}

		if (empty($this->professionalPhoneNumber)) {
			throw new \InvalidArgumentException('The professionalPhoneNumber cannot be empty');
		}
	}
}