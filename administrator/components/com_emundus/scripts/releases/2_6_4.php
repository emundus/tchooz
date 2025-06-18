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

class Release2_6_4Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$tags = [
				['tag' => 'LAST_COMMENT', 'request' => '[LAST_COMMENT]', 'description' => 'Dernier commentaire déposé sur ce dossier', 'published' => 1],
				['tag' => 'LAST_COMMENT_DATE', 'request' => '[LAST_COMMENT_DATE]', 'description' => 'Date de dépôt du dernier commentaire', 'published' => 1],
				['tag' => 'LAST_COMMENT_AUTHOR', 'request' => '[LAST_COMMENT_AUTHOR]', 'description' => 'Auteur du commentaire', 'published' => 1],
				['tag' => 'LAST_COMMENT_TARGET', 'request' => '[LAST_COMMENT_TARGET]', 'description' => 'Fil d\'ariane du commentaire', 'published' => 1],
			];

			// add new default tags
			foreach ($tags as $tag) {
				$query->clear()
					->insert($this->db->quoteName('#__emundus_setup_tags'))
					->columns($this->db->quoteName(['tag', 'request', 'description', 'published']))
					->values(
						$this->db->quote($tag['tag']) . ', ' .
						$this->db->quote($tag['request']) . ', ' .
						$this->db->quote($tag['description']) . ', ' .
						$this->db->quote($tag['published'])
					);

				$tasks[] = $this->db->setQuery($query)->execute();
			}

			$tasks[] = EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ACTIONS_DELETE', 'Supprimer');
			$tasks[] = EmundusHelperUpdate::insertTranslationsTag('COM_EMUNDUS_ACTIONS_DELETE', 'Delete', 'override', 0, null, null, 'en-GB');

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}