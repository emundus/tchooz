<?php
/**
 * @package     Tchooz\Services\Addons
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Addons;

use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Services\Handlers\HandlerInterface;

abstract class AbstractAddonHandler implements HandlerInterface
{
	protected AddonEntity $addon;

	public function __construct(AddonEntity $addon)
	{
		$this->addon = $addon;
	}
}

