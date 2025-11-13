<?php
/**
 * @package     Tchooz\Entities\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Addons;

class AddonValue
{
	private bool $enabled;

	private bool $displayed;

	private array $params;

	public function __construct(bool $enabled, bool $displayed, array $params)
	{
		$this->enabled = $enabled;
		$this->displayed = $displayed;
		$this->params = $params;
	}

	public function isEnabled(): bool
	{
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void
	{
		$this->enabled = $enabled;
	}

	public function isDisplayed(): bool
	{
		return $this->displayed;
	}

	public function setDisplayed(bool $displayed): void
	{
		$this->displayed = $displayed;
	}

	public function getParams(): array
	{
		return $this->params;
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}
}