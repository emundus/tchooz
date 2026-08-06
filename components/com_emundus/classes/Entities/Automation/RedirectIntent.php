<?php

namespace Tchooz\Entities\Automation;

/**
 * Intention de redirection émise par une action (la « décision »), sans effet de bord.
 * Le transport (réponse fetch ou $app->redirect() pleine page) est décidé ailleurs, au point
 * d'entrée HTTP, à partir de l'intent collecté dans RedirectIntentRegistry.
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
