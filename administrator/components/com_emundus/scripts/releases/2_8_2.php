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
use Joomla\CMS\Component\ComponentHelper;

class Release2_8_2Installer extends ReleaseInstaller
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
			$query->clear()
				->select('ff.id, ff.params')
				->from($this->db->quoteName('#__fabrik_lists','fl'))
				->leftJoin($this->db->quoteName('#__fabrik_forms','ff').' ON '.$this->db->quoteName('fl.form_id').' = '.$this->db->quoteName('ff.id'))
				->where($this->db->quoteName('fl.db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_letters'));
			$this->db->setQuery($query);
			$setup_letters_form = $this->db->loadObject();

			if(!empty($setup_letters_form) && !empty($setup_letters_form->id))
			{
				$params = json_decode($setup_letters_form->params, true);
				$params['plugin_state'] = [1];
				$params['only_process_curl'] = ['onBeforeStore'];
				$params['form_php_file'] = ['emundus-setup-letters-check-file.php'];
				$params['form_php_require_once'] = [0];
				$params['curl_code'] = [''];
				$params['plugins'] = ['php'];
				$params['plugin_locations'] = ['both'];
				$params['plugin_events'] = ['both'];
				$params['plugin_description'] = ['Check letters compliance'];

				$setup_letters_form->params = json_encode($params);
				$tasks[] = $this->db->updateObject('#__fabrik_forms', $setup_letters_form, 'id');
			}

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