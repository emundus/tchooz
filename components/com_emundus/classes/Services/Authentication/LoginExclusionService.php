<?php
/**
 * @package     Tchooz\Services
 * @subpackage  Authentication
 *
 * @copyright   Copyright (C) 2015 emundus.fr. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Tchooz\Services\Authentication;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;

/**
 * Decides whether a login attempt must be rejected based on regex patterns
 * configured in the com_emundus component options (user fieldset).
 *
 * This service is read-only and side-effect free: it neither touches the
 * session nor dispatches events. The login rejection itself is performed by
 * the caller (the emundus user plugin) on the onUserLogin event.
 */
class LoginExclusionService
{
	/**
	 * Component param name: whether the login restriction is enabled (1/0).
	 */
	public const PARAM_ENABLED = 'restrict_login';

	/**
	 * Component param name: the regex patterns, one per line.
	 */
	public const PARAM_PATTERNS = 'restrict_login_patterns';

	/**
	 * Component param name: the custom rejection message.
	 */
	public const PARAM_MESSAGE = 'restrict_login_message';

	/**
	 * Email domain that is always allowed, regardless of configured patterns.
	 * Acts as a safety net to prevent locking out internal staff.
	 */
	private const ALWAYS_ALLOWED_EMAIL_DOMAIN = '@emundus.fr';

	/**
	 * Component parameters holding the restriction configuration.
	 *
	 * @var Registry
	 */
	private Registry $params;

	/**
	 * @param   Registry|null  $params  Component params; defaults to com_emundus options.
	 */
	public function __construct(?Registry $params = null)
	{
		$this->params = $params ?? ComponentHelper::getParams('com_emundus');
	}

	/**
	 * Whether the login restriction is enabled.
	 *
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return (int) $this->params->get(self::PARAM_ENABLED, 0) === 1;
	}

	/**
	 * The configured regex patterns, one per line, empty lines removed.
	 *
	 * @return string[]
	 */
	public function getPatterns(): array
	{
		$raw = (string) $this->params->get(self::PARAM_PATTERNS, '');

		if ($raw === '')
		{
			return [];
		}

		$lines = preg_split('/\r\n|\r|\n/', $raw);

		return array_values(array_filter(array_map('trim', $lines), static fn($line) => $line !== ''));
	}

	/**
	 * The rejection message to display, or the default one when none is set.
	 *
	 * The returned value may be a plain sentence or a language key; the caller
	 * is responsible for running it through Text::_().
	 *
	 * @return string
	 */
	public function getRejectionMessage(): string
	{
		$message = trim((string) $this->params->get(self::PARAM_MESSAGE, ''));

		return $message !== '' ? $message : 'PLG_USER_EMUNDUS_RESTRICT_LOGIN_DEFAULT_MESSAGE';
	}

	/**
	 * Whether the given credentials match an exclusion pattern and must be rejected.
	 *
	 * Both the username and the email are tested against every pattern.
	 * An invalid pattern is skipped and logged rather than blocking a legitimate
	 * user (fail-safe).
	 *
	 * @param   string  $username
	 * @param   string  $email
	 *
	 * @return bool
	 */
	public function isExcluded(string $username, string $email): bool
	{
		if (!$this->isEnabled())
		{
			return false;
		}

		if ($this->isAlwaysAllowedEmail($email))
		{
			return false;
		}

		$patterns = $this->getPatterns();

		if (empty($patterns))
		{
			return false;
		}

		$subjects = array_filter([$username, $email], static fn($subject) => $subject !== '');

		foreach ($patterns as $pattern)
		{
			$regex = $this->buildRegex($pattern);

			foreach ($subjects as $subject)
			{
				$result = @preg_match($regex, $subject);

				if ($result === false)
				{
					Log::add('Invalid login exclusion pattern ignored: ' . $pattern, Log::WARNING, 'com_emundus.auth');

					// Skip this pattern entirely for all subjects.
					continue 2;
				}

				if ($result === 1)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Wraps a raw pattern (entered without delimiters) into a case-insensitive regex.
	 *
	 * @param   string  $pattern
	 *
	 * @return string
	 */
	private function buildRegex(string $pattern): string
	{
		return '#' . str_replace('#', '\#', $pattern) . '#i';
	}

	/**
	 * Whether the given email belongs to the always-allowed domain.
	 * Comparison is case-insensitive.
	 *
	 * @param   string  $email
	 *
	 * @return bool
	 */
	private function isAlwaysAllowedEmail(string $email): bool
	{
		if ($email === '')
		{
			return false;
		}

		return str_ends_with(strtolower($email), self::ALWAYS_ALLOWED_EMAIL_DOMAIN);
	}
}
