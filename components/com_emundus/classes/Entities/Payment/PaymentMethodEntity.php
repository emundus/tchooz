<?php

namespace Tchooz\Entities\Payment;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;

class PaymentMethodEntity
{
	private int $id;
	public string $name;
	public string $label;
	public ?string $description;
	public int $published;
	private array $services = [];

	public function __construct(int $id = 0, string $name = '', string $label = '', ?string $description = null, int $published = 0, array $services = [])
	{
		$this->id = $id;
		$this->name = $name;
		$this->label = $label;
		$this->description = $description;
		$this->published = $published;
		$this->setServices($services);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description ?? '';
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	/**
	 * @return array<int>
	 */
	public function getServices(): array
	{
		return $this->services;
	}

	/**
	 * @param   array  $services
	 *
	 * @return void
	 */
	public function setServices(array $services): void
	{
		$this->services = array_map('intval', $services);
	}

	public function isServiceAvailable(int $service_id): bool
	{
		return in_array($service_id, $this->services);
	}

	public function serialize(): array
	{
		return [
			'id'          => $this->getId(),
			'name'        => $this->name,
			'label'       => $this->label,
			'description' => $this->description,
			'published'   => $this->published,
			'services'    => $this->services,
		];
	}
}