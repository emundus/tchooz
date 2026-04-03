<?php

namespace Tchooz\Entities\Calculation;

use Tchooz\Enums\Calculation\CalculationTypeEnum;
use Tchooz\Services\Calculation\CalculationTemplateRegistry;

class CalculationEntity
{
	private int $id;
	private CalculationTypeEnum $type;
	private ?string $templateCode;
	private array $configuration;

	public function __construct(int $id, CalculationTypeEnum $type = CalculationTypeEnum::CUSTOM, string $templateCode = null, array $configuration = [])
	{
		$this->id = $id;
		$this->type = $type;
		$this->setTemplateCode($templateCode);
		$this->configuration = $configuration;
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

	public function getType(): CalculationTypeEnum
	{
		return $this->type;
	}

	public function setType(CalculationTypeEnum $type): self
	{
		$this->type = $type;

		return $this;
	}

	public function getTemplateCode(): ?string
	{
		return $this->templateCode;
	}

	/**
	 * @param   string|null  $templateCode
	 *
	 * @return self
	 */
	public function setTemplateCode(?string $templateCode): self
	{
		if ($templateCode !== null)
		{
			$templateRegistry = new CalculationTemplateRegistry();

			if (empty($templateRegistry->getTemplate($templateCode)))
			{
				throw new \InvalidArgumentException("Template code '$templateCode' does not exist.");
			}
		}

		$this->templateCode = $templateCode;

		return $this;
	}

	public function getConfiguration(): array
	{
		return $this->configuration;
	}

	public function setConfiguration(array $configuration): self
	{
		$this->configuration = $configuration;

		return $this;
	}
}