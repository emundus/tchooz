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
use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';

class SharingFilesInstall
{
	private $db;

	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request','ccid','int',11);
		if(!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne ccid à la table jos_emundus_files_request<br>';
			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request','user_id','int',11);
		if(!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne user_id à la table jos_emundus_files_request<br>';
			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request','r','tinyint',3,0,0);
		if(!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne r à la table jos_emundus_files_request<br>';
			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request','u','tinyint',3,0,0);
		if(!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne u à la table jos_emundus_files_request<br>';
			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request','show_history','tinyint',3,0,0);
		if(!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne show_history à la table jos_emundus_files_request<br>';
			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request','show_shared_users','tinyint',3,0,0);
		if(!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne show_shared_users à la table jos_emundus_files_request<br>';
			return $result;
		}

		$this->db->setQuery('ALTER TABLE `jos_emundus_files_request` MODIFY `attachment_id` int(11) NULL;');
		$attachment_modified = $this->db->execute();
		if(!$attachment_modified) {
			$result['message'] .= 'Erreur lors de la modification de la colonne attachment_id de la table jos_emundus_files_request<br>';
			return $result;
		}

		$column_added = EmundusHelperUpdate::addColumn('jos_emundus_campaign_candidature','locked_elements','text');
		if(!$column_added['status']) {
			$result['message'] .= 'Erreur lors de l\'ajout de la colonne locked_elements à la table jos_emundus_files_request<br>';
			return $result;
		}

		$result['status'] = true;

		return $result;
	}
}