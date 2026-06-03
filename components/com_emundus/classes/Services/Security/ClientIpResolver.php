<?php
/**
 * @package    Tchooz\Services\Security
 * @copyright  Emundus
 * @license    GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Security;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Input\Input;

defined('_JEXEC') or die;

/**
 * Resolves the real client IP in a spoof-resistant way.
 *
 * X-Forwarded-For is a freely forgeable request header. It is only trusted
 * when the request actually arrives from a known trusted proxy; otherwise
 * REMOTE_ADDR is used, which cannot be spoofed on a direct-access setup.
 *
 * The trusted-proxy list is read from SecurityCheck Pro's configuration so
 * there is a single source of truth for proxy trust across the site. When no
 * proxy is configured (or the component is absent/disabled), resolution
 * degrades safely to REMOTE_ADDR.
 *
 * This class is intentionally free of any rate-limiting or anti-bot concern:
 * it answers exactly one question — "what is the real client IP?" — so it can
 * be reused for logging, auditing or any other endpoint.
 *
 * @since 1.0.0
 */
class ClientIpResolver
{
	/**
	 * Joomla input, used to read the server (request) variables.
	 *
	 * @var Input
	 */
	private Input $input;

	/**
	 * @param   Input  $input  The application input (server bag is used).
	 */
	public function __construct(Input $input)
	{
		$this->input = $input;
	}

	/**
	 * Resolve the best-known client IP.
	 *
	 * @return  string  The client IP (REMOTE_ADDR when XFF cannot be trusted).
	 *
	 * @since   1.0.0
	 */
	public function resolve(): string
	{
		$remoteAddr = $this->input->server->getString('REMOTE_ADDR', '0.0.0.0');

		$trustedProxies = $this->getTrustedProxies();

		// No trusted proxy declared → never trust XFF, use the direct peer.
		if (empty($trustedProxies))
		{
			return $remoteAddr;
		}

		// Request did not come from a trusted proxy → ignore XFF.
		if (!$this->ipInRanges($remoteAddr, $trustedProxies))
		{
			return $remoteAddr;
		}

		$forwarded = $this->input->server->getString('HTTP_X_FORWARDED_FOR', '');
		if (empty($forwarded))
		{
			return $remoteAddr;
		}

		// Walk the chain from right (closest proxy) to left, skipping trusted
		// proxies; the first non-trusted entry is the real client.
		$chain = array_map('trim', explode(',', $forwarded));
		for ($i = count($chain) - 1; $i >= 0; $i--)
		{
			if (!$this->ipInRanges($chain[$i], $trustedProxies))
			{
				return filter_var($chain[$i], FILTER_VALIDATE_IP) ? $chain[$i] : $remoteAddr;
			}
		}

		return $remoteAddr;
	}

	/**
	 * Read the trusted-proxy list from SecurityCheck Pro's configuration.
	 *
	 * Mirrors SecurityCheck Pro's own logic: proxy headers are only trusted
	 * when 'trust_ip_overrides' is enabled AND the proxy list is non-empty.
	 *
	 * @return  array  List of trusted proxy IPs / CIDR ranges (possibly empty).
	 *
	 * @since   1.0.0
	 */
	public function getTrustedProxies(): array
	{
		if (!ComponentHelper::isEnabled('com_securitycheckpro'))
		{
			return [];
		}

		$params = ComponentHelper::getParams('com_securitycheckpro');

		// Align with SecurityCheck Pro: if it does not trust overrides,
		// neither do we — fall back to REMOTE_ADDR.
		if ((int) $params->get('trust_ip_overrides', 0) !== 1)
		{
			return [];
		}

		$raw = (string) $params->get('trusted_proxies', '');
		if (trim($raw) === '')
		{
			return [];
		}

		// Tolerant parsing: commas, semicolons, whitespace or newlines.
		$parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);

		$proxies = [];
		foreach ($parts as $entry)
		{
			$entry  = trim($entry);
			$ipPart = strpos($entry, '/') !== false ? explode('/', $entry)[0] : $entry;
			if (filter_var($ipPart, FILTER_VALIDATE_IP))
			{
				$proxies[] = $entry;
			}
		}

		return $proxies;
	}

	/**
	 * Test whether an IP falls within any of the given IPs / CIDR ranges.
	 * Supports both IPv4 and IPv6.
	 *
	 * @param   string  $ip      The IP to test.
	 * @param   array   $ranges  List of plain IPs or CIDR ranges.
	 *
	 * @return  bool
	 *
	 * @since   1.0.0
	 */
	private function ipInRanges(string $ip, array $ranges): bool
	{
		foreach ($ranges as $range)
		{
			// Plain IP (no CIDR notation)
			if (strpos($range, '/') === false)
			{
				if ($ip === $range)
				{
					return true;
				}
				continue;
			}

			[$subnet, $bits] = explode('/', $range);

			$ipBin     = inet_pton($ip);
			$subnetBin = inet_pton($subnet);

			// Skip malformed entries or IPv4/IPv6 mismatch (different lengths).
			if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin))
			{
				continue;
			}

			$bits  = (int) $bits;
			$bytes = intdiv($bits, 8);
			$rem   = $bits % 8;

			if ($bytes > 0 && strncmp($ipBin, $subnetBin, $bytes) !== 0)
			{
				continue;
			}

			if ($rem === 0)
			{
				return true;
			}

			$mask = chr((0xFF << (8 - $rem)) & 0xFF);
			if ((($ipBin[$bytes] ^ $subnetBin[$bytes]) & $mask) === "\0")
			{
				return true;
			}
		}

		return false;
	}
}