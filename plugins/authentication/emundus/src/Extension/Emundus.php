<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Authentication.emundus
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Authentication\Emundus\Extension;

use Joomla\CMS\Event\User\AuthenticationEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla Authentication plugin
 *
 * @since  1.5
 */
final class Emundus extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;
    use UserFactoryAwareTrait;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   5.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return ['onUserAuthenticate' => 'onUserAuthenticate'];
    }

    /**
     * This method should handle any authentication and report back to the subject
     *
     * @param   AuthenticationEvent  $event    Authentication event
     *
     * @return  void
     *
     * @since   1.5
     */
    public function onUserAuthenticate(AuthenticationEvent $event): void
    {
        $credentials = $event->getCredentials();
        $response    = $event->getAuthenticationResponse();

	    $response->type = 'Emundus';

	    if (!empty($credentials['username']) && filter_var($credentials['username'], FILTER_VALIDATE_EMAIL)) {
		    require_once JPATH_ROOT . '/components/com_emundus/models/user.php';
		    $m_user = new \EmundusModelUser();

		    $username = $m_user->getUsernameByEmail($credentials['username']);
		    if (!empty($username)) {
			    $event->setArgument('credentials', ['username' => $username, 'password' => $credentials['password']]);
				$response->username = $username;
		    }
	    }

	    return;
    }
}
