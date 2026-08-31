<?php
/**
 * @package     Tchooz\Entities\Favorite
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Favorite;

use DateTimeImmutable;

class FavoriteFileEntity
{
	private DateTimeImmutable $created;

	public function __construct(
		private string $fnum,
		private int $userId,
		private int $id = 0,
		?DateTimeImmutable $created = null
	)
	{
		$this->created = $created ?? new DateTimeImmutable();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): self
	{
		$this->id = $id;

		return $this;
	}

	public function getFnum(): string
	{
		return $this->fnum;
	}

	public function setFnum(string $fnum): self
	{
		$this->fnum = $fnum;

		return $this;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;

		return $this;
	}

	public function getCreated(): DateTimeImmutable
	{
		return $this->created;
	}

	public function setCreated(DateTimeImmutable $created): self
	{
		$this->created = $created;

		return $this;
	}

	public function __serialize(): array
	{
		return [
			'id'      => $this->id,
			'fnum'    => $this->fnum,
			'user_id' => $this->userId,
			'created' => $this->created->format('Y-m-d H:i:s'),
		];
	}
}
