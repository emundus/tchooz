<?php
/**
 * @package    Tchooz\Services\PublicAccess
 * @copyright  Emundus
 * @license    GNU General Public License version 2 or later
 */

namespace Tchooz\Exception;

defined('_JEXEC') or die;

/**
 * Base exception for any rejection raised while guarding a public application
 * submission. Carries an HTTP status code so the controller can map it
 * straight onto a response without inspecting the concrete subclass.
 *
 * @since 1.0.0
 */
class PublicApplicationGuardException extends \RuntimeException
{
	/**
	 * HTTP status to surface for this rejection.
	 *
	 * @var int
	 */
	protected int $httpStatus = 403;

	/**
	 * @return int  The HTTP status code associated with this rejection.
	 */
	public function getHttpStatus(): int
	{
		return $this->httpStatus;
	}
}