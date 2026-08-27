<?php

namespace Tchooz\Entities\Fields;

/**
 * Read-only value shown with a copy button, for text the administrator has to paste elsewhere.
 * The value is never stored: it is computed by the frontend, which knows context the
 * configuration class does not, such as the site origin or the synchronizer id.
 */
class CopyableTextField extends Field
{
	public static function getType(): string
	{
		return 'copyable_text';
	}

	/**
	 * @inheritDoc
	 */
	public function toSchema(): array
	{
		return $this->defaultSchema();
	}
}
