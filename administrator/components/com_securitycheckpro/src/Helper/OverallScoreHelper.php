<?php
declare(strict_types=1);

/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Helper;

defined('_JEXEC') or die();

class OverallScoreHelper
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $info */
    public static function score(array $info, int $option): int
    {
        return match ($option) {
            1 => self::scoreJoomlaConfig($info),
            2 => self::scoreFirewall($info),
            default => 0,
        };
    }

    // -------------------------------------------------------------------------
    // Scoring — Case 1: Joomla Configuration   (max 100)
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $info */
    private static function scoreJoomlaConfig(array $info): int
    {
        if (self::getBool($info, 'kickstart_exists')) {
            return 2;
        }

        $overall = 0;

        $coreInstalled = self::getStr($info, 'coreinstalled');
        $coreLatest    = self::getStr($info, 'corelatest');
        if ($coreInstalled !== '' && $coreLatest !== '' && version_compare($coreInstalled, $coreLatest, '==')) {
            $overall += 8; // was 4 — core outdated is the most exploited vector
        }

        // 'unread_logs' in backend, 'logs_pending' in the frontend API (JsonModel)
        $unreadLogs = $info['unread_logs'] ?? $info['logs_pending'] ?? PHP_INT_MAX;
        if (is_numeric($unreadLogs) && (int) $unreadLogs <= 10) {
            $overall += 1; // was 5 — operational metric, not a security control
        }

        if (self::getInt($info, 'files_with_incorrect_permissions', -1) === 0) { $overall += 5;  }
        if (self::getInt($info, 'files_with_bad_integrity', -1)         === 0) { $overall += 10; }
        if (self::getInt($info, 'vuln_extensions', -1)                  === 0) { $overall += 30; }
        if (self::getInt($info, 'suspicious_files', -1)                 === 0) { $overall += 15; }
        if (self::getBool($info, 'backend_protection'))                        { $overall += 10; }

        $fwOptions = self::getArr($info, 'firewall_options');
        if (($fwOptions['forbid_new_admins'] ?? 0) == 1) { $overall += 5; }

        if (self::getInt($info, 'twofactor_enabled') >= 1) { $overall += 10; }

        $ht = self::getArr($info, 'htaccess_protection');
        foreach (['xframe_options', 'sts_options', 'xss_options', 'csp_policy', 'referrer_policy', 'prevent_mime_attacks'] as $k) {
            if (self::getNestedBool($ht, $k)) { $overall += 1; }
        }

        return $overall;
    }

    // -------------------------------------------------------------------------
    // Scoring — Case 2: Web Firewall   (max 100)
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $info */
    private static function scoreFirewall(array $info): int
    {
        if (!self::getBool($info, 'firewall_plugin_enabled')) {
            return 2;
        }

        $overall   = 10; // firewall enabled
        $fwOptions = self::getArr($info, 'firewall_options');
        $ht        = self::getArr($info, 'htaccess_protection');

        $asString = static function ($v): string {
            return is_array($v) ? implode(',', array_map('strval', $v)) : (string) $v;
        };
        $stripTagsExceptions = $asString($fwOptions['strip_tags_exceptions'] ?? '');
        $sqlPatternExceptions = $asString($fwOptions['sql_pattern_exceptions'] ?? '');
        $lfiExceptions        = $asString($fwOptions['lfi_exceptions'] ?? '');

        if (!empty($fwOptions['dynamic_blacklist']))           { $overall += 10; }
        if (!empty($fwOptions['logs_attacks']))                { $overall += 2;  }
        if (!empty($fwOptions['second_level']))                { $overall += 2;  }
        if (!str_contains($stripTagsExceptions, '*'))          { $overall += 4;  }
        if (!str_contains($sqlPatternExceptions, '*'))         { $overall += 4;  }
        if (!str_contains($lfiExceptions, '*'))                { $overall += 4;  }
        if (!empty($fwOptions['session_protection_active']))   { $overall += 2;  }
        if (!empty($fwOptions['session_hijack_protection']))   { $overall += 2;  }
        if (!empty($fwOptions['upload_scanner_enabled']))      { $overall += 4;  }
        if (self::getBool($info, 'spam_protection_plugin_enabled')) { $overall += 2; }

        // Cron checks: gradual scale instead of binary < 2 days
        $overall += self::cronScore(self::getStr($info, 'last_check'));
        $overall += self::cronScore(self::getStr($info, 'last_check_integrity'));

        $htMap = [
            'prevent_access'                => 6,
            'prevent_unauthorized_browsing' => 4,
            'file_injection_protection'     => 4,
            'self_environ'                  => 4,
            'xframe_options'                => 2,
            'prevent_mime_attacks'          => 2,
            'default_banned_list'           => 3,
            'disable_server_signature'      => 3,
            'disallow_php_eggs'             => 3,
            'disallow_sensible_files_access'=> 3,
        ];
        foreach ($htMap as $k => $pts) {
            if (self::getNestedBool($ht, $k)) { $overall += $pts; }
        }

        return $overall;
    }

    // -------------------------------------------------------------------------
    // Cron gradual scoring (max 10 per check, same ceiling as before)
    // -------------------------------------------------------------------------

    private static function cronScore(string $dateStr): int
    {
        if ($dateStr === '') {
            return 0;
        }
        $ts = strtotime($dateStr);
        if ($ts === false) {
            return 0;
        }
        $days = (int) floor((time() - $ts) / 86400);

        if ($days < 1)  { return 10; }
        if ($days < 3)  { return 5;  }
        if ($days < 7)  { return 2;  }
        return 0;
    }

    // -------------------------------------------------------------------------
    // Safe getters
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $a */
    private static function getInt(array $a, string $k, int $def = 0): int
    {
        $v = $a[$k] ?? $def;
        return is_numeric($v) ? (int) $v : $def;
    }

    /** @param array<string, mixed> $a */
    private static function getBool(array $a, string $k, bool $def = false): bool
    {
        return (bool) ($a[$k] ?? $def);
    }

    /** @param array<string, mixed> $a */
    private static function getStr(array $a, string $k, string $def = ''): string
    {
        $v = $a[$k] ?? $def;
        return is_string($v) ? $v : (string) $v;
    }

    /**
     * @param  array<string, mixed> $a
     * @return array<string, mixed>
     */
    private static function getArr(array $a, string $k): array
    {
        $v = $a[$k] ?? [];
        return is_array($v) ? $v : (array) $v;
    }

    /** @param array<string, mixed> $a */
    private static function getNestedBool(array $a, string $k, bool $def = false): bool
    {
        $v = $a[$k] ?? $def;
        if (is_bool($v))    { return $v; }
        if (is_numeric($v)) { return ((int) $v) === 1; }
        if (is_string($v))  { return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true); }
        return $def;
    }
}
