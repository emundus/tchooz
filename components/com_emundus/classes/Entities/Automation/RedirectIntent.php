<?php

namespace Tchooz\Entities\Automation;

/**
 * A redirect intention emitted by an action (the "decision"), with no side effect. The transport
 * (fetch response or full-page $app->redirect()) is decided elsewhere, at the HTTP entry point,
 * from the intent collected in RedirectIntentRegistry.
 */
class RedirectIntent
{
	public function __construct(
		private string $url,
		private ?string $source = null
	) {}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getSource(): ?string
	{
		return $this->source;
	}
}
