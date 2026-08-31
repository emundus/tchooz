<?php
/**
 * @package     Tchooz\Services\Integrations\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Integrations\Handlers;

use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;
use Tchooz\Services\Integrations\AbstractIntegrationHandler;

class WorldlineIntegrationHandler extends AbstractIntegrationHandler
{
	public function getRequiredAddons(): array
	{
		return [AddonEnum::PAYMENT];
	}

	public function onActivate(): bool
	{
		return true;
	}

	public function onDeactivate(): bool
	{
		return true;
	}

	public function onSetup(object $setup, ?SynchronizerRepository $repository = null): bool
	{
		// Set before delegating: the parent persists the entity once, enabled state included.
		// It also handles required-field validation and encrypts every PasswordField.
		$this->synchronizer->setEnabled(true);

		return parent::onSetup($setup, $repository);
	}
}
