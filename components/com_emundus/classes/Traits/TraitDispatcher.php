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
	public function dispatchEvent(string $event, array $arguments = [], bool $dispatch_event_handler = true, $plugin_folder = 'emundus'): void
	{
		PluginHelper::importPlugin('emundus');
		PluginHelper::importPlugin('actionlog');

		$dispatcher = Factory::getApplication()->getDispatcher();

		if(empty($event))
		{
			return;
		}

		if($dispatch_event_handler)
		{
			$onCallEventHandler = new GenericEvent(
				'onCallEventHandler',
				[
					$event,
					$arguments
				]
			);
		}

		$onEvent            = new GenericEvent(
			$event,
			$arguments
		);

		if($dispatch_event_handler)
		{
			$dispatcher->dispatch('onCallEventHandler', $onCallEventHandler);
		}

		$dispatcher->dispatch($event, $onEvent);
	}
}