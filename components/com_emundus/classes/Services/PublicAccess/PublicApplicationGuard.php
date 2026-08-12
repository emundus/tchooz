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
 * RATE-LIMIT POLICY (rationale for the defaults):
 *   - Cooldown 30s          : anti double-click / scripted bursts
 *   - 3 / minute            : blocks rapid automated submissions
 *   - 10 / hour             : tolerates shared NAT / public terminals
 *   - 30 / day              : generous for shared IPs
 *   - 5 / campaign / day    : prevents spam targeted at one campaign
 *
 * Each window is tunable per platform through the public-session addon, but the
 * guard clamps every supplied value to a hard bound (MIN_* / MAX_* constants)
 * so a misconfiguration can never widen a window past an abuse-sized hole nor
 * shorten the cooldown below a meaningful floor. The bounds are the security
 * contract; the addon only chooses where inside them to sit.
 *
 * @since 1.0.0
 */
class PublicApplicationGuard
{
	private AntiBotChallenge $challenge;

	private ClientIpResolver $ipResolver;

	private RateLimiter $rateLimiter;

	/**
	 * Resolved, clamped rate-limit windows actually enforced by this instance.
	 */
	private int $cooldown;
	private int $perMinute;
	private int $perHour;
	private int $perDay;
	private int $perCampaignPerDay;

	const DEFAULT_RATE_LIMIT_WINDOW = 30;
	const DEFAULT_RATE_LIMIT_GLOBAL_PER_MINUTE = 3;
	const DEFAULT_RATE_LIMIT_GLOBAL_PER_HOUR = 10;
	const DEFAULT_RATE_LIMIT_GLOBAL_PER_DAY = 30;
	const DEFAULT_RATE_LIMIT_PER_CAMPAIGN_PER_DAY = 5;

	// Hard bounds. A configured value is always clamped into [min, max]; the
	// guard never honours anything outside them. Counts floor at 1 (0 would
	// lock every applicant out); the cooldown floors well above 0 so it cannot
	// be effectively disabled.
	const MIN_RATE_LIMIT_WINDOW = 5;
	const MAX_RATE_LIMIT_WINDOW = 300;
	const MAX_RATE_LIMIT_GLOBAL_PER_MINUTE = 30;
	const MAX_RATE_LIMIT_GLOBAL_PER_HOUR = 200;
	const MAX_RATE_LIMIT_GLOBAL_PER_DAY = 1000;
	const MAX_RATE_LIMIT_PER_CAMPAIGN_PER_DAY = 100;

	/**
	 * @param   AntiBotChallenge  $challenge    Honeypot + signed dwell time.
	 * @param   ClientIpResolver  $ipResolver   Spoof-resistant client IP.
	 * @param   RateLimiter       $rateLimiter  Generic multi-window enforcement.
	 * @param   array             $rateLimits   Per-platform window overrides keyed
	 *                                          by cooldown / per_minute / per_hour /
	 *                                          per_day / per_campaign_per_day. Each
	 *                                          is clamped to its hard bound; missing
	 *                                          or non-numeric keys keep the default.
	 */
	public function __construct(
		AntiBotChallenge $challenge,
		ClientIpResolver $ipResolver,
		RateLimiter $rateLimiter,
		array $rateLimits = []
	) {
		$this->challenge   = $challenge;
		$this->ipResolver  = $ipResolver;
		$this->rateLimiter = $rateLimiter;

		$this->cooldown          = $this->resolveLimit($rateLimits, 'cooldown', self::DEFAULT_RATE_LIMIT_WINDOW, self::MIN_RATE_LIMIT_WINDOW, self::MAX_RATE_LIMIT_WINDOW);
		$this->perMinute         = $this->resolveLimit($rateLimits, 'per_minute', self::DEFAULT_RATE_LIMIT_GLOBAL_PER_MINUTE, 1, self::MAX_RATE_LIMIT_GLOBAL_PER_MINUTE);
		$this->perHour           = $this->resolveLimit($rateLimits, 'per_hour', self::DEFAULT_RATE_LIMIT_GLOBAL_PER_HOUR, 1, self::MAX_RATE_LIMIT_GLOBAL_PER_HOUR);
		$this->perDay            = $this->resolveLimit($rateLimits, 'per_day', self::DEFAULT_RATE_LIMIT_GLOBAL_PER_DAY, 1, self::MAX_RATE_LIMIT_GLOBAL_PER_DAY);
		$this->perCampaignPerDay = $this->resolveLimit($rateLimits, 'per_campaign_per_day', self::DEFAULT_RATE_LIMIT_PER_CAMPAIGN_PER_DAY, 1, self::MAX_RATE_LIMIT_PER_CAMPAIGN_PER_DAY);
	}

	/**
	 * Resolve one window: take the override when numeric, fall back to the
	 * default otherwise, then clamp the result into [$min, $max].
	 *
	 * @param   array   $overrides  Raw per-platform values.
	 * @param   string  $key        Window key to read.
	 * @param   int     $default    Value used when the override is absent.
	 * @param   int     $min        Hard floor.
	 * @param   int     $max        Hard ceiling.
	 *
	 * @return  int
	 *
	 * @since   1.0.0
	 */
	private function resolveLimit(array $overrides, string $key, int $default, int $min, int $max): int
	{
		$value = isset($overrides[$key]) && is_numeric($overrides[$key]) ? (int) $overrides[$key] : $default;

		return max($min, min($max, $value));
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
			$this->cooldown,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_COOLDOWN'
		);
		$this->rateLimiter->enforceWindow(
			$globalBucket,
			$this->perMinute,
			60,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_RATE_LIMIT_MINUTE'
		);
		$this->rateLimiter->enforceWindow(
			$globalBucket,
			$this->perHour,
			3600,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_RATE_LIMIT_HOUR'
		);
		$this->rateLimiter->enforceWindow(
			$globalBucket,
			$this->perDay,
			86400,
			'COM_EMUNDUS_PUBLIC_CAMPAIGN_APPLICATION_RATE_LIMIT_DAY'
		);

		// 4. Per-campaign window.
		$this->rateLimiter->enforceWindow(
			$campaignBucket,
			$this->perCampaignPerDay,
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