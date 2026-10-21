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

			$this->addYamlVariables();

			$this->updateApplicantStepTypeWording();

			$this->trimEmailCategories();

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

	private function addYamlVariables(): void
	{
		\EmundusHelperUpdate::addYamlVariable('cards', 'block', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'tchoozy', false, true, false);

		\EmundusHelperUpdate::addYamlVariable('weight-h1', '500', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('weight-h2', '500', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('weight-h3', '500', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('weight-h4', '500', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('weight-h5', '500', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('weight-h6', '500', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('weight-body', '400', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);

		\EmundusHelperUpdate::addYamlVariable('text-transform-h1', 'none', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('text-transform-h2', 'none', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('text-transform-h3', 'none', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('text-transform-h4', 'none', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('text-transform-h5', 'none', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('text-transform-h6', 'none', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
		\EmundusHelperUpdate::addYamlVariable('text-transform-button', 'none', JPATH_ROOT . '/templates/g5_helium/custom/config/default/styles.yaml', 'font', false, true, false);
	}

	private function updateApplicantStepTypeWording(): void
	{
		$query = $this->db->createQuery();
		$query->update($this->db->qn('#__emundus_setup_step_types'))
			->set($this->db->qn('label') . ' = ' . $this->db->q('Déposant'))
			->where('TRIM(' . $this->db->qn('label') . ') = ' . $this->db->q('Candidat'));
		$this->db->setQuery($query);
		$this->tasks[] = $this->db->execute();
	}

	/**
	 * Trim leading and trailing whitespace from existing email categories.
	 * Idempotent: only rows whose category differs from its trimmed value are updated.
	 *
	 * @throws \Exception
	 */
	private function trimEmailCategories(): void
	{
		$query = $this->db->createQuery();
		$query->update($this->db->qn('#__emundus_setup_emails'))
			->set($this->db->qn('category') . ' = TRIM(' . $this->db->qn('category') . ')')
			->where($this->db->qn('category') . ' IS NOT NULL')
			->where($this->db->qn('category') . ' != TRIM(' . $this->db->qn('category') . ')');
		$this->db->setQuery($query);
		$this->tasks[] = $this->db->execute();
	}
}
