<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;

class Release2_9_5Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$tasks = [];

		try
		{
			$tasks[] = EmundusHelperUpdate::updateOverrideTag('COM_FABRIK_REQUIRED_ICON_NOT_DISPLAYED', ['en-GB' => 'Tous les champs sont obligatoires sauf mention contraire'], ['en-GB' => 'All fields are mandatory unless otherwise stated.']);

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}