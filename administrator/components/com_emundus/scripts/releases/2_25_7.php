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

			$this->updateNationalityDatas();

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

	/**
	 * Add missing nationalities to the `data_nationality` reference table.
	 * Idempotent: a nationality is only inserted when its French label is not already present.
	 *
	 * @throws \Exception
	 */
	private function updateNationalityDatas(): void
	{
		$nationalities = [
			['label_fr' => 'Anglaise', 'label_en' => 'English'],
			['label_fr' => 'Kosovare', 'label_en' => 'Kosovar'],
			['label_fr' => 'Hong Kong', 'label_en' => 'Hong Kong Chinese'],
		];

		foreach ($nationalities as $nationality)
		{
			$query = $this->db->createQuery();
			$query->select('COUNT(' . $this->db->quoteName('id') . ')')
				->from($this->db->quoteName('data_nationality'))
				->where($this->db->quoteName('label_fr') . ' = ' . $this->db->quote($nationality['label_fr']));
			$this->db->setQuery($query);

			if ((int) $this->db->loadResult() > 0)
			{
				continue;
			}

			$data = (object) $nationality;
			$this->tasks[] = $this->db->insertObject('data_nationality', $data);
		}
	}

}
