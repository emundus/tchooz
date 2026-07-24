<?php

namespace Tchooz\Entities\Location;

use Joomla\CMS\User\User;

class SpecificationEntity
{
	private int $id;

	private \DateTime $createdAt;

	private string $name;

	private bool $published;

	private User $createdBy;

	private ?\DateTime $updatedAt;

	private ?User $updatedBy;
}