<?php

namespace Tchooz\Services\Sofis;

use Joomla\CMS\Log\Log;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

/**
 * Single registration point of the Sofis log channel. Joomla keys loggers by their options, so the
 * last addLogger() on this file decides its priorities: every Sofis class must register through here.
 *
 * Warnings and errors only by default; everything (debug traces included) when the synchronizer
 * "debug" option or the site debug mode is on.
 */
// todo: make this more generic for re-use for other addons/plugins
final class SofisLogger
{
	public const CHANNEL = 'com_emundus.sofis';

	private const FILE = 'com_emundus.sofis.php';

	private const DEFAULT_PRIORITIES = Log::EMERGENCY | Log::ALERT | Log::CRITICAL | Log::ERROR | Log::WARNING;

	private static bool $registered = false;

	public static function register(): void
	{
		if (self::$registered)
		{
			return;
		}

		self::$registered = true;

		Log::addLogger(['text_file' => self::FILE], self::isDebugEnabled() ? Log::ALL : self::DEFAULT_PRIORITIES, [self::CHANNEL]);
	}

	private static function isDebugEnabled(): bool
	{
		if (defined('JDEBUG') && JDEBUG)
		{
			return true;
		}

		$synchronizer = (new SynchronizerRepository())->getByType('sofis');

		return !empty($synchronizer?->getConfig()['diagnostic']['debug']);
	}
}
