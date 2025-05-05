<?php

/**
 * @package    Joomla
 * @subpackage eMundus
 * @link       http://www.emundus.fr
 * @copyright    Copyright (C) 2024 eMundus SAS. All rights reserved.
 * @license    GNU/GPL
 * @author     eMundus SAS - LEGENDRE Jérémy
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\User\User;

jimport('joomla.application.component.view');

/**
 * Classement View
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      1.39.0
 */
class EmundusViewRanking extends JViewLegacy
{
    public User $user;
    private DatabaseDriver $db;
    public int $hierarchy_id = 0;
    public bool $display_filters = false;
    public $params;
    public string $comparison_modal_tabs = '';
    public $comparison_modal_specific_tabs = '';

	public bool $readonly = false;

	public string $title = '';

	public string $introduction = '';

    function __construct($config = array())
    {
		$app = Factory::getApplication();
        $this->user = $app->getIdentity();
        $this->db = Factory::getContainer()->get('DatabaseDriver');
	    if (!class_exists('EmundusModelRanking')) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/ranking.php');
		}
	    $this->model = new EmundusModelRanking();
        $this->hierarchy_id = $this->model->getUserHierarchy($this->user->id);
        $this->hierarchy = $this->model->getHierarchyData($this->hierarchy_id);

        $menu = $app->getMenu();
        $active = $menu->getActive();
        $query = $this->db->getQuery(true);
        $query->select('jm.id')
            ->from($this->db->quoteName('jos_modules', 'jm'))
            ->leftJoin($this->db->quoteName('jos_modules_menu', 'jmm') . ' ON jm.id = jmm.moduleid')
            ->where('jmm.menuid = ' . $active->id)
            ->andWhere('jm.module = ' . $this->db->quote('mod_emundus_filters'))
            ->andWhere('jm.published = 1');

        $this->db->setQuery($query);
        $module_id = $this->db->loadResult();

        if (!empty($module_id)) {
            $this->display_filters = true;
        }

        $this->params = $active->getParams();
		$this->params = json_decode($this->params);

        $comparison_modal_tabs = $this->params->comparison_modal_tabs ?? [];
        $this->comparison_modal_tabs = implode(',', $comparison_modal_tabs);

        $comparison_modal_specific_tabs = $this->params->comparison_modal_specific_tabs ?? [];
        if (!empty($comparison_modal_specific_tabs)) {
            $this->comparison_modal_specific_tabs = [];

            foreach ($comparison_modal_specific_tabs as $value) {
                if (!empty($value->specific_tab_iframe_url)) {
                    $this->comparison_modal_specific_tabs[] = [
                        'label' => Text::_($value->specific_tab_label),
                        'url' => $value->specific_tab_iframe_url
                    ];
                }
            }

            $this->comparison_modal_specific_tabs = json_encode($this->comparison_modal_specific_tabs);
        }

		$this->readonly = $this->params->force_readonly ?? false;
		$this->title = Text::_($this->params->title ?? 'COM_EMUNDUS_CLASSEMENT_TITLE');
		$this->introduction = Text::_($this->params->introduction ?? '');

	    parent::__construct($config);
    }

    function display($tpl = null): void
    {
	    parent::display($tpl);
    }
}
