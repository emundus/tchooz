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

class Release2_0_2Installer extends ReleaseInstaller
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
			// Install Dropfiles
			if(!EmundusHelperUpdate::installExtension('Dropfiles themes - preview', 'preview', null, 'plugin', 1, 'dropfilesthemes')) {
				EmundusHelperUpdate::displayMessage('Error installing Dropfiles themes - preview');
			}

			$manifest_path = JPATH_ADMINISTRATOR . '/components/com_dropfiles/com_dropfiles.xml';
			$xml_string = file_get_contents($manifest_path);
			$xml        = simplexml_load_string($xml_string);
			$json       = json_encode($xml);
			if (!empty($json))
			{
				$array          = json_decode($json, true);
				$manifest_cache = [
					'name'         => $array['name'],
					'type'         => $array['@attributes']['type'],
					'creationDate' => $array['creationDate'],
					'author'       => $array['author'],
					'copyright'    => $array['copyright'],
					'authorEmail'  => $array['authorEmail'],
					'authorUrl'    => $array['authorUrl'],
					'version'      => $array['version'],
					'description'  => $array['description'],
					'group'        => !empty($array['@attributes']['group']) ? $array['@attributes']['group'] : '',
					'namespace'    => $array['namespace'],
					'filename'     => 'com_dropfiles',
				];
				$manifest_cache = json_encode($manifest_cache);

				$query = $this->db->getQuery(true);
				$query->update($this->db->quoteName('#__extensions'))
					->set($this->db->quoteName('manifest_cache') . ' = ' . $this->db->quote($manifest_cache))
					->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_dropfiles'))
					->where($this->db->quoteName('type') . ' = ' . $this->db->quote('component'));
				$this->db->setQuery($query);

				if(!$this->db->execute()) {
					EmundusHelperUpdate::displayMessage('Error updating Dropfiles manifest_cache');
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