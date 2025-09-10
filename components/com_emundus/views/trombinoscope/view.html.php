<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 28/03/2017
 * Time: 01:14
 */

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

use \Joomla\CMS\Editor\Editor;
use Joomla\CMS\Factory;

class EmundusViewTrombinoscope extends JViewLegacy
{
	protected $actions;
	
	public $string_fnums = ''; 
	public $trombi_checked = '';
	public $badge_checked = '';
	public $selected_format = '';
	public $default_margin = '';
	public $default_header_height = '';
	public $wysiwyg = '';
	public $wysiwyg_header = '';
	public $wysiwyg_footer = '';
	public $form_elements_id_list = '';
	public $htmlLetters = array();
	public $templ = array();
	
	
	

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	public function display($tpl = null)
	{

		$current_user = JFactory::getUser();
		if (!EmundusHelperAccess::asPartnerAccessLevel($current_user->id)) {
			die(JText::_('COM_EMUNDUS_ACCESS_RESTRICTED_ACCESS'));
		}

		$app   = JFactory::getApplication();
		$fnums = $app->input->getString('fnums', null);

		$m_trombi = new EmundusModelTrombinoscope();

		$htmlLetters = $m_trombi->selectHTMLLetters();
		$templ       = [];

		foreach ($htmlLetters as $letter) {
			$templ[$letter['attachment_id']] = $letter;
		}

		$fnums_json_decode = $m_trombi->fnums_json_decode($fnums);

		$programme = $m_trombi->getProgByFnum($fnums_json_decode[0]['fnum']);
		$m_trombi->set_template($programme['code'], 'trombi');
		$m_trombi->set_template($programme['code'], 'badge');

		$form_elements_id_list = 'index.php?option=com_emundus&view=export_select_columns&format=raw&code=' . $programme['code'] . '&layout=programme&rowid=' . $programme['id'];

		// SET EDITOR PARAMS
		$params = array('mode' => 'simple');
		$editor = new Editor();


		// COM_EMUNDUS_DISPLAY THE EDITOR (name, html, width, height, columns, rows, bottom buttons, id, asset, author, params)
		$wysiwyg        = $editor->display('trombi_tmpl', $templ[$htmlLetters[0]['attachment_id']]['body'], '100%', '250', '20', '20', true, 'trombi_tmpl', null, null, $params);
		$wysiwyg_header = $editor->display('trombi_head', $templ[$htmlLetters[0]['attachment_id']]['header'], '100%', '250', '20', '20', true, 'trombi_head', null, null, $params);
		$wysiwyg_footer = $editor->display('trombi_foot', $templ[$htmlLetters[0]['attachment_id']]['footer'], '100%', '250', '20', '20', true, 'trombi_foot', null, null, $params);

		$this->string_fnums = $fnums;

		// Option trombinoscope cochée par défaut
		$this->trombi_checked = 'checked';
		$this->badge_checked = '';
		$this->selected_format = $templ[$htmlLetters[0]['attachment_id']]['attachment_id'];

		// Autres options
		$this->default_margin = $m_trombi->default_margin;
		$this->default_header_height = $m_trombi->default_header_height;
		$this->wysiwyg = $wysiwyg;
		$this->wysiwyg_header = $wysiwyg_header;
		$this->wysiwyg_footer = $wysiwyg_footer;
		$this->form_elements_id_list = $form_elements_id_list;
		$this->htmlLetters = $htmlLetters;
		$this->templ = $templ;

		parent::display($tpl);
	}
}
