<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.content
 *
 * @copyright   (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\WebServices\Emundus\Extension;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Factory;
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
		$params = $this->params;

		// IP Restriction
	    $clientIp = $event->getApplication()->input->server->getString('REMOTE_ADDR', '0.0.0.0');
		$ipRestriction = $params->get('ip_restriction', '');
	    if(!empty($ipRestriction))
		{
			$allowedIps = array_column((array)$ipRestriction, 'ip');

			if(!in_array($clientIp, $allowedIps))
			{
				return;
			}
		}
		//

	    // Rate Limiting
	    $limitEnabled = (bool) $params->get('rate_limit_enabled', true);
	    if ($limitEnabled)
	    {
			$maxRequests = (int) $params->get('rate_limit_max_requests', 60);
		    $windowSeconds = (int) $params->get('rate_limit_window_seconds', 60);

		    $this->enforceRateLimit($event, $maxRequests, $windowSeconds, $clientIp);
	    }
		//

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

	private function enforceRateLimit(BeforeApiRouteEvent $event, int $maxRequests, int $windowSeconds, string $clientIp): void
	{
		if(empty($clientIp))
		{
			$app      = $event->getApplication();
			$clientIp = $app->input->server->getString('REMOTE_ADDR', '0.0.0.0');
		}

		$key = 'rate_limit_' . md5($clientIp);

		$lifetime = $windowSeconds;
		$cache = Factory::getContainer()
			->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', [
				'defaultgroup' => 'com_emundus',
				'lifetime' => $lifetime
			]);

		$now = time();

		$timestamps = $cache->get($key);
		if (!is_array($timestamps)) {
			$timestamps = [];
		}

		$timestamps = array_filter($timestamps, function ($ts) use ($now, $windowSeconds) {
			return ($ts > ($now - $windowSeconds));
		});

		$timestamps[] = $now;

		$used = count($timestamps);
		$remaining = max(0, $maxRequests - $used);
		$resetTime = ($timestamps[0] ?? $now) + $windowSeconds; // window reset timestamp

		header('X-RateLimit-Limit: ' . $maxRequests);
		header('X-RateLimit-Remaining: ' . $remaining);
		header('X-RateLimit-Reset: ' . $resetTime);

		if ($used > $maxRequests) {
			$retryAfter = $resetTime - $now;
			if ($retryAfter < 0) {
				$retryAfter = 0;
			}

			header('HTTP/1.1 429 Too Many Requests');
			header('Content-Type: application/json; charset=utf-8');
			header('Retry-After: ' . (int) $retryAfter);

			echo json_encode([
				'status' => 429,
				'message' => 'Too many requests. Try again after ' . $retryAfter . ' seconds.',
				'limit' => $maxRequests,
				'remaining' => 0,
				'reset' => $resetTime,
			]);

			exit;
		}

		$cache->store($timestamps, $key);
	}
}
