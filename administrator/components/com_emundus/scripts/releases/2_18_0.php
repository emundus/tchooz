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

class Release2_18_0Installer extends ReleaseInstaller
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
			$updateOwnerAction = \EmundusHelperUpdate::createNewAction(
				'update_owner',
				['multi' => true, 'c' => 1, 'r' => 0, 'u' => 0, 'd' => 0],
				'',
				'',
				1
			);
			$this->tasks[]     = !empty($updateOwnerAction);

			// set program id for existing campaigns
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=files&layout=updateowner&format=raw'));
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

				$data            = [
					'menutype'          => 'actions',
					'title'             => 'Modifier le propriétaire',
					'alias'             => 'update-owner',
					'path'              => 'update-owner',
					'link'              => 'index.php?option=com_emundus&view=files&layout=updateowner&format=raw',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
					'note'              => 'update_owner|c|1',
				];
				$updateOwnerMenu = \EmundusHelperUpdate::addJoomlaMenu($data, $headingId);
				\EmundusHelperUpdate::insertFalangTranslation(1, $updateOwnerMenu['id'], 'menu', 'title', 'Update the owner');
			}

			// Remove foreign key constraint on user column for form tables to avoid issues with user deletion in old platforms
			$query = "SELECT DISTINCT CONCAT(
  'ALTER TABLE `', kcu.TABLE_NAME,
  '` DROP FOREIGN KEY `', kcu.CONSTRAINT_NAME, '`;'
) AS drop_sql
FROM information_schema.KEY_COLUMN_USAGE kcu
WHERE kcu.TABLE_SCHEMA = DATABASE()
  AND kcu.REFERENCED_TABLE_NAME = 'jos_emundus_users'
  AND kcu.REFERENCED_COLUMN_NAME = 'user_id'
  AND kcu.COLUMN_NAME = 'user'
  AND (kcu.TABLE_NAME LIKE 'jos_emundus_1%' OR kcu.TABLE_NAME LIKE 'jos_emundus_evaluations%');";
			$this->db->setQuery($query);
			$dropConstraintsSql = $this->db->loadColumn();

			foreach ($dropConstraintsSql as $sql)
			{
				$this->db->setQuery($sql);
				$this->tasks[] = $this->db->execute();
			}

			$installed = \EmundusHelperUpdate::installExtension('plg_fabrik_element_numeric', 'numeric', null, 'plugin', 1, 'fabrik_element');
			$this->tasks[] = $installed;
			if (!$installed)
			{
				$result['message'] .= 'Failed to install numeric plugin. ';
			}

			$installed = \EmundusHelperUpdate::installExtension('plg_fabrik_element_emundus_calculation', 'emundus_calculation', null, 'plugin', 1, 'fabrik_element');
			$this->tasks[] = $installed;
			if (!$installed)
			{
				$result['message'] .= 'Failed to install emundus_calculation plugin. ';
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
