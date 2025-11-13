<?php
/**
 * @package     Tchooz\Factories\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons;

interface AddonHandlerInterface
{
	public function toggle(bool $state): bool;
}