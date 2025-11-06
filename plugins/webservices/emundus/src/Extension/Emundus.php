<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.content
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\WebServices\Emundus\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Services adapter for com_emundus.
 *
 * @since  4.0.0
 */
final class Emundus extends CMSPlugin implements SubscriberInterface
{
    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   5.1.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    /**
     * Registers com_emundus's API's routes in the application
     *
     * @param   BeforeApiRouteEvent  $event  The event object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router = $event->getRouter();

	    $routes = [
		    new Route(['GET'], 'v1/emundus/campaigns', 'campaigns.displayList', [], ['component' => 'com_emundus', 'public' => true]),
		    new Route(['GET'], 'v1/emundus/transactions', 'transactions.displayList', [], ['component' => 'com_emundus']),
		    new Route(['GET'], 'v1/emundus/transactions/:id', 'transactions.displayItem', ['id' => '(\d+)'], ['component' => 'com_emundus']),
		    new Route(['GET'], 'v1/emundus/files', 'files.displayList', [], ['component' => 'com_emundus']),
		    new Route(['GET'], 'v1/emundus/files/:fnum', 'files.displayItem', ['fnum' => '([0-9]{28})'], ['component' => 'com_emundus']),
		    new Route(['GET'], 'v1/emundus/fileuploads', 'fileuploads.displayList', [], ['component' => 'com_emundus']),
		    new Route(['GET'], 'v1/emundus/fileuploads/:id', 'fileuploads.displayItem', ['id' => '(\d+)'], ['component' => 'com_emundus']),
		    new Route(['GET'], 'v1/emundus/choices/', 'choices.displayList', [], ['component' => 'com_emundus']),
		];

	    $router->addRoutes($routes);
	}
}
