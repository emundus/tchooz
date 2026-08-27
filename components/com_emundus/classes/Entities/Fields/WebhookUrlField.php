<?php

namespace Tchooz\Entities\Fields;

/**
 * Read-only field displaying the callback URL an integration must declare on the provider side.
 * The value is built by the frontend, which is the only side that knows the site origin and the
 * synchronizer id.
 */
class WebhookUrlField extends Field
{
	public static function getType(): string
	{
		return 'webhook_url';
	}

	/**
	 * @inheritDoc
	 */
	public function toSchema(): array
	{
		return $this->defaultSchema();
	}
}
