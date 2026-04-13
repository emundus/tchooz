<?php
/**
 * @package     Tchooz\Subscribers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Providers;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Tchooz\Subscribers\GenerateReferenceSubscriber;

class EmundusSubscriberProvider implements ServiceProviderInterface
{
	public function register(Container $container)
	{
		// TODO: Subscribers can be conditionned by component parameters, so we should check those before registering the subscriber
		$subject = $container->get(DispatcherInterface::class);
		$subject->addSubscriber(new GenerateReferenceSubscriber('generate_reference'));
	}
}