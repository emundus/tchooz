<?php
/**
 * @package     Tchooz\Entities\NumericSign
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\NumericSign;

use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Enums\NumericSign\SignAuthenticationLevel;
use Tchooz\Enums\NumericSign\SignStatus;

class RequestSigners
{
	private int $id = 0;

	private Request $request;

	private SignStatus $status = SignStatus::TO_SIGN;

	private string $signedAt = '';

	private ContactEntity $contact;

	private int $step = 1;

	private int $page = 0;

	private string $position = '';

	private SignAuthenticationLevel $authenticationLevel = SignAuthenticationLevel::STANDARD;

	public function __construct(Request $request, ContactEntity $contact, SignStatus|string $status = null)
	{
		$this->request = $request;
		$this->contact = $contact;
		if (!empty($status))
		{
			$this->status = $status instanceof SignStatus ? $status : SignStatus::from($status);
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getRequest(): Request
	{
		return $this->request;
	}

	public function setRequest(Request $request): void
	{
		$this->request = $request;
	}

	public function getStatus(): SignStatus
	{
		return $this->status;
	}

	public function setStatus(SignStatus|string $status): self
	{
		if (is_string($status))
		{
			$status = SignStatus::from($status);
		}
		elseif (!($status instanceof SignStatus))
		{
			throw new \InvalidArgumentException('Invalid status type');
		}

		$this->status = $status;

		return $this;
	}

	public function getSignedAt(): string
	{
		return $this->signedAt;
	}

	public function setSignedAt(string $signedAt): void
	{
		$this->signedAt = $signedAt;
	}

	public function getContact(): ContactEntity
	{
		return $this->contact;
	}

	public function setContact(ContactEntity $contact): void
	{
		$this->contact = $contact;
	}

	public function getStep(): int
	{
		return $this->step;
	}

	public function setStep(int $step): void
	{
		$this->step = $step;
	}

	public function getPage(): int
	{
		return $this->page;
	}

	public function setPage(int $page): void
	{
		$this->page = $page;
	}

	public function getPosition(): string
	{
		return $this->position;
	}

	public function setPosition(string $position): void
	{
		$this->position = $position;
	}

	public function getAuthenticationLevel(): SignAuthenticationLevel
	{
		return $this->authenticationLevel;
	}

	public function setAuthenticationLevel(SignAuthenticationLevel|string $authenticationLevel): self
	{
		if (is_string($authenticationLevel))
		{
			$authenticationLevel = SignAuthenticationLevel::from($authenticationLevel);
		}
		elseif (!($authenticationLevel instanceof SignAuthenticationLevel))
		{
			throw new \InvalidArgumentException('Invalid authentication level type');
		}

		$this->authenticationLevel = $authenticationLevel;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id'                   => $this->id,
			'request_id'           => $this->request->getId(),
			'status'               => $this->status->value,
			'signed_at'            => $this->signedAt ?: null,
			'contact_id'           => $this->contact->getId(),
			'step'                 => $this->step,
			'page'                 => $this->page,
			'position'             => $this->position,
			'authentication_level' => $this->authenticationLevel->value,
		];
	}
}