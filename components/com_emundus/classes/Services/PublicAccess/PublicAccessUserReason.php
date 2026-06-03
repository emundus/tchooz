<?php

namespace Tchooz\Services\PublicAccess;

use Egulias\EmailValidator\Result\Reason\DetailedReason;

class PublicAccessUserReason extends DetailedReason
{

	public function __construct()
	{
		parent::__construct('This email is associated to the public system user, no email can be sent to him');
	}

	/**
	 * @inheritDoc
	 */
	public function code(): int
	{
		return 1000;
	}

	/**
	 * @inheritDoc
	 */
	public function description(): string
	{
		return 'This email is associated to the public system user, no email can be sent to him';
	}
}