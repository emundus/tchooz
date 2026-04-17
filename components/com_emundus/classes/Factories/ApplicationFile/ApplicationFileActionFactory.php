<?php

namespace Tchooz\Factories\ApplicationFile;

use Tchooz\Entities\ApplicationFile\Actions\CustomApplicationFileAction;

class ApplicationFileActionFactory
{
	/**
	 * @param   string  $json
	 *
	 * @return array<CustomApplicationFileAction>
	 */
	public static function customApplicationActionsFromJson(string $json): array
	{
		$actions = [];

		if (!empty($json))
		{
			$json = json_decode($json);

		}

		return $actions;
	}
}