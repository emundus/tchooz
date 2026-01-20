<?php
/**
 * @package     Tchooz\Services\Language\Objects
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Language\Objects;

use Tchooz\Services\Language\Objects\Definition\ObjectDefinition;

interface ObjectInterface
{
	public function getType(): string;

	public function getName(): string;

	public function getDescription(): string;

	public function getDatas(array $filters = []): array;

	public function getDefinition(): ObjectDefinition;
}