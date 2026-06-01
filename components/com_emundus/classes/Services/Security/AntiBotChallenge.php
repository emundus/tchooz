<?php
/**
 * @package    Tchooz\Services\PublicAccess
 * @copyright  Emundus
 * @license    GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Security;

use Joomla\CMS\Language\Text;
use Joomla\Input\Input;
use Tchooz\Exception\ChallengeFailedException;

defined('_JEXEC') or die;

/**
 * Server-side, third-party-free anti-bot challenge for the public application
 * form. Two complementary checks:
 *
 *  - Honeypot: a field hidden off-screen in the layout that humans never see.
 *    Any non-empty value means an automated agent filled the form blindly.
 *
 *  - Minimum dwell time: an HMAC-signed render timestamp embedded in the form.
 *    Humans take at least a couple of seconds between page render and submit;
 *    bots submit in milliseconds. The signature prevents forging or replaying
 *    the timestamp beyond its validity window.
 *
 * On failure a generic ChallengeFailedException is thrown; the message must
 * never reveal which check tripped, so an attacker cannot tune around it.
 *
 * Issuing side (layout) and verifying side (controller) both live here, so the
 * token format stays in one place: call issueToken() when rendering the form,
 * validate() when handling the submission.
 *
 * @since 1.0.0
 */
class AntiBotChallenge
{
	/**
	 * Name of the hidden honeypot field expected in the POST body.
	 */
	public const HONEYPOT_FIELD = 'contact_url';

	/**
	 * Name of the signed-timestamp field expected in the POST body.
	 */
	public const TIMESTAMP_FIELD = 'challenge_ts';

	/**
	 * Minimum seconds a human is assumed to need before submitting.
	 */
	private const MIN_DWELL_SECONDS = 2;

	/**
	 * Maximum age of a rendered form before it is considered stale / replayed.
	 */
	private const MAX_AGE_SECONDS = 900;

	/**
	 * Joomla input (POST bag is used).
	 *
	 * @var Input
	 */
	private Input $input;

	/**
	 * Site secret used to sign the render timestamp.
	 *
	 * @var string
	 */
	private string $secret;

	/**
	 * @param   Input   $input   The application input.
	 * @param   string  $secret  The site secret (Factory app->get('secret')).
	 */
	public function __construct(Input $input, string $secret)
	{
		$this->input  = $input;
		$this->secret = $secret;
	}

	/**
	 * Issue a signed render token to embed in the form (hidden field).
	 *
	 * Call this when rendering the public application form and output the
	 * returned value as the value of the TIMESTAMP_FIELD input.
	 *
	 * @return  string  "<timestamp>.<hmac>"
	 *
	 * @since   1.0.0
	 */
	public function issueToken(): string
	{
		$ts = time();

		return $ts . '.' . hash_hmac('sha256', (string) $ts, $this->secret);
	}

	/**
	 * Validate the anti-bot challenge for the current submission.
	 *
	 * @return  void
	 *
	 * @throws  ChallengeFailedException  If honeypot is filled or dwell invalid.
	 *
	 * @since   1.0.0
	 */
	public function validate(): void
	{
		$this->checkHoneypot();
		$this->checkDwellTime();
	}

	/**
	 * @throws ChallengeFailedException
	 */
	private function checkHoneypot(): void
	{
		$honeypot = $this->input->post->getString(self::HONEYPOT_FIELD, '');

		if (trim($honeypot) !== '')
		{
			throw new ChallengeFailedException(Text::_('ACCESS_DENIED'));
		}
	}

	/**
	 * @throws ChallengeFailedException
	 */
	private function checkDwellTime(): void
	{
		$raw   = $this->input->post->getString(self::TIMESTAMP_FIELD, '');
		$parts = explode('.', $raw, 2);

		if (count($parts) !== 2 || !ctype_digit($parts[0]))
		{
			throw new ChallengeFailedException(Text::_('ACCESS_DENIED'));
		}

		[$ts, $signature] = $parts;

		$expected = hash_hmac('sha256', $ts, $this->secret);

		if (!hash_equals($expected, $signature))
		{
			// Token tampered with or not issued by us.
			throw new ChallengeFailedException(Text::_('ACCESS_DENIED'));
		}

		$elapsed = time() - (int) $ts;

		if ($elapsed < self::MIN_DWELL_SECONDS || $elapsed > self::MAX_AGE_SECONDS)
		{
			throw new ChallengeFailedException(Text::_('ACCESS_DENIED'));
		}
	}
}