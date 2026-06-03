<?php
/**
 * @package    Tchooz\Services\PublicAccess
 * @copyright  Emundus
 * @license    GNU General Public License version 2 or later
 */

namespace Tchooz\Exception;

defined('_JEXEC') or die;

/**
 * Raised when the anti-bot challenge (honeypot or dwell time) fails.
 *
 * Deliberately generic: the message handed to the user must never reveal
 * which check tripped, so an attacker cannot tune around it. The caller is
 * expected to surface a plain "access denied".
 *
 * @since 1.0.0
 */
class ChallengeFailedException extends PublicApplicationGuardException
{
	/**
	 * @var int
	 */
	protected int $httpStatus = 403;
}