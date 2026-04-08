<?php

namespace Tchooz\Services\PublicAccess;

use Egulias\EmailValidator\EmailLexer;
use Egulias\EmailValidator\Result\InvalidEmail;
use Egulias\EmailValidator\Result\Reason\EmptyReason;
use Egulias\EmailValidator\Validation\EmailValidation;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;

/**
 * Public access user should never receive emails, this validation checks if the email is the same as the one of the public access user and if it is it returns an error
 */
class PublicAccessValidation implements EmailValidation
{
	private ?InvalidEmail $error = null;
	private array $warnings = [];


	public function isValid(string $email, EmailLexer $emailLexer): bool
	{
		$valid = true;

		if (empty($email))
		{
			$this->warnings[] = new InvalidEmail(new EmptyReason(), '');
			$valid = false;
		}

		$systemUserId = (int) ComponentHelper::getParams('com_emundus')->get('system_public_user_id', 0);
		if (!empty($systemUserId))
		{
			$systemUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($systemUserId);

			if ($systemUser->email === $email)
			{
				$this->error = new InvalidEmail(new PublicAccessUserReason(), '');
				$valid = false;
			}
		}


		return $valid;
	}

	public function getError(): ?InvalidEmail
	{
		return $this->error;
	}

	public function getWarnings(): array
	{
		return $this->warnings;
	}
}