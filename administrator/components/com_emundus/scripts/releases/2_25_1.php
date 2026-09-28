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

class Release2_25_1Installer extends ReleaseInstaller
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
			$query->clear()
				->update($this->db->quoteName('#__emundus_chatroom'))
				->set($this->db->quoteName('status') . ' = 1')
				->where($this->db->quoteName('status') . ' IS NULL');

			$this->tasks[] = ($this->db->setQuery($query))->execute();

			$result = EmundusHelperUpdate::alterColumn('jos_emundus_chatroom', 'status', 'TINYINT', 1, 0, 1);
			$result['message'] .=  $result['message'] . "\n";
			$this->tasks[] = $result['status'];

			$this->deleteTranslationsFalangForm();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function deleteTranslationsFalangForm(): void
	{
		$query = $this->db->createQuery(true);

		$query->select('reference_id')
			->from($this->db->qn('#__falang_content'))
			->where($this->db->qn('value') . ' LIKE ' . $this->db->q('index.php?option=com_fabrik&view=form%'))
			->where($this->db->qn('reference_table') . ' LIKE ' . $this->db->q('menu'));
		$this->db->setQuery($query);
		$falangFormTranslations = $this->db->loadColumn();

		if(!empty($falangFormTranslations))
		{
			$query->clear()
				->delete($this->db->qn('#__falang_content'))
				->where($this->db->qn('reference_table') . ' LIKE ' . $this->db->q('menu'))
				->whereIn($this->db->qn('reference_id'), $falangFormTranslations);
			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();
		}
	}
}
