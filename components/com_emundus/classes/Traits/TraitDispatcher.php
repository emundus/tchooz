<?php
/**
 * @package     classes
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

trait TraitDispatcher
{
	public function dispatchJoomlaEvent(string $event, array $arguments = [], bool $dispatch_event_handler = true, $plugin_folder = 'emundus', bool $dispatch_default_event = true): void
	{
		PluginHelper::importPlugin('emundus');
		PluginHelper::importPlugin('actionlog');

		$dispatcher = Factory::getApplication()->getDispatcher();

		if (empty($event))
		{
			return;
		}

		if ($dispatch_event_handler)
		{
			$onCallEventHandler = new GenericEvent(
				'onCallEventHandler',
				[
					$event,
					$arguments
				]
			);
		}

		$onEvent = new GenericEvent(
			$event,
			$arguments
		);

		if ($dispatch_event_handler)
		{
			$dispatcher->dispatch('onCallEventHandler', $onCallEventHandler);
		}

		if ($dispatch_default_event)
		{
			$dispatcher->dispatch($event, $onEvent);
		}
	}
}