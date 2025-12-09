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
use Tchooz\Enums\NumericSign\SignAuthenticationLevelEnum;
use Tchooz\Enums\NumericSign\SignStatusEnum;

class RequestSigners
{
	private int $id = 0;

	private Request $request;

	private SignStatusEnum $status = SignStatusEnum::TO_SIGN;

	private string $signedAt = '';

	private ContactEntity $contact;

	private int $step = 1;

	private int $page = 0;

	private string $position = '';

	private ?int $order = null;

	private ?string $anchor = null;

	private SignAuthenticationLevelEnum $authenticationLevel = SignAuthenticationLevelEnum::STANDARD;

	public function __construct(Request $request, ContactEntity $contact, SignStatusEnum|string $status = null)
	{
		$this->request = $request;
		$this->contact = $contact;
		if (!empty($status))
		{
			$this->status = $status instanceof SignStatusEnum ? $status : SignStatusEnum::from($status);
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

	public function getStatus(): SignStatusEnum
	{
		return $this->status;
	}

	public function setStatus(SignStatusEnum|string $status): self
	{
		if (is_string($status))
		{
			$status = SignStatusEnum::from($status);
		}
		elseif (!($status instanceof SignStatusEnum))
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

	public function getOrder(): ?int
	{
		return $this->order;
	}

	public function setOrder(?int $order): self
	{
		$this->order = $order;

		return $this;
	}

	public function getAnchor(): ?string
	{
		return $this->anchor;
	}

	public function setAnchor(?string $anchor): self
	{
		$this->anchor = $anchor;

		return $this;
	}

	public function getAuthenticationLevel(): SignAuthenticationLevelEnum
	{
		return $this->authenticationLevel;
	}

	public function setAuthenticationLevel(SignAuthenticationLevelEnum|string $authenticationLevel): self
	{
		if (is_string($authenticationLevel))
		{
			$authenticationLevel = SignAuthenticationLevelEnum::from($authenticationLevel);
		}
		elseif (!($authenticationLevel instanceof SignAuthenticationLevelEnum))
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
			'order'                => $this->order,
			'anchor'               => $this->anchor,
			'authentication_level' => $this->authenticationLevel->value,
		];
	}
}