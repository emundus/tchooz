<?php
/**
 * @package     Tchooz\Entities\Emails\Modifiers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Emails\Modifiers;

use Tchooz\Interfaces\TagModifierInterface;

class TrimModifier implements TagModifierInterface
{
	private array $params = [];


	public function getName(): string
	{
		return 'TRIM';
	}

	public function getLabel(): string
	{
		return 'Trim';
	}

	public function transform(string $value, array $params = []): string
	{
		return trim($value);
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	public function getParams(): array
	{
		return $this->params;
	}
}