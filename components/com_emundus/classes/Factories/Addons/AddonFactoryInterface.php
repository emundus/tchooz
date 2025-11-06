<?php
/**
 * @package     Tchooz\Factories\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Addons;

interface AddonFactoryInterface
{
	public function toggle(bool $state): bool;
}