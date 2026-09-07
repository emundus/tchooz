<?php

namespace Tchooz\Services\Automation;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Full-page transport of the redirect channel: performs the $app->redirect() for the intent
 * collected in RedirectIntentRegistry, when the current request can carry one.
 *
 * A request that answers JSON must not be redirected: fetch() follows a 303 transparently and the
 * caller would parse an HTML page instead of its response. Those requests declare themselves with
 * format=json (or format=raw) and their endpoint consumes the intent to return it in the payload.
 */
class RedirectIntentTransport
{
	public function __construct(private CMSApplicationInterface $app) {}

	/**
	 * Consume the pending intent and redirect to it. Never returns when it does redirect. The intent
	 * is left pending when this request cannot carry a redirect, so that its endpoint can still
	 * return it.
	 */
	public function transportPending(): bool
	{
		if (!$this->isInlineTransportAvailable())
		{
			return false;
		}

		$intent = RedirectIntentRegistry::consume();

		if ($intent === null || empty($intent->getUrl()))
		{
			return false;
		}

		if ($this->isCurrentUrl($intent->getUrl()))
		{
			$this->log('Redirect intent "' . $intent->getSource() . '" (' . $intent->getUrl() . ') dropped, already on that URL');

			return false;
		}

		$this->app->redirect($intent->getUrl());

		return true;
	}

	/**
	 * Whether this request can carry a full-page redirect.
	 */
	public function isInlineTransportAvailable(): bool
	{
		// A console application (scheduled tasks) has neither a menu nor a redirect.
		if (!$this->app instanceof CMSApplication || !$this->app->isClient('site'))
		{
			return false;
		}

		if (in_array($this->app->getInput()->get('format', 'html'), ['json', 'raw'], true))
		{
			return false;
		}

		// jQuery sets this header on every $.ajax call, which covers the legacy back-office calls
		// answering JSON without declaring a format.
		$requestedWith = $this->app->getInput()->server->getString('HTTP_X_REQUESTED_WITH', '');

		return strtolower($requestedWith) !== 'xmlhttprequest';
	}

	/**
	 * Guards against a redirect loop: an intent pointing at the page currently being served.
	 */
	private function isCurrentUrl(string $url): bool
	{
		$url = $this->normalize($url);

		if ($url === $this->normalize(Uri::getInstance()->toString(['path', 'query'])))
		{
			return true;
		}

		// Built like ActionRedirect routes its URLs, otherwise the comparison fails as soon as a
		// language segment is present.
		$active = $this->app->getMenu()->getActive();

		return !empty($active) && $url === $this->normalize(Route::_('index.php?Itemid=' . $active->id, false));
	}

	private function normalize(string $url): string
	{
		return rtrim(urldecode($url), '/');
	}

	private function log(string $message): void
	{
		Log::addLogger(['text_file' => 'com_emundus.action.log.php'], Log::ALL, ['com_emundus.action']);
		Log::add($message, Log::WARNING, 'com_emundus.action');
	}
}
