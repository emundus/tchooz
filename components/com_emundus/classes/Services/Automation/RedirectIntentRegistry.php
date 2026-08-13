<?php

namespace Tchooz\Services\Automation;

use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\RedirectIntent;

/**
 * Request-scoped collector for the current redirect intent.
 *
 * A redirecting action never calls $app->redirect() itself: it registers its URL here through
 * request(). The HTTP entry point (fetch controller or system plugin for full-page requests) reads
 * and clears the intent through consume() and decides on the transport.
 *
 * The lifetime is the HTTP request, deliberately decoupled from AutomationExecutionContext whose
 * endProcessing() resets its state at the end of each automation chain — the intent would otherwise
 * be destroyed before the entry point could read it. Actions only register on the site client, where
 * a transport exists, so nothing accumulates in queued-task batches or CLI runs.
 */
class RedirectIntentRegistry
{
	private static ?RedirectIntent $pending = null;

	/**
	 * Register a redirect request. Policy: first come, first served. Any later request is ignored
	 * and logged (never a silent drop).
	 */
	public static function request(RedirectIntent $intent): void
	{
		if (empty($intent->getUrl()))
		{
			return;
		}

		if (self::$pending !== null)
		{
			self::log('Redirect intent "' . $intent->getSource() . '" (' . $intent->getUrl() . ') dropped, "' . self::$pending->getSource() . '" already requested');

			return;
		}

		self::$pending = $intent;
	}

	/**
	 * Read and clear the current intent. Called by the transport.
	 */
	public static function consume(): ?RedirectIntent
	{
		$intent        = self::$pending;
		self::$pending = null;

		return $intent;
	}

	/**
	 * Test seam: static state survives between test methods, so each one starts from a clean slate.
	 */
	public static function reset(): void
	{
		self::$pending = null;
	}

	private static function log(string $message): void
	{
		Log::addLogger(['text_file' => 'com_emundus.action.log.php'], Log::ALL, ['com_emundus.action']);
		Log::add($message, Log::WARNING, 'com_emundus.action');
	}
}
