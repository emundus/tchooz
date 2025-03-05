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
use Joomla\CMS\Log\Log;
use Joomla\CMS\User\UserHelper;

class Release2_2_5Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$query  = $this->db->getQuery(true);
		$result = ['status' => false, 'message' => ''];

		try
		{
			EmundusHelperUpdate::installExtension('plg_extension_emundus', 'emundus', '', 'plugin', 1, 'extension', '{}');

            $column_added = EmundusHelperUpdate::addColumn('jos_emundus_files_request', 'expert_user_id', 'INT', 11);
            if ($column_added['status'])
            {
                EmundusHelperUpdate::displayMessage('La colonne expert_user_id a été ajoutée à la table jos_emundus_files_request.', 'success');
            }
            else
            {
                throw new \Exception('Erreur lors de l\'ajout de la colonne expert_user_id à la table jos_emundus_files_request.');
            }

			EmundusHelperUpdate::enableEmundusPlugins('fabrikcron', 'system');

			$query->clear()
				->select('fe.id,fe.default')
				->from($this->db->quoteName('#__fabrik_elements','fe'))
				->leftJoin($this->db->quoteName('#__fabrik_formgroup','ffg').' ON '.$this->db->quoteName('ffg.group_id').' = '.$this->db->quoteName('fe.group_id'))
				->leftJoin($this->db->quoteName('#__fabrik_forms','ff').' ON '.$this->db->quoteName('ff.id').' = '.$this->db->quoteName('ffg.form_id'))
				->where($this->db->quoteName('ff.label').' LIKE '.$this->db->quote('SETUP_FILL_A_NEW_APPLICATION_FORM'))
				->where($this->db->quoteName('fe.name').' = '.$this->db->quote('campaign_id'))
				->where($this->db->quoteName('fe.plugin').' = '.$this->db->quote('databasejoin'));
			$this->db->setQuery($query);
			$element = $this->db->loadObject();

			if (!empty($element->id)) {
				if(str_contains($element->default, 'getAllowedCampaign'))
				{
					$element->default = '';
					$this->db->updateObject('#__fabrik_elements', $element, 'id');
				}
			}

			$result['status'] = true;
		}
		catch (\Exception $e)
		{
			$result['message'] = $e->getMessage();

			return $result;
		}

		return $result;
	}
}