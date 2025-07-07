<?php
/**
 * @package     Tchooz\Interfaces
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Interfaces;

interface TagModifierInterface
{
	public function getName(): string;
	public function getLabel(): string;
	public function transform(string $value): string;
}