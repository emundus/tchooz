<?php

namespace Tchooz\Exception;

defined('_JEXEC') or die;

/**
 * Raised when a rate-limit window has been exceeded.
 *
 * Unlike the anti-bot challenge, this message IS meant to be shown to the
 * user (e.g. "please wait N seconds"), so it carries a human-readable,
 * already-localised message and an HTTP 429 status.
 *
 * @since 1.0.0
 */
class RateLimitExceededException extends PublicApplicationGuardException
{
	/**
	 * @var int
	 */
	protected int $httpStatus = 429;
}