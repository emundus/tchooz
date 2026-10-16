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

class Release2_25_4Installer extends ReleaseInstaller
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
			$this->tasks[] = EmundusHelperUpdate::disableEmundusPlugins('emunduswaitingroom', 'system');

			$this->deleteTagAssociationNoLongerAvailable();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function deleteTagAssociationNoLongerAvailable(): void
	{
		$query = $this->db->createQuery();

		$query->select('id')
			->from($this->db->qn('#__emundus_setup_action_tag'));
		$this->db->setQuery($query);
		$actionTags = array_map('intval', (array) $this->db->loadColumn());

		if (empty($actionTags)) {
			return;
		}

		$query->clear()
			->delete($this->db->qn('#__emundus_tag_assoc'))
			->where($this->db->qn('id_tag') . ' NOT IN (' . implode(',', $actionTags) . ')');
		$this->db->setQuery($query);
		$this->tasks[] = $this->db->execute();
	}
}
