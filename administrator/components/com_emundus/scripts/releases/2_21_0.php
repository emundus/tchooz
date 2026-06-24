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
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\PublicAccess\PublicApplicationGuard;

class Release2_21_0Installer extends ReleaseInstaller
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
			// Seed the public-application rate-limit windows on the public-session
			// addon with the guard's defaults. Only missing keys are written, so an
			// admin who already tuned a window keeps their value. Defaults come from
			// the guard constants, which stay the single source of truth.
			$addonRepository    = new AddonRepository();
			$publicSessionAddon = $addonRepository->getByName(AddonEnum::PUBLIC_SESSION->value);

			if (!empty($publicSessionAddon))
			{
				$defaults = [
					'rate_limit_cooldown'             => PublicApplicationGuard::DEFAULT_RATE_LIMIT_WINDOW,
					'rate_limit_per_minute'           => PublicApplicationGuard::DEFAULT_RATE_LIMIT_GLOBAL_PER_MINUTE,
					'rate_limit_per_hour'             => PublicApplicationGuard::DEFAULT_RATE_LIMIT_GLOBAL_PER_HOUR,
					'rate_limit_per_day'              => PublicApplicationGuard::DEFAULT_RATE_LIMIT_GLOBAL_PER_DAY,
					'rate_limit_per_campaign_per_day' => PublicApplicationGuard::DEFAULT_RATE_LIMIT_PER_CAMPAIGN_PER_DAY,
				];

				$params  = $publicSessionAddon->getParams();
				$changed = false;
				foreach ($defaults as $key => $value)
				{
					if (!array_key_exists($key, $params))
					{
						$params[$key] = $value;
						$changed      = true;
					}
				}

				if ($changed)
				{
					$publicSessionAddon->setParams($params);
					$this->tasks[] = $addonRepository->flush($publicSessionAddon);
				}
			}

			$tableContactsFiles = \EmundusHelperUpdate::createTable(
				'jos_emundus_contacts_files',
				[
					new \EmundusTableColumn('fnum', \EmundusColumnTypeEnum::VARCHAR, 28, false),
					new \EmundusTableColumn('contact_id', \EmundusColumnTypeEnum::INT, null, false),
				],
				[
					new \EmundusTableForeignKey('#__emundus_contact_files_fnum___fk', 'fnum', 'jos_emundus_campaign_candidature', 'fnum', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
					new \EmundusTableForeignKey('#__emundus_contact_files_contact_id___fk', 'contact_id', 'jos_emundus_contacts', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),

				]
			);
			$this->tasks[] = $tableContactsFiles['status'];
			if (!$tableContactsFiles['status'])
			{
				$result['message'] .= "\n" . $tableContactsFiles['message'];
			}

			$tableOrganizationsFiles = \EmundusHelperUpdate::createTable(
				'jos_emundus_organizations_files',
				[
					new \EmundusTableColumn('fnum', \EmundusColumnTypeEnum::VARCHAR, 28, false),
					new \EmundusTableColumn('organization_id', \EmundusColumnTypeEnum::INT, null, false),
				],
				[
					new \EmundusTableForeignKey('#__emundus_organizations_files_fnum___fk', 'fnum', 'jos_emundus_campaign_candidature', 'fnum', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
					new \EmundusTableForeignKey('#__emundus_organizations_files_orga_id___fk', 'organization_id', 'jos_emundus_organizations', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),

				]
			);
			$this->tasks[] = $tableOrganizationsFiles['status'];
			if (!$tableOrganizationsFiles['status'])
			{
				$result['message'] .= "\n" . $tableOrganizationsFiles['message'];
			}

			$query = $this->db->createQuery();
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=files&layout=updateassociatedcontacts&format=raw'));
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
					'title'             => 'Modifier les contacts associés',
					'alias'             => 'update-associated-contacts',
					'path'              => 'update-associated-contacts',
					'link'              => 'index.php?option=com_emundus&view=files&layout=updateassociatedcontacts&format=raw',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
					'note'              => ActionEnum::CONTACT->value . '|u|1',
				];
				$generateUpdateAssociatedContactsMenu = \EmundusHelperUpdate::addJoomlaMenu($data, $headingId);
				\EmundusHelperUpdate::insertFalangTranslation(1, $generateUpdateAssociatedContactsMenu['id'], 'menu', 'title', 'Update associated contacts');
			}

			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__menu'))
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_emundus&view=files&layout=updateassociatedorganizations&format=raw'));
			$this->db->setQuery($query);
			$existingOrganizationsMenu = $this->db->loadResult();

			if (empty($existingOrganizationsMenu))
			{
				$query->clear()
					->select('id')
					->from($this->db->quoteName('#__menu'))
					->where($this->db->quoteName('alias') . ' = ' . $this->db->quote('2014-09-25-11-03-52'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('heading'));
				$this->db->setQuery($query);
				$headingId = $this->db->loadResult();

				$data = [
					'menutype'          => 'actions',
					'title'             => 'Modifier les organisations associées',
					'alias'             => 'update-associated-organizations',
					'path'              => 'update-associated-organizations',
					'link'              => 'index.php?option=com_emundus&view=files&layout=updateassociatedorganizations&format=raw',
					'type'              => 'component',
					'component_id'      => ComponentHelper::getComponent('com_emundus')->id,
					'template_style_id' => 0,
					'params'            => [],
					'note'              => ActionEnum::ORGANIZATION->value . '|u|1',
				];
				$generateUpdateAssociatedOrganizationsMenu = \EmundusHelperUpdate::addJoomlaMenu($data, $headingId);
				\EmundusHelperUpdate::insertFalangTranslation(1, $generateUpdateAssociatedOrganizationsMenu['id'], 'menu', 'title', 'Update associated organizations');
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
