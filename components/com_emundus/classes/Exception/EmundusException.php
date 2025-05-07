<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Tchooz\Exception;

// phpcs:disable PSR1.Files.SideEffects
use Joomla\CMS\Factory;

\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Exception class defining a not allowed access
 *
 * @since  3.6.3
 */
class EmundusException extends \RuntimeException
{
	// Redefine the exception so message isn't optional
	public function __construct($message, $code = 0, \Throwable $previous = null, $redirect = true, $display_notice = false, $format = 'html') {
		// some code

		// make sure everything is assigned properly
		parent::__construct($message, $code, $previous);

		if($redirect)
		{
			$app = Factory::getApplication();
			$session = $app->getSession();
			$session->set('error', $message);
			$session->set('errorcode', $code);
			if($display_notice)
			{
				$app->enqueueMessage($message, 'error');
			}

			//TODO: Allow custom redirect link, keep /error by default
			$link = 'index.php?option=com_emundus&view=error';
			$error_menu = $app->getMenu()->getItems('link', $link, true);
			$error_alias = $error_menu ? $error_menu->alias : 'error';
			if($format === 'raw') {
				$error_alias .= '?format=raw';
				$error_alias .= '&code='.$code;
			}

			$app->redirect('/'.$error_alias, $code);
		}
	}
}