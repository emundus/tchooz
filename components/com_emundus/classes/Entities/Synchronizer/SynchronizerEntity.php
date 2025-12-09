<?php

namespace Tchooz\Entities\Synchronizer;

use Tchooz\Enums\Synchronizer\SynchronizerContextEnum;

class SynchronizerEntity
{
	private int $id;

	private string $type;

	private string $name;

	private string $description;

	private array $params;

	private array $config;

	private bool $published;

	private bool $enabled;

	private ?string $icon;

	private ?string $consumptions;

	private ?SynchronizerContextEnum $context;

	public function __construct(
		int $id,
		string $type,
		string $name,
		string $description,
		array $params = [],
		array $config = [],
		bool $published = false,
		bool $enabled = false,
		?string $icon = null,
		?string $consumptions = null,
		?SynchronizerContextEnum $context = null
	) {
		$this->id = $id;
		$this->type = $type;
		$this->params = $params;
		$this->config = $config;
		$this->published = $published;
		$this->name = $name;
		$this->description = $description;
		$this->enabled = $enabled;
		$this->icon = $icon;
		$this->consumptions = $consumptions;
		$this->context = $context;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): void
	{
		$this->type = $type;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	public function getConfig(): array
	{
		return $this->config;
	}

	public function setConfig(array $config): void
	{
		$this->config = $config;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function setIcon(string $icon): void
	{
		$this->icon = $icon;
	}

	public function getConsumptions(): ?string
	{
		return $this->consumptions;
	}

	public function setConsumptions(?string $consumptions): void
	{
		$this->consumptions = $consumptions;
	}

	public function getContext(): ?SynchronizerContextEnum
	{
		return $this->context;
	}

	public function setContext(?SynchronizerContextEnum $context): void
	{
		$this->context = $context;
	}
}