<?php
/**
 * @package    Tchooz\Services\PublicAccess
 * @copyright  Emundus
 * @license    GNU General Public License version 2 or later
 */

namespace Tchooz\Services\PublicAccess;

use Tchooz\Exception\ChallengeFailedException;
use Tchooz\Exception\RateLimitExceededException;
use Tchooz\Services\Security\AntiBotChallenge;
use Tchooz\Services\Security\ClientIpResolver;
use Tchooz\Services\Security\RateLimiter;

defined('_JEXEC') or die;

/**
 * Orchestrates the abuse-prevention pipeline for public (guest) campaign
 * applications. This is the single entry point the controller talks to; it
 * owns the business sequence and the concrete rate-limit policy, delegating
 * the mechanics to the focused services it composes:
 *
 *   - AntiBotChallenge  — honeypot + signed dwell time
 *   - ClientIpResolver  — spoof-resistant client IP
 *   - RateLimiter       — generic multi-window enforcement
 *
 * The controller calls guard(); on any rejection a
 * PublicApplicationGuardException subclass is thrown, carrying the HTTP status
 * to surface (403 for a failed challenge, 429 for a rate limit).
 *
 * RATE-LIMIT POLICY (rationale):
 *   - Cooldown 30s          : anti double-click / scripted bursts
 *   - 3 / minute            : blocks rapid automated submissions
 *   - 10 / hour             : tolerates shared NAT / public terminals
 *   - 30 / day              : generous for shared IPs
 *   - 5 / campaign / day    : prevents spam targeted at one campaign
 *
 * @since 1.0.0
 */
class PublicApplicationGuard
{
	private AntiBotChallenge $challenge;

	private ClientIpResolver $ipResolver;

	private RateLimiter $rateLimiter;

	const DEFAULT_RATE_LIMIT_WINDOW = 30;
	const DEFAULT_RATE_LIMIT_CAMPAIGN_PER_MINUTE = 3;
	const DEFAULT_RATE_LIMIT_CAMPAIGN_PER_HOUR = 10;
	const DEFAULT_RATE_LIMIT_CAMPAIGN_PER_DAY = 30;
	const DEFAULT_RATE_LIMIT_GLOBAL_PER_CAMPAIGN_PER_DAY = 5;


	public function __construct(
		AntiBotChallenge $challenge,
		ClientIpResolver $ipResolver,
		RateLimiter $rateLimiter
	) {
		$this->challenge   = $challenge;
		$this->ipResolver  = $ipResolver;
		$this->rateLimiter = $rateLimiter;
	}

	/**
	 * Run the full abuse-prevention pipeline for a public application.
	 *
	 * Order matters: the cheapest, zero-side-effect checks (anti-bot) run
	 * first, so a caught bot never touches the cache or the database. Only
	 * once everything passes is the action recorded against the limiter.
	 *
	 * @param   int  $campaignId  The campaign being applied to.
	 *
	 * @return  void
	 *
	 * @throws  ChallengeFailedException      (403) honeypot/dwell
	 * @throws  RateLimitExceededException    (429) a window exceeded
	 *
	 * @since   1.0.0
	 */
	public function guard(int $campaignId): void
	{
		// 1. Anti-bot — cheapest, no side effects.
		$this->challenge->validate();

		// 2. Resolve a trustworthy client IP and derive the buckets.
		$clientIp     = $this->ipResolver->resolve();
		$ipHash       = md5($clientIp);
		$globalBucket = 'public_apply_' . $ipHash;
		$campaignBucket = 'public_apply_' . $ipHash . '_c' . $campaignId;

		// 3. Global windows (all campaigns).
		$this->rateLimiter->enforceCooldown(
			$globalBucket,
			self::DEFAULT_RATE_LIMIT_WINDOW,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_COOLDOWN'
		);
		$this->rateLimiter->enforceWindow(
			$globalBucket,
			self::DEFAULT_RATE_LIMIT_CAMPAIGN_PER_MINUTE,
			60,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_RATE_LIMIT_MINUTE'
		);
		$this->rateLimiter->enforceWindow(
			$globalBucket,
			self::DEFAULT_RATE_LIMIT_CAMPAIGN_PER_HOUR,
			3600,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_RATE_LIMIT_HOUR'
		);
		$this->rateLimiter->enforceWindow(
			$globalBucket,
			self::DEFAULT_RATE_LIMIT_CAMPAIGN_PER_DAY,
			86400,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_RATE_LIMIT_DAY'
		);

		// 4. Per-campaign window.
		$this->rateLimiter->enforceWindow(
			$campaignBucket,
			self::DEFAULT_RATE_LIMIT_GLOBAL_PER_CAMPAIGN_PER_DAY,
			86400,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_RATE_LIMIT_CAMPAIGN'
		);

		// 5. All clear — record the action against both buckets.
		$this->rateLimiter->record($globalBucket);
		$this->rateLimiter->record($campaignBucket);
	}

	/**
	 * Convenience accessor so the layout can issue a challenge token without
	 * needing to construct AntiBotChallenge itself.
	 *
	 * @return  string  The signed render token for the form's hidden field.
	 *
	 * @since   1.0.0
	 */
	public function issueChallengeToken(): string
	{
		return $this->challenge->issueToken();
	}
}