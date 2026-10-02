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
use Tchooz\Entities\Addons\AddonEntity;
use Tchooz\Enums\Addons\AddonEnum;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Services\Addons\Configurations\CollaborateAddonConfiguration;

class Release2_25_2Installer extends ReleaseInstaller
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
			$this->registerCollaborateAddon();

			$this->replaceApplicantInTagsDescription();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Collaboration used to be enabled and configured on the mod_emundus_applications module
	 * (mod_emundus_applications_actions, mod_emundus_applications_collaborate_default_rights) and on the
	 * collaborate_link component parameter. It is now an addon: carry the existing setup over.
	 */
	private function registerCollaborateAddon(): void
	{
		$addonRepository = new AddonRepository();

		if (!empty($addonRepository->getByName(AddonEnum::COLLABORATE->value)))
		{
			return;
		}

		$query = $this->db->createQuery()
			->select($this->db->quoteName('params'))
			->from($this->db->quoteName('#__modules'))
			->where($this->db->quoteName('module') . ' = ' . $this->db->quote('mod_emundus_applications'))
			->where($this->db->quoteName('published') . ' = 1');
		$this->db->setQuery($query);
		$modulesParams = $this->db->loadColumn();

		$activated     = false;
		$defaultRights = CollaborateAddonConfiguration::DEFAULT_RIGHTS_VALUE;

		foreach ($modulesParams as $moduleParams)
		{
			$moduleParams = json_decode($moduleParams, true);

			if (!empty($moduleParams['mod_emundus_applications_actions']) && in_array('collaborate', (array) $moduleParams['mod_emundus_applications_actions']))
			{
				$activated = true;

				if (!empty($moduleParams['mod_emundus_applications_collaborate_default_rights']))
				{
					$defaultRights = array_values(array_intersect(
						(array) $moduleParams['mod_emundus_applications_collaborate_default_rights'],
						CollaborateAddonConfiguration::RIGHTS
					));
				}
				else
				{
					// Never saved: shareFileWith() used to grant every right in that case
					$defaultRights = CollaborateAddonConfiguration::RIGHTS;
				}
				break;
			}
		}

		$params = [
			CollaborateAddonConfiguration::CONFIGURATION_GROUP => [
				CollaborateAddonConfiguration::DEFAULT_RIGHTS  => $defaultRights,
				CollaborateAddonConfiguration::ACCEPTANCE_MENU => (string) ComponentHelper::getParams('com_emundus')->get('collaborate_link', ''),
			],
		];

		$addon         = new AddonEntity(AddonEnum::COLLABORATE->value, $activated, false, true, $params);
		$this->tasks[] = $addonRepository->flush($addon);
	}

	private function replaceApplicantInTagsDescription(): void
	{
		$updated = true;

		$query = $this->db->createQuery();
		$query->clear()
			->select('id,description')
			->from($this->db->qn('#__emundus_setup_tags'));
		$this->db->setQuery($query);
		$tags = $this->db->loadObjectList();

		// Ordered so plural/capitalized forms match before their shorter variants.
		$replacements = [
			'Candidats' => 'Déposants',
			'candidats' => 'déposants',
			'Candidat'  => 'Déposant',
			'candidat'  => 'déposant',
		];

		foreach ($tags as $tag)
		{
			$query->clear()
				->select('id, value')
				->from($this->db->qn('#__falang_content'))
				->where($this->db->qn('reference_table') . ' = ' . $this->db->q('emundus_setup_tags'))
				->where($this->db->qn('reference_field') . ' = ' . $this->db->q('description'))
				->where($this->db->qn('reference_id') . ' = ' . (int)$tag->id);
			$this->db->setQuery($query);
			$falangTranslations = $this->db->loadObjectList();
			foreach ($falangTranslations as $falangTranslation)
			{
				if (empty($falangTranslation->value))
				{
					continue;
				}

				$newDescription = strtr($falangTranslation->value, $replacements);
				if ($newDescription === $falangTranslation->value)
				{
					continue;
				}

				$falangTranslation->value = $newDescription;
				$this->tasks[] = $this->db->updateObject('#__falang_content', $falangTranslation, 'id');
			}

			if (empty($tag->description))
			{
				continue;
			}

			$newDescription = strtr($tag->description, $replacements);
			if ($newDescription === $tag->description)
			{
				continue;
			}

			$tag->description = $newDescription;

			$this->tasks[] = $this->db->updateObject('#__emundus_setup_tags', $tag, 'id');
		}
	}
}
