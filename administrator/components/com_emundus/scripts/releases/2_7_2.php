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

class Release2_7_2Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$tasks[] = EmundusHelperUpdate::addCustomEvents([
				['label' => 'onAfterRemoveSharedUser','published' => 1, 'category' => 'Collaboration', 'description' => 'After a user has been removed from collaborators']
			])['status'];

			$tasks[] = EmundusHelperUpdate::addCustomEvents([
				['label' => 'onBeforeGenerateLetters','published' => 1, 'category' => 'Letters', 'description' => 'Before generating letters, can be used to modify the letter data']
			])['status'];

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