<?php
/**
 * @package     Tchooz\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations\Handlers;

use Joomla\CMS\Factory;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;
use Tchooz\Synchronizers\NumericSign\DocaposteSynchronizer;

class DocaposteIntegrationHandler extends AbstractIntegrationHandler
{
	public function getRequiredAddons(): array
	{
		return [AddonEnum::NUMERIC_SIGN];
	}

	public function onActivate(): bool
	{
		return true;
	}

	public function onDeactivate(): bool
	{
		return true;
	}

	public function onAfterSetup(object $setup): bool
	{
		// Test authentication by instanciating the synchronizer
		try
		{
			new DocaposteSynchronizer();
		}
		catch (\Exception $e)
		{
			return false;
		}

		return true;
	}
}