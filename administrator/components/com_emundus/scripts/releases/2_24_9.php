<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_24_9Installer extends ReleaseInstaller
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
			$this->updateMentionsLegalesArticle();

			$this->inheritApplicationFormDocumentVisibility();
			$this->addSystemFlagOnFilters();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function updateMentionsLegalesArticle(): void
	{
		$query = $this->db->createQuery(true);

		$query->select('id, introtext')
			->from($this->db->qn('#__content'))
			->where($this->db->qn('alias') . ' = ' . $this->db->q('mentions-legales'));
		$this->db->setQuery($query);
		$mentionsLegalesArticle = $this->db->loadObject();

		if(!empty($mentionsLegalesArticle) && !empty($mentionsLegalesArticle->id))
		{
			$mentionsLegalesArticle->introtext = str_replace('website : scaleway.com', 'lien : scaleway.com', $mentionsLegalesArticle->introtext);

			$this->tasks[] = $this->db->updateObject('#__content', $mentionsLegalesArticle, 'id');
		}
	}

	/**
	 * Export templates flagged as system are the only ones an automation may print with, and the only
	 * ones shared with every user allowed to export. The column lives on #__emundus_filters, which also
	 * holds the search and list filters: it is only meaningful for mode = 'export'.
	 *
	 * Named is_system because SYSTEM is a reserved word in MySQL 8.4.
	 *
	 * Defaults to 0, so every template saved so far stays personal.
	 */
	private function addSystemFlagOnFilters(): void
	{
		$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_filters', 'is_system', 'TINYINT', 1, 0, 0)['status'];
	}

	/**
	 * Visibility of the generated application file in the documents list moved to its own setting,
	 * display_application_form_document, because export_application_pdf only decides whether that PDF
	 * is produced on submission — and an automation can now produce it too.
	 *
	 * Carry the former value over so nothing changes for existing instances. Only fills the setting when
	 * it is absent: this script runs again on later updates and must not undo an administrator's choice.
	 *
	 * @throws \Exception
	 */
	private function inheritApplicationFormDocumentVisibility(): void
	{
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName(['extension_id', 'params']))
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_emundus'));
		$this->db->setQuery($query);
		$component = $this->db->loadObject();

		if (empty($component->extension_id))
		{
			return;
		}

		$params = json_decode($component->params, true);
		if (!is_array($params) || array_key_exists('display_application_form_document', $params))
		{
			return;
		}

		$params['display_application_form_document'] = (string) (int) ($params['export_application_pdf'] ?? 0);

		$component->params = json_encode($params);
		$this->tasks[]     = $this->db->updateObject('#__extensions', $component, ['extension_id']);
	}
}
