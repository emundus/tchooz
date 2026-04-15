<?php
/**
 * @package     Tchooz\Services
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Handlers;

interface HandlerInterface
{
	public function onActivate(): bool;

	public function onDeactivate(): bool;
}