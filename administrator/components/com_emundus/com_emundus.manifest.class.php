<?php
/**
 * eMundus: Installer Manifest Class
 *
 * @package     Joomla
 * @subpackage  eMundus
 * @author      eMundus
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

class Com_EmundusInstallerScript
{
	private $db;

	protected $manifest_cache;
	protected $schema_version;
	protected EmundusHelperUpdate $h_update;

	public function __construct()
	{
		// Get component manifest cache
		$this->db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $this->db->getQuery(true);

		$query->select('extension_id, manifest_cache')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('com_emundus'));
		$this->db->setQuery($query);
		$extension = $this->db->loadObject();
		$this->manifest_cache = json_decode($extension->manifest_cache);

		$query->clear()
			->select('version_id')
			->from($this->db->quoteName('#__schemas'))
			->where($this->db->quoteName('extension_id') . ' = ' . $this->db->quote($extension->extension_id));
		$this->db->setQuery($query);
		$this->schema_version = $this->db->loadResult();

		require_once(JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');
		$this->h_update = new EmundusHelperUpdate();
	}

    /**
     * Run before installation or upgrade run
     *
     * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
     *                         or install (standard install)
     *                         or update (update)
     * @param   object $parent installer object
     *
     * @return  void
     */
    public function preflight($type, $parent)
    {
	    if (version_compare(PHP_VERSION, '7.4.0', '<'))
	    {
		    echo "\033[31mThis extension works with PHP 7.4.0 or newer. Please contact your web hosting provider to update your PHP version. \033[0m\n";
		    exit;
	    }
    }

    /**
     * Run when the component is installed
     *
     * @param   object $parent installer object
     *
     * @return bool
     */
    public function install($parent)
    {
        $parent->getParent()->setRedirectURL('index.php?option=com_emundus');

        return true;
    }

    /**
     * Run when the component is updated
     *
     * @param   object $parent installer object
     *
     * @return  bool
     */
    public function update($parent)
    {
        $succeed = true;

        $cache_version = $this->manifest_cache->version;

        $firstrun = false;
        $regex    = '/^6\.[0-9]*/m';
        preg_match_all($regex, $cache_version, $matches, PREG_SET_ORDER, 0);
        if (!empty($matches)) {
            $cache_version = (string) $parent->manifest->version;
            $firstrun      = true;
        }

		$query = $this->db->getQuery(true);

        if ($this->manifest_cache) {
            if (version_compare($cache_version, '2.0.0', '<=') || $firstrun) {
	            EmundusHelperUpdate::displayMessage('Installation de la version 2.0.0...');

				$disabled = EmundusHelperUpdate::disableEmundusPlugins('webauthn');
				if($disabled) {
					EmundusHelperUpdate::displayMessage('Le plugin WebAuthn a été désactivé.', 'success');
				}

				$query->update($this->db->quoteName('#__fabrik_elements'))
					->set($this->db->quoteName('eval') . ' = 0')
					->set($this->db->quoteName('default') . ' = ' . $this->db->quote(''))
					->where($this->db->quoteName('name') . ' LIKE ' . $this->db->quote('fnum'))
					->where($this->db->quoteName('eval') . ' = 1');
				$this->db->setQuery($query);
				if($this->db->execute()) {
					EmundusHelperUpdate::displayMessage('Les valeurs par défaut des champs fnums ont été retirées, ces valeurs sont désormais pré-remplis via le plugin emundus_events', 'success');
				}
				else {
					EmundusHelperUpdate::displayMessage('Erreur lors de la modification des champs fnums', 'error');
					$succeed = false;
				}

	            $column_added = EmundusHelperUpdate::addColumn('jos_emundus_setup_attachments', 'max_filesize', 'DOUBLE(6,2)');
				if($column_added['status']) {
					EmundusHelperUpdate::displayMessage('La colonne max_filesize a été ajoutée à la table jos_emundus_setup_attachments', 'success');
				}
				else {
					EmundusHelperUpdate::displayMessage('Erreur lors de l\'ajout de la colonne max_filesize à la table jos_emundus_setup_attachments', 'error');
					$succeed = false;
				}

				// Install colorpicker
	            EmundusHelperUpdate::installExtension('plg_fabrik_element_emundus_colorpicker','emundus_colorpicker','{"name":"plg_fabrik_element_emundus_colorpicker","type":"plugin","creationDate":"November 2023","author":"Media A-Team, Inc.","copyright":"Copyright (C) 2005-2023 Media A-Team, Inc. - All rights reserved.","authorEmail":"brice.hubinet@emundus.fr","authorUrl":"www.emundus.fr","version":"4.0Zeta","description":"PLG_ELEMENT_COLOURPICKER_DESCRIPTION","group":"","filename":"emundus_colorpicker"}','plugin',1,'fabrik_element');

	            $query->clear()
		            ->update($this->db->quoteName('#__fabrik_elements','fe'))
		            ->leftJoin($this->db->quoteName('#__fabrik_formgroup','ffg').' ON '.$this->db->quoteName('fe.group_id').' = '.$this->db->quoteName('ffg.group_id'))
		            ->leftJoin($this->db->quoteName('#__fabrik_lists','fl').' ON '.$this->db->quoteName('ffg.form_id').' = '.$this->db->quoteName('fl.form_id'))
		            ->set($this->db->quoteName('fe.plugin') . ' = ' . $this->db->quote('emundus_colorpicker'))
		            ->set($this->db->quoteName('fe.params') . ' = ' . $this->db->quote('{"rgaa":"1","save_label":"1","show_in_rss_feed":"0","show_label_in_rss_feed":"0","use_as_rss_enclosure":"0","rollover":"","tipseval":"0","tiplocation":"top-left","labelindetails":"0","labelinlist":"0","comment":"","edit_access":"1","edit_access_user":"","view_access":"1","view_access_user":"","list_view_access":"1","encrypt":"0","store_in_db":"1","default_on_copy":"0","can_order":"0","alt_list_heading":"","custom_link":"","custom_link_target":"","custom_link_indetails":"1","use_as_row_class":"0","include_in_list_query":"1","always_render":"0","icon_folder":"0","icon_hovertext":"1","icon_file":"","icon_subdir":"","filter_length":"20","filter_access":"1","full_words_only":"0","filter_required":"0","filter_build_method":"0","filter_groupby":"text","inc_in_adv_search":"1","filter_class":"input-medium","filter_responsive_class":"","tablecss_header_class":"","tablecss_header":"","tablecss_cell_class":"","tablecss_cell":"","sum_on":"0","sum_label":"Sum","sum_access":"8","sum_split":"","avg_on":"0","avg_label":"Average","avg_access":"8","avg_round":"0","avg_split":"","median_on":"0","median_label":"Median","median_access":"8","median_split":"","count_on":"0","count_label":"Count","count_condition":"","count_access":"8","count_split":"","custom_calc_on":"0","custom_calc_label":"Custom","custom_calc_query":"","custom_calc_access":"1","custom_calc_split":"","custom_calc_php":"","validations":[]}'))
		            ->where($this->db->quoteName('fe.plugin') . ' LIKE ' . $this->db->quote('dropdown'))
		            ->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('class'))
		            ->where($this->db->quoteName('fl.db_table_name') . ' != ' . $this->db->quote('jos_emundus_setup_status'));
	            $this->db->setQuery($query);
	            $this->db->execute();

	            $query->clear()
		            ->update($this->db->quoteName('#__fabrik_elements','fe'))
		            ->leftJoin($this->db->quoteName('#__fabrik_formgroup','ffg').' ON '.$this->db->quoteName('fe.group_id').' = '.$this->db->quoteName('ffg.group_id'))
		            ->leftJoin($this->db->quoteName('#__fabrik_lists','fl').' ON '.$this->db->quoteName('ffg.form_id').' = '.$this->db->quoteName('fl.form_id'))
		            ->set($this->db->quoteName('fe.plugin') . ' = ' . $this->db->quote('emundus_colorpicker'))
		            ->set($this->db->quoteName('fe.params') . ' = ' . $this->db->quote('{"rgaa":"1","save_label":"0","show_in_rss_feed":"0","show_label_in_rss_feed":"0","use_as_rss_enclosure":"0","rollover":"","tipseval":"0","tiplocation":"top-left","labelindetails":"0","labelinlist":"0","comment":"","edit_access":"1","edit_access_user":"","view_access":"1","view_access_user":"","list_view_access":"1","encrypt":"0","store_in_db":"1","default_on_copy":"0","can_order":"0","alt_list_heading":"","custom_link":"","custom_link_target":"","custom_link_indetails":"1","use_as_row_class":"0","include_in_list_query":"1","always_render":"0","icon_folder":"0","icon_hovertext":"1","icon_file":"","icon_subdir":"","filter_length":"20","filter_access":"1","full_words_only":"0","filter_required":"0","filter_build_method":"0","filter_groupby":"text","inc_in_adv_search":"1","filter_class":"input-medium","filter_responsive_class":"","tablecss_header_class":"","tablecss_header":"","tablecss_cell_class":"","tablecss_cell":"","sum_on":"0","sum_label":"Sum","sum_access":"8","sum_split":"","avg_on":"0","avg_label":"Average","avg_access":"8","avg_round":"0","avg_split":"","median_on":"0","median_label":"Median","median_access":"8","median_split":"","count_on":"0","count_label":"Count","count_condition":"","count_access":"8","count_split":"","custom_calc_on":"0","custom_calc_label":"Custom","custom_calc_query":"","custom_calc_access":"1","custom_calc_split":"","custom_calc_php":"","validations":[]}'))
		            ->where($this->db->quoteName('fe.plugin') . ' LIKE ' . $this->db->quote('dropdown'))
		            ->where($this->db->quoteName('fe.name') . ' LIKE ' . $this->db->quote('class'))
		            ->where($this->db->quoteName('fl.db_table_name') . ' = ' . $this->db->quote('jos_emundus_setup_status'));
	            $this->db->setQuery($query);
	            $this->db->execute();
            }
        }

        return $succeed;
    }

    /**
     * Run when the component is uninstalled.
     *
     * @param   object $parent installer object
     *
     * @return  void
     */
    public function uninstall($parent)
    {
    }

    /**
     * Run after installation or upgrade run
     *
     * @param   string $type   discover_install (Install unregistered extensions that have been discovered.)
     *                         or install (standard install)
     *                         or update (update)
     * @param   object $parent installer object
     *
     * @return  bool
     */
    public function postflight($type, $parent)
    {

	    $db    = Factory::getContainer()->get('DatabaseDriver');
	    $query = $db->getQuery(true);

	    $query->select('custom_data')
		    ->from($db->quoteName('#__extensions'))
		    ->where($db->quoteName('element') . ' LIKE ' . $db->quote('com_emundus'));
	    $db->setQuery($query);
	    $custom_data = $db->loadResult();

	    if (!empty($custom_data))
	    {
		    $custom_data = json_decode($custom_data, true);

		    $custom_data['sitename'] = Factory::getApplication()->get('sitename');
	    }
	    else
	    {
		    $custom_data = [
			    'sitename' => Factory::getApplication()->get('sitename'),
		    ];
	    }

	    $query->clear()
		    ->update($db->quoteName('#__extensions'))
		    ->set($db->quoteName('custom_data') . ' = ' . $db->quote(json_encode($custom_data)))
		    ->where($db->quoteName('element') . ' LIKE ' . $db->quote('com_emundus'));
	    $db->setQuery($query);

		if(!$db->execute()) {
			return false;
		}
		
		// Sync gantry5 logo
	    if(file_exists(JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml')) {
		    $logo = JPATH_SITE . '/images/logo_custom.png';
		    $query->clear()
			    ->select('id,content')
			    ->from($db->quoteName('#__modules'))
			    ->where($db->quoteName('module') . ' = ' . $db->quote('mod_custom'))
			    ->where($db->quoteName('title') . ' LIKE ' . $db->quote('Logo'));
		    $db->setQuery($query);
		    $logo_module = $db->loadObject();

		    preg_match('#src="(.*?)"#i', $logo_module->content, $tab);
		    $pattern = "/^(?:ftp|https?|feed)?:?\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
        (?:[\w#!:\.\?\+\|=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";

		    if (preg_match($pattern, $tab[1])) {
			    $tab[1] = parse_url($tab[1], PHP_URL_PATH);
		    }

		    if (!empty($tab[1])) {
			    $logo = str_replace('images/', 'gantry-media://', $tab[1]);

			    EmundusHelperUpdate::updateYamlVariable('image', $logo, JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml');
		    } elseif(file_exists($logo)) {
			    $logo = str_replace('images/', 'gantry-media://', $tab[1]);

			    EmundusHelperUpdate::updateYamlVariable('image', $logo, JPATH_ROOT . '/templates/g5_helium/custom/config/default/particles/logo.yaml');
		    }
	    }

	    // Insert new translations in overrides files
	    EmundusHelperUpdate::languageBaseToFile();

	    // Recompile Gantry5 css at each update
	    EmundusHelperUpdate::recompileGantry5();

	    // Clear Joomla Cache
	    EmundusHelperUpdate::clearJoomlaCache();

		return true;
    }
}
