<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Factories\Language\LanguageFactory;
use scripts\ReleaseInstaller;
use Tchooz\Repositories\Language\LanguageRepository;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\Reference\InternalReferenceEntity;

class Release2_19_0Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'program_id', 'INT', 11)['status'];

			// set program id for existing campaigns
			$query->clear()
				->select('esc.id, esp.id AS program_id')
				->from($this->db->quoteName('jos_emundus_setup_campaigns', 'esc'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('esp.code'))
				->where($this->db->quoteName('esc.program_id') . ' IS NULL');

			$campaigns = $this->db->setQuery($query)->loadObjectList();

			foreach ($campaigns as $campaign)
			{
				$query->clear()
					->update($this->db->quoteName('jos_emundus_setup_campaigns'))
					->set($this->db->quoteName('program_id') . ' = ' . (int) $campaign->program_id)
					->where($this->db->quoteName('id') . ' = ' . (int) $campaign->id);
				$this->tasks[] = $this->db->setQuery($query)->execute();
			}

			// add foreign key constraint if not exists
			// check if the foreign key already exists
			$query->clear()
				->select('CONSTRAINT_NAME')
				->from($this->db->quoteName('information_schema.KEY_COLUMN_USAGE'))
				->where($this->db->quoteName('TABLE_NAME') . ' = ' . $this->db->quote('jos_emundus_setup_campaigns'))
				->where($this->db->quoteName('COLUMN_NAME') . ' = ' . $this->db->quote('program_id'))
				->where($this->db->quoteName('CONSTRAINT_NAME') . ' = ' . $this->db->quote('fk_jesc_program_id'));
			$fkExists = $this->db->setQuery($query)->loadResult();

			if (!$fkExists)
			{
				$query = "ALTER TABLE `jos_emundus_setup_campaigns`
				ADD CONSTRAINT `fk_jesc_program_id`
				FOREIGN KEY (`program_id`)
				REFERENCES `jos_emundus_setup_programmes`(`id`)
				ON DELETE SET NULL
				ON UPDATE CASCADE";
				$this->tasks[] = $this->db->setQuery($query)->execute();
			}

			$this->tasks[] = \EmundusHelperUpdate::createNewAction(name: 'custom_reference', crud: ['multi' => 1, 'c' => 1, 'r' => 0, 'u' => 0, 'd' => 1], published: 1);

			$this->tasks[] = \EmundusHelperUpdate::makeFromEntity(InternalReferenceEntity::class);
			$this->tasks[] = \EmundusHelperUpdate::makeFromEntity(ApplicationFileEntity::class);
			$this->tasks[] = \EmundusHelperUpdate::addColumnIndex('jos_emundus_internal_reference', 'sequence_int')['status'];

			$query = $this->db->createQuery();
			$query->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=files&layout=generatereference&format=raw'));
			$this->db->setQuery($query);
			$existingMenu = $this->db->loadResult();

			if (empty($existingMenu))
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('2014-09-25-11-03-52'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
				$this->db->setQuery($query);
				$headingId = $this->db->loadResult();

				$data                  = [
					'menutype'          => 'actions',
					'title'             => 'Générer une référence',
					'alias'             => 'generate-reference',
					'path'              => 'generate-reference',
					'link'              => 'index.php?option=com_emundus&view=files&layout=generatereference&format=raw',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
					'note'              => 'custom_reference|c|1',
				];
				$generateReferenceMenu = \EmundusHelperUpdate::addJoomlaMenu($data, $headingId, 1);
				\EmundusHelperUpdate::insertFalangTranslation(1, $generateReferenceMenu['id'], 'menu', 'title', 'Generate a reference');
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=references&layout=history&format=raw'))
				->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote('application'));
			$this->db->setQuery($query);
			$existingMenu = $this->db->loadResult();

			if (empty($existingMenu))
			{
				$data                  = [
					'menutype'          => 'application',
					'title'             => 'Historique des références',
					'alias'             => 'references-history',
					'path'              => 'references-history',
					'link'              => 'index.php?option=com_emundus&view=references&layout=history&format=raw',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
					'note'              => 'custom_reference|r',
					'access'            => 6
				];
				$generateReferenceMenu = \EmundusHelperUpdate::addJoomlaMenu($data, 1, 1);
				\EmundusHelperUpdate::insertFalangTranslation(1, $generateReferenceMenu['id'], 'menu', 'title', 'Reference\'s history');
			}

			$query->clear()
				->select('value')
				->from($this->db->quoteName('#__emundus_setup_config'))
				->where($this->db->quoteName('namekey') . ' = ' . $this->db->quote('custom_reference_format'));
			$this->db->setQuery($query);
			$customReferenceModule = $this->db->loadResult();
			if(empty($customReferenceModule))
			{
				$defaultParameters = [
					'blocks' => [],
					'separator' => '-',
					'triggering_status' => null,
					'show_to_applicant' => false,
					'show_in_files' => false,
					'sequence' => [
						'position' => 'end',
						'reset_type' => 'never',
						'length' => 4
					]
				];
				$customReferenceModule = (object) [
					'namekey' => 'custom_reference_format',
					'value' => json_encode($defaultParameters),
					'default' => '[]'
				];
				$this->tasks[] = $this->db->insertObject('#__emundus_setup_config', $customReferenceModule);
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
