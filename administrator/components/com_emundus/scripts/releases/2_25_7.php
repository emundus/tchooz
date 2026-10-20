<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts\releases;

use EmundusHelperUpdate;
use scripts\ReleaseInstaller;

class Release2_25_7Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$this->conditionValueText();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Throwable $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * A composite or a reference value is stored as JSON in `value`, which outgrows 255 characters as
	 * soon as it carries a few sub-fields or transformations.
	 *
	 * @throws \Exception
	 */
	private function conditionValueText(): void
	{
		$this->tasks[] = EmundusHelperUpdate::alterColumn('jos_emundus_condition', 'value', 'TEXT', null, 1)['status'];
	}

}
