<?php

namespace Tchooz\Entities\SMS;

class ReceiverEntity
{
	public function __construct(
		public string $phone_number,
		public int $ccid = 0,
		public int $user_id = 0
	) {
		$this->phone_number = $this->formatPhoneNumber($phone_number);
	}

	private function formatPhoneNumber(string $phone_number): string
	{
		if (!empty($phone_number)) {
			$phone_number = preg_replace('/[^+0-9]/', '', $phone_number);
		}

		return $phone_number;
	}

	public function getUserId(): string
	{
		return $this->user_id;
	}

	public function getFnum(): string
	{
		return !empty($this->ccid) ? \EmundusHelperFiles::getFnumFromId($this->ccid) :  '';
	}
}