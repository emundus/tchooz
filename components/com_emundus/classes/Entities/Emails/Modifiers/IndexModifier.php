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

class IndexModifier implements TagModifierInterface
{
	private array $params = [];

	public function getName(): string
	{
		return 'INDEX';
	}

	public function getLabel(): string
	{
		return 'Index';
	}

	public function transform(string $value, array $params = []): string
	{
		return $value;
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