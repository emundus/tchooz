<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use scripts\ReleaseInstaller;

class Release2_13_4Installer extends ReleaseInstaller
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
			$query = $this->db->createQuery();

			// Check noprofile
			$query->clear()
				->select('*')
				->from($this->db->quoteName('#__emundus_setup_profiles'))
				->where($this->db->quoteName('id') . ' = 1000');
			$this->db->setQuery($query);
			$noprofile = $this->db->loadObject();

			if (empty($noprofile))
			{
				$insert = (object)
				[
					'id'             => 1000,
					'label'          => 'noprofile',
					'description'    => 'Default profile assigned to users without profile.',
					'acl_aro_groups' => 2,
					'status'         => 0,
					'published'      => 1
				];
				$this->db->insertObject('#__emundus_setup_profiles', $insert);
			}

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}