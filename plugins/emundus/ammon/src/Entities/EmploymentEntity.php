<?php

namespace Joomla\Plugin\Emundus\Ammon\Entities;

class EmploymentEntity {
	public bool $disabled = false;

	public function __construct(
		public string $company, // Company internal reference
		public string $professionalEmail,
		public string $professionalPhoneNumber,
		public string $professionalPostNumber = '',
		$context = '',
	) {
		if (empty($this->company)) {
			throw new \InvalidArgumentException('The employment company cannot be empty for the ' . $context);
		}

		if (empty($this->professionalEmail)) {
			throw new \InvalidArgumentException('The professionalEmail cannot be empty for the ' . $context);
		}

		if (empty($this->professionalPhoneNumber)) {
			throw new \InvalidArgumentException('The professionalPhoneNumber cannot be empty for the ' . $context);
		}
	}
}