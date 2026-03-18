<?php
/**
 * @package     Tchooz\Entities\Filters
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Filters;

use Joomla\CMS\User\User;
use Tchooz\Enums\Filters\FilterModeEnum;

class FilterEntity
{
	private int $id;

	private \DateTime $timeDate;

	private User $user;

	private string $name;

	private array $constraints;

	private ?int $itemId;

	private FilterModeEnum $mode;

	public function __construct(
		string         $name,
		array          $constraints,
		User           $user,
		FilterModeEnum $mode = FilterModeEnum::LIST,
		?int           $itemId = null,
		int            $id = 0,
		?\DateTime     $timeDate = null
	)
	{
		$this->name        = $name;
		$this->constraints = $constraints;
		$this->user        = $user;
		$this->mode        = $mode;
		$this->itemId      = $itemId;
		$this->id          = $id;
		$this->timeDate    = $timeDate ?? new \DateTime();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getTimeDate(): \DateTime
	{
		return $this->timeDate;
	}

	public function setTimeDate(\DateTime $timeDate): void
	{
		$this->timeDate = $timeDate;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function setUser(User $user): void
	{
		$this->user = $user;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getConstraints(): array
	{
		return $this->constraints;
	}

	public function setConstraints(array $constraints): void
	{
		$this->constraints = $constraints;
	}

	public function getItemId(): ?int
	{
		return $this->itemId;
	}

	public function setItemId(?int $itemId): void
	{
		$this->itemId = $itemId;
	}

	public function getMode(): FilterModeEnum
	{
		return $this->mode;
	}

	public function setMode(FilterModeEnum $mode): void
	{
		$this->mode = $mode;
	}

	public function __serialize(): array
	{
		return [
			'id'          => $this->id,
			'timeDate'    => $this->timeDate->format('Y-m-d H:i:s'),
			'user'        => $this->user->id,
			'name'        => $this->name,
			'constraints' => $this->constraints,
			'itemId'      => $this->itemId,
			'mode'        => $this->mode->value
		];
	}
}