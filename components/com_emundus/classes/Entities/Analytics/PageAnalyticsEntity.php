<?php
/**
 * @package     Tchooz\Entities\Analytics
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Analytics;

class PageAnalyticsEntity
{
	private int $id;

	private \DateTime $date;

	private int $count;

	private string $link;

	public function __construct(int $id, \DateTime $date, int $count, string $link)
	{
		$this->id    = $id;
		$this->date  = $date;
		$this->count = $count;
		$this->link  = $link;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getDate(): \DateTime
	{
		return $this->date;
	}

	public function setDate(\DateTime $date): void
	{
		$this->date = $date;
	}

	public function getCount(): int
	{
		return $this->count;
	}

	public function setCount(int $count): void
	{
		$this->count = $count;
	}

	public function getLink(): string
	{
		return $this->link;
	}

	public function setLink(string $link): void
	{
		$this->link = $link;
	}
}