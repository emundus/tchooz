<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2015 eMundus. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses. See COPYRIGHT.php for copyright notices and
 * details.
 */

namespace Unit\Helper;

// no direct access
defined('_JEXEC') or die('Restricted access');

include_once(JPATH_SITE . '/components/com_emundus/models/users.php');
include_once(JPATH_SITE . '/components/com_emundus/models/formbuilder.php');
include_once(JPATH_SITE . '/components/com_emundus/models/settings.php');
include_once(JPATH_SITE . '/components/com_emundus/classes/api/FileSynchronizer.php');
include_once(JPATH_SITE . '/components/com_emundus/models/campaign.php');
include_once(JPATH_SITE . '/components/com_emundus/models/programme.php');
include_once(JPATH_SITE . '/components/com_emundus/models/events.php');

use DateTime;
use EmundusModelCampaign;
use EmundusModelFormbuilder;
use EmundusModelProgramme;
use EmundusModelsettings;
use EmundusModelUsers;
use Exception;
use JFactory;
use JLog;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;

class Dataset
{
	private $db;
	
	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		
		if (!defined('EVALUATOR_RIGHTS')) {
			define('EVALUATOR_RIGHTS',
				[
					['id' => '1', 'c' => 0, 'r' => 1, 'u' => 0, 'd' => 0],
					['id' => '4', 'c' => 1, 'r' => 1, 'u' => 0, 'd' => 0],
					['id' => '5', 'c' => 1, 'r' => 1, 'u' => 1, 'd' => 0]
				]
			);
		}
	}

	public function createSampleUser($profile = 9, $username = 'user.test@emundus.fr', $password = 'test1234', $j_groups = [2], $firstname = 'Test', $lastname = 'USER')
	{
		$user_id = 0;
		$m_users = new EmundusModelUsers;
		$query = $this->db->getQuery(true);

		$query->insert('#__users')
			->columns('name, username, email, password, registerDate, lastvisitDate, params')
			->values($this->db->quote($firstname . ' ' . $lastname) . ', ' . $this->db->quote($username) .  ', ' . $this->db->quote($username) . ',' .  $this->db->quote(UserHelper::hashPassword($password)) . ',' . $this->db->quote(date('Y-m-d H:i:s')) . ',' . $this->db->quote(date('Y-m-d H:i:s')) . ',' . $this->db->quote('{}'));

		try {
			$this->db->setQuery($query);
			$this->db->execute();
			$user_id = $this->db->insertid();
		} catch (Exception $e) {
			error_log("Failed to insert jos_users" . $e->getMessage());
			JLog::add("Failed to insert jos_users" . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
		}

		if (!empty($user_id)) {
			if(!empty($j_groups)) {
				foreach ($j_groups as $j_group) {
					$query->clear()
						->insert($this->db->quoteName('#__user_usergroup_map'))
						->columns('user_id, group_id')
						->values($user_id . ',' . $j_group);
					try {
						$this->db->setQuery($query);
						$this->db->execute();
					} catch (Exception $e) {
						JLog::add("Failed to insert jos_user_usergroup_map" . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
					}
				}
			}

			$other_param['firstname'] 		= $firstname;
			$other_param['lastname'] 		= $lastname;
			$other_param['profile'] 		= $profile;
			$other_param['em_oprofiles'] 	= '';
			$other_param['univ_id'] 		= 0;
			$other_param['em_groups'] 		= '';
			$other_param['em_campaigns'] 	= [];
			$other_param['news'] 			= '';
			$m_users->addEmundusUser($user_id, $other_param);
		} else {
			error_log('Failed to create sample user');
		}

		return $user_id;
	}

	public function deleteSampleUser($uid)
	{
		$deleted = false;
		if (!empty($uid)) {
			
			$query = $this->db->getQuery(true);

			$query->delete('#__users')
				->where('id = ' . $uid);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleFile($cid, $uid, $return_ccid = false){
		$m_formbuilder = new EmundusModelFormbuilder;

		$fnum = $m_formbuilder->createTestingFile($cid, $uid, $return_ccid);
		if (empty($fnum)) {
			// wait for 1 second to avoid duplicate file name
			sleep(1);
			$fnum = $m_formbuilder->createTestingFile($cid,$uid, $return_ccid);

			if (empty($fnum)) {
				error_log('Failed to create sample file');
			}
		}

		return $fnum;
	}

	public function deleteSampleFile($fnum)
	{
		$deleted = false;
		if (!empty($fnum)) {
			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_campaign_candidature')
				->where('fnum = ' . $this->db->quote($fnum));

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleTag()
	{
		$m_settings = new EmundusModelSettings;

		return $m_settings->createTag()->id;
	}

	public function deleteSampleTag($id)
	{
		$deleted = false;
		if (!empty($id)) {
			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_setup_action_tag')
				->where('id = ' . $id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleComment($fnum, $aid, $uid, $reason = 'Test unitaire', $comment_body = 'Commentaire pour un test unitaire')
	{
		if(!class_exists('EmundusModelApplication')){
			include_once(JPATH_SITE . '/components/com_emundus/models/application.php');
		}
		$m_application = new \EmundusModelApplication;

		$row['fnum'] = $fnum;
		$row['applicant_id'] = $aid;
		$row['user_id'] = $uid;
		$row['reason'] = $reason;
		$row['comment_body'] = $comment_body;

		return $m_application->addComment($row);
	}

	public function deleteSampleComment($id)
	{
		$deleted = false;
		if (!empty($id)) {
			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_comments')
				->where('id = ' . $id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleStatus(){
		$m_settings = new EmundusModelSettings;

		return $m_settings->createStatus()->step;
	}

	public function createSampleForm($prid = 9, $label = ['fr' => 'Formulaire Tests unitaires', 'en' => 'form for unit tests'], $intro = ['fr' => 'Ce formulaire est un formulaire de test eMundus, utilisé uniquement pour tester le bon fonctionnement de la plateforme.', 'en' => ''], $user = 1) {
		$m_formbuilder = new EmundusModelFormbuilder;
		return $m_formbuilder->createFabrikForm($prid, $label, $intro, '', $user);
	}

	public function createSampleGroup() {
		$data = [];
		$m_formbuilder = new EmundusModelFormbuilder;

		$form_id = $this->createSampleForm();

		if (!empty($form_id)) {
			$group = $m_formbuilder->createGroup(['fr' => 'Groupe Tests unitaires', 'en' => 'Group Unit tests'] , $form_id);

			if (!empty($group['group_id'])) {
				$group_id = $group['group_id'];

				
				$query = $this->db->getQuery(true);

				$query->select('params')
					->from('#__fabrik_groups')
					->where('id = ' . $group_id);

				$this->db->setQuery($query);

				$params = $this->db->loadResult();
				$params = json_decode($params, true);

				$params['is_sample'] = true;

				$query->clear()
					->update('#__fabrik_groups')
					->set('params = ' . $this->db->quote(json_encode($params)))
					->where('id = ' . $group_id);

				$this->db->setQuery($query);
				$this->db->execute();

				$data = array(
					'form_id' => $form_id,
					'group_id' => $group_id
				);
			}
		}

		return $data;
	}

	public function deleteSampleGroup($group_id) {
		$deleted = false;
		if (!empty($group_id)) {
			
			$query = $this->db->getQuery(true);

			$query->select('params')
				->from('#__fabrik_groups')
				->where('id = ' . $group_id);

			$this->db->setQuery($query);

			$params = $this->db->loadResult();
			$params = json_decode($params, true);

			if ($params['is_sample']) {
				$query->clear()
					->delete('#__fabrik_groups')
					->where('id = ' . $group_id);

				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			}
		}

		return $deleted;
	}

	public function deleteSampleForm($form_id) {
		$deleted = false;
		if (!empty($form_id)) {
			
			$query = $this->db->getQuery(true);

			$query->delete('#__fabrik_groups')
				->where('id = ' . $form_id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleProgram($label = 'Programme Test Unitaire',$user_id = 1)
	{
		$m_programme = new EmundusModelProgramme;
		return $m_programme->addProgram(['label' => $label, 'published' => 1],Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id));
	}

	public function deleteSampleProgram($program_id) {
		$deleted = false;
		if (!empty($program_id)) {
			
			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_setup_programmes')
				->where('id = ' . $program_id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleCampaign($program, $user_id = 1)
	{
		$campaign_id = 0;

		if (!empty($program)) {
			$m_campaign = new EmundusModelCampaign;

			$start_date = new DateTime();
			$start_date->modify('-1 day');
			$end_date = new DateTime();
			$end_date->modify('+1 year');
			$campaign_id = $m_campaign->createCampaign([
				'label' =>  json_encode(['fr' => 'Campagne test unitaire '.rand(1,9999), 'en' => 'Campagne test unitaire '.rand(1,9999)]),
				'description' => 'Lorem ipsum',
				'short_description' => 'Lorem ipsum',
				'start_date' => $start_date->format('Y-m-d H:i:s'),
				'end_date' => $end_date->format('Y-m-d H:i:s'),
				'profile_id' => 1000,
				'training' => $program['programme_code'],
				'year' => '2022-2023',
				'published' => 1,
				'is_limited' => 0,
				'user' => $user_id
			], $user_id);
		}

		return $campaign_id;
	}

	public function deleteSampleCampaign($campaign_id) {
		$deleted = false;
		if (!empty($campaign_id)) {
			
			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_setup_campaigns')
				->where('id = ' . $campaign_id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleAttachment() {
		$sample_id = 0;

		
		$query = $this->db->getQuery(true);

		$rand_id = rand();

		$query->insert('#__emundus_setup_attachments')
			->columns(['lbl', 'value', 'description', 'allowed_types', 'nbmax'])
			->values($this->db->quote('_test_unitaire_' . $rand_id) . ',' . $this->db->quote('Test unitaire ' . $rand_id) . ',' . $this->db->quote('Document pour les tests unitaire') . ',' .$this->db->quote('pdf'). ',' . $this->db->quote(1));

		$this->db->setQuery($query);
		$inserted = $this->db->execute();
		if ($inserted) {
			$sample_id = $this->db->insertid();
		}

		return $sample_id;
	}

	public function deleteSampleAttachment($aid) {
		$deleted = false;
		if (!empty($aid)) {

			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_setup_attachments')
				->where('id = ' . $aid);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function createSampleLetter($attachment_id, $template_type = 2, $programs = [], $status = [], $campaigns = []) {
		$letter_id = 0;

		if (!empty($attachment_id)) {
			
			$query = $this->db->getQuery(true);

			$query->insert('#__emundus_setup_letters')
				->columns(['attachment_id', 'template_type', 'header', 'body', 'footer', 'title'])
				->values($attachment_id . ',' . $template_type . ',' . $this->db->quote('<p>letter_header</p>') . ',' . $this->db->quote('<p>letter_body</p>') . ',' . $this->db->quote('<p>letter_footer</p>') . ',' . $this->db->quote('Lettre Test unitaire'));
			$this->db->setQuery($query);

			$inserted = $this->db->execute();

			if ($inserted) {
				$letter_id = $this->db->insertid();

				if (!empty($programs)) {
					$values = [];
					foreach ($programs as $program) {
						$values[] = $letter_id . ',' . $this->db->quote($program);
					}

					$query->clear()
						->insert('#__emundus_setup_letters_repeat_training')
						->columns(['parent_id', 'training'])
						->values($values);

					$this->db->setQuery($query);
					$this->db->execute();
				}

				if (!empty($status)) {
					$values = [];
					foreach ($status as $statu) {
						$values[] = $letter_id . ',' . $this->db->quote($statu);
					}

					$query->clear()
						->insert('#__emundus_setup_letters_repeat_status')
						->columns(['parent_id', 'status'])
						->values($values);

					$this->db->setQuery($query);
					$this->db->execute();
				}

				if (!empty($campaigns)) {
					$values = [];
					foreach ($campaigns as $campaign) {
						$values[] = $letter_id . ',' . $this->db->quote($campaign);
					}

					$query->clear()
						->insert('#__emundus_setup_letters_repeat_campaign')
						->columns(['parent_id', 'campaign'])
						->values($values);

					$this->db->setQuery($query);
					$this->db->execute();
				}
			}
		}

		return $letter_id;
	}

	public function deleteSampleLetter($letter_id)
	{
		$deleted = false;
		if (!empty($letter_id)) {

			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_setup_letters')
				->where('id = ' . $letter_id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;

	}

	public function createSampleUpload($fnum, $campaign_id, $user_id = 95, $attachment_id = 1) {
		$upload_id = 0;

		if (!empty($fnum)) {
			$filename = $user_id . '-' . $campaign_id . '-unittest' . rand(0, 100) . '.pdf';
			$localFilename = 'Unit Test file.pdf';

			
			$query = $this->db->getQuery(true);

			$query->insert('#__emundus_uploads')
				->columns(['fnum', 'user_id', 'campaign_id', 'attachment_id', 'filename', 'local_filename', 'timedate', 'can_be_deleted', 'can_be_viewed'])
				->values($fnum . ',' . $user_id . ',' . $campaign_id . ',' . $attachment_id . ',' . $this->db->quote($filename) . ',' . $this->db->quote($localFilename) . ',' . $this->db->quote(date('Y-m-d H:i:s')) . ',1,1');

			try {
				$this->db->setQuery($query);
				if($this->db->execute()) {
					$upload_id = $this->db->insertid();
				}
			} catch (Exception $e) {
				error_log('attachment insertion failed');
			}
		}

		return $upload_id;
	}

	public function deleteSampleUpload($upload_id) {
		$deleted = false;
		if (!empty($upload_id)) {

			$query = $this->db->getQuery(true);

			$query->delete('#__emundus_uploads')
				->where('id = ' . $upload_id);

			$this->db->setQuery($query);
			$deleted = $this->db->execute();
		}

		return $deleted;
	}

	public function  duplicateSampleProfile($profile_id)
	{
		$query = $this->db->getQuery(true);

		// Get profile
		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_setup_profiles'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($profile_id));
		$this->db->setQuery($query);
		$oldprofile = $this->db->loadObject();

		$newprofile = 0;
		if (!empty($oldprofile)) {
			// Create a new profile
			$query->clear()
				->insert('#__emundus_setup_profiles')
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($oldprofile->label . ' - Copy'))
				->set($this->db->quoteName('published') . ' = 1')
				->set($this->db->quoteName('menutype') . ' = ' . $this->db->quote($oldprofile->menutype))
				->set($this->db->quoteName('acl_aro_groups') . ' = ' . $this->db->quote($oldprofile->acl_aro_groups))
				->set($this->db->quoteName('status') . ' = ' . $this->db->quote($oldprofile->status));
			$this->db->setQuery($query);
			$this->db->execute();
			$newprofile = $this->db->insertid();
		}

		return $newprofile;
	}

	public function addJGroup($j_group, $user_id) {
		
		$query = $this->db->getQuery(true);

		try
		{
			$query->insert($this->db->quoteName('#__user_usergroup_map'))
				->columns('user_id, group_id')
				->values($user_id . ',' . $j_group);
			$this->db->setQuery($query);
			$this->db->execute();
		}
		catch (Exception $e)
		{
			JLog::add("Failed to insert jos_user_usergroup_map" . $e->getMessage(), JLog::ERROR, 'com_emundus.error');
		}
	}

	public function getUnitTestFabrikForm()
	{
		$form_id = 0;

		
		$query = "show create table jos_emundus_unit_test_form";

		try {
			$this->db->setQuery($query);
			$create_table = $this->db->execute();
		} catch (Exception $e) {
			$form_id = $this->createUnitTestFabrikForm();
		}

		if (!empty($create_table)) {
			$query = $this->db->getQuery(true);
			$query->select('form_id')
				->from('#__fabrik_lists')
				->where('db_table_name = ' . $this->db->quote('jos_emundus_unit_test_form'));

			$this->db->setQuery($query);
			$form_id = $this->db->loadResult();
		}

		return $form_id;
	}

	private function createUnitTestFabrikForm()
	{
		$form_id = 0;
		$create_form = "INSERT INTO jos_fabrik_forms (label, record_in_database, error, intro, created, created_by, created_by_alias, modified, modified_by, checked_out, checked_out_time, publish_up, publish_down, reset_button_label, submit_button_label, form_template, view_only_template, published, private, params) VALUES ('FORM_UNIT_TEST', 1, 'FORM_ERROR', '<p>FORM_UNIT_TEST_INTRO</p>', '2023-07-04 11:33:48', 95, 'coordinator', '2023-07-04 11:33:48', 95, 95, '2023-07-04 11:33:48', '2023-07-04 11:33:48', null, 'RESET', 'SAVE_CONTINUE', 'emundus', 'bootstrap', 1, 0, '{\"outro\":\"\",\"copy_button\":\"0\",\"copy_button_label\":\"Save as copy\",\"copy_button_class\":\"\",\"copy_icon\":\"\",\"copy_icon_location\":\"before\",\"reset_button\":\"0\",\"reset_button_label\":\"Remise \\\\u00e0 z\\\\u00e9ro\",\"reset_button_class\":\"btn-warning\",\"reset_icon\":\"\",\"reset_icon_location\":\"before\",\"apply_button\":\"0\",\"apply_button_label\":\"Appliquer\",\"apply_button_class\":\"\",\"apply_icon\":\"\",\"apply_icon_location\":\"before\",\"goback_button\":\"1\",\"goback_button_label\":\"GO_BACK\",\"goback_button_class\":\"goback-btn\",\"goback_icon\":\"\",\"goback_icon_location\":\"before\",\"submit_button\":\"1\",\"submit_button_label\":\"SAVE_CONTINUE\",\"save_button_class\":\"btn-primary save-btn sauvegarder\",\"save_icon\":\"\",\"save_icon_location\":\"after\",\"submit_on_enter\":\"0\",\"delete_button\":\"0\",\"delete_button_label\":\"GO_BACK\",\"delete_button_class\":\"btn-danger\",\"delete_icon\":\"\",\"delete_icon_location\":\"before\",\"ajax_validations\":\"0\",\"ajax_validations_toggle_submit\":\"0\",\"submit-success-msg\":\"\",\"suppress_msgs\":\"0\",\"show_loader_on_submit\":\"0\",\"spoof_check\":\"1\",\"multipage_save\":\"0\",\"note\":\"\",\"labels_above\":\"1\",\"labels_above_details\":\"1\",\"pdf_template\":\"\",\"pdf_orientation\":\"portrait\",\"pdf_size\":\"letter\",\"pdf_include_bootstrap\":\"1\",\"admin_form_template\":\"\",\"admin_details_template\":\"\",\"show-title\":\"1\",\"print\":\"\",\"email\":\"\",\"pdf\":\"\",\"show-referring-table-releated-data\":\"0\",\"tiplocation\":\"above\",\"process-jplugins\":\"2\",\"plugins\":[\"emundustriggers\"],\"plugin_state\":[\"1\"],\"plugin_locations\":[\"both\"],\"plugin_events\":[\"both\"],\"plugin_description\":[\"emundus_events\"]}');";

		
		$query = $this->db->getQuery(true);

		$this->db->setQuery($create_form);
		$inserted = $this->db->execute();
		if ($inserted) {
			$form_id = $this->db->insertid();

			if (!empty($form_id)) {
				$create_list = "INSERT INTO jos_fabrik_lists (label, introduction, form_id, db_table_name, db_primary_key, auto_inc,
                                      connection_id, created, created_by, created_by_alias, modified, modified_by,
                                      checked_out, checked_out_time, published, publish_up, publish_down, access, hits,
                                      rows_per_page, template, order_by, order_dir, filter_action, group_by, private,
                                      params)
                            VALUES ('FORM_UNIT_TESTS', '', " . $form_id . ", 'jos_emundus_unit_test_form', 'jos_emundus_unit_test_form.id', 1, 1, '2023-07-04 11:33:48', 95,
                            'coordinator', '2023-07-04 11:33:48', 95, 95, '2023-07-04 11:33:48', 1, '2023-07-04 11:33:48', null, 1001, 0,
                            10, 'bootstrap', '[\"\"]', '[\"ASC\"]', 'onchange', '', 0,
                            '{\"show-table-filters\":\"1\",\"advanced-filter\":\"0\",\"advanced-filter-default-statement\":\"=\",\"search-mode\":\"0\",\"search-mode-advanced\":\"0\",\"search-mode-advanced-default\":\"all\",\"search_elements\":\"\",\"list_search_elements\":\"null\",\"search-all-label\":\"All\",\"require-filter\":\"0\",\"filter-dropdown-method\":\"0\",\"toggle_cols\":\"0\",\"empty_data_msg\":\"\",\"outro\":\"\",\"list_ajax\":\"0\",\"show-table-add\":\"1\",\"show-table-nav\":\"1\",\"show_displaynum\":\"1\",\"showall-records\":\"0\",\"show-total\":\"0\",\"sef-slug\":\"\",\"show-table-picker\":\"1\",\"admin_template\":\"\",\"show-title\":\"1\",\"pdf\":\"\",\"pdf_template\":\"\",\"pdf_orientation\":\"portrait\",\"pdf_size\":\"a4\",\"pdf_include_bootstrap\":\"1\",\"bootstrap_stripped_class\":\"1\",\"bootstrap_bordered_class\":\"0\",\"bootstrap_condensed_class\":\"0\",\"bootstrap_hover_class\":\"1\",\"responsive_elements\":\"\",\"responsive_class\":\"\",\"list_responsive_elements\":\"null\",\"tabs_field\":\"\",\"tabs_max\":\"10\",\"tabs_all\":\"1\",\"list_ajax_links\":\"0\",\"actionMethod\":\"default\",\"detailurl\":\"\",\"detaillabel\":\"\",\"list_detail_link_icon\":\"search\",\"list_detail_link_target\":\"_self\",\"editurl\":\"\",\"editlabel\":\"\",\"list_edit_link_icon\":\"edit\",\"checkboxLocation\":\"end\",\"hidecheckbox\":\"1\",\"addurl\":\"\",\"addlabel\":\"\",\"list_add_icon\":\"plus\",\"list_delete_icon\":\"delete\",\"popup_width\":\"\",\"popup_height\":\"\",\"popup_offset_x\":\"\",\"popup_offset_y\":\"\",\"note\":\"\",\"alter_existing_db_cols\":\"default\",\"process-jplugins\":\"1\",\"cloak_emails\":\"0\",\"enable_single_sorting\":\"default\",\"collation\":\"utf8mb4_general_ci\",\"force_collate\":\"\",\"list_disable_caching\":\"0\",\"distinct\":\"1\",\"group_by_raw\":\"1\",\"group_by_access\":\"1\",\"group_by_order\":\"\",\"group_by_template\":\"\",\"group_by_template_extra\":\"\",\"group_by_order_dir\":\"ASC\",\"group_by_start_collapsed\":\"0\",\"group_by_collapse_others\":\"0\",\"group_by_show_count\":\"1\",\"menu_module_prefilters_override\":\"1\",\"prefilter_query\":\"\",\"join-display\":\"default\",\"delete-joined-rows\":\"0\",\"show_related_add\":\"0\",\"show_related_info\":\"0\",\"rss\":\"0\",\"feed_title\":\"\",\"feed_date\":\"\",\"feed_image_src\":\"\",\"rsslimit\":\"150\",\"rsslimitmax\":\"2500\",\"csv_import_frontend\":\"10\",\"csv_export_frontend\":\"10\",\"csvfullname\":\"2\",\"csv_export_step\":\"100\",\"newline_csv_export\":\"nl2br\",\"csv_clean_html\":\"leave\",\"csv_multi_join_split\":\",\",\"csv_custom_qs\":\"\",\"csv_frontend_selection\":\"0\",\"incfilters\":\"0\",\"csv_format\":\"0\",\"csv_which_elements\":\"selected\",\"show_in_csv\":\"\",\"csv_elements\":\"null\",\"csv_include_data\":\"1\",\"csv_include_raw_data\":\"0\",\"csv_include_calculations\":\"0\",\"csv_filename\":\"\",\"csv_encoding\":\"UTF-8\",\"csv_double_quote\":\"1\",\"csv_local_delimiter\":\"\",\"csv_end_of_line\":\"n\",\"open_archive_active\":\"0\",\"open_archive_set_spec\":\"\",\"open_archive_timestamp\":\"\",\"open_archive_license\":\"http:\\\\/\\\\/creativecommons.org\\\\/licenses\\\\/by-nd\\\\/2.0\\\\/rdf\",\"dublin_core_type\":\"dc:description.abstract\",\"raw\":\"0\",\"open_archive_elements\":\"null\",\"search_use\":\"0\",\"search_title\":\"\",\"search_description\":\"\",\"search_date\":\"\",\"search_link_type\":\"details\",\"dashboard\":\"0\",\"dashboard_icon\":\"\",\"allow_view_details\":\"11\",\"allow_edit_details\":\"11\",\"allow_edit_details2\":\"\",\"allow_add\":\"11\",\"allow_delete\":\"10\",\"allow_delete2\":\"\",\"allow_drop\":\"10\",\"menu_access_only\":\"0\",\"isview\":\"0\"}');";
				$this->db->setQuery($create_list);
				$inserted = $this->db->execute();
				$list_id = $this->db->insertid();

				$create_hidden_group = "INSERT INTO jos_fabrik_groups (name, css, label, published, created, created_by, created_by_alias, modified, modified_by, checked_out, checked_out_time, is_join, private, params) VALUES ('GROUP_FORM_UNIT_HIDDEN', '', 'GROUP_FORM_UNIT_HIDDEN', 1, '2023-07-04 11:33:48', 95, 'coordinator', '2023-07-04 11:33:48', 95, 0, '2023-07-04 11:33:48', 0, 0, '{\"split_page\":\"0\",\"list_view_and_query\":\"1\",\"access\":\"1\",\"intro\":\"\",\"outro\":\"\",\"repeat_group_button\":\"0\",\"repeat_template\":\"repeatgroup\",\"repeat_max\":\"\",\"repeat_min\":\"\",\"repeat_num_element\":\"\",\"repeat_error_message\":\"\",\"repeat_no_data_message\":\"\",\"repeat_intro\":\"\",\"repeat_add_access\":\"1\",\"repeat_delete_access\":\"1\",\"repeat_delete_access_user\":\"\",\"repeat_copy_element_values\":\"0\",\"group_columns\":\"1\",\"group_column_widths\":\"\",\"repeat_group_show_first\":\"-1\",\"random\":\"0\",\"labels_above\":\"-1\",\"labels_above_details\":\"-1\"}')";
				$this->db->setQuery($create_hidden_group);
				$inserted = $this->db->execute();
				$hidden_group_id = $this->db->insertid();

				$query->clear()
					->insert('jos_fabrik_formgroup')
					->columns('form_id, group_id, ordering')
					->values($this->db->quote($form_id) . ',' . $this->db->quote($hidden_group_id) . ',1');
				$this->db->setQuery($query);
				$this->db->execute();

				$create_elements = "INSERT INTO jos_fabrik_elements (name, group_id, plugin, label, checked_out, checked_out_time, created, created_by, created_by_alias, modified, modified_by, width, height, `default`, hidden, eval, ordering, show_in_list_summary, filter_type, filter_exact_match, published, link_to_detail, primary_key, auto_increment, access, use_in_page_title, parent_id, params) 
                    VALUES ('fnum', " . $hidden_group_id  . ", 'field', 'fnum', 0, '0000-00-00 00:00:00', '2023-07-04 11:33:48', 95, 'coordinator', '2023-07-04 11:33:48', 95, 44, 0, '\$fnum = JFactory::getSession()->get(''emundusUser'')->fnum;if (!isset(\$fnum)) {return JFactory::getApplication()->input->get->get(''rowid'');}return \$fnum;', 1, 1, 1, 0, '', 0, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[],\"placeholder\":\"\",\"password\":0,\"maxlength\":255,\"disable\":0,\"readonly\":0,\"autocomplete\":1,\"speech\":0,\"advanced_behavior\":0,\"text_format\":\"text\",\"integer_length\":11,\"decimal_length\":2,\"field_use_number_format\":0,\"field_thousand_sep\":\",\",\"field_decimal_sep\":\".\",\"text_format_string\":\"\",\"field_format_string_blank\":1,\"text_input_mask\":\"\",\"text_input_mask_autoclear\":0,\"text_input_mask_definitions\":\"\",\"render_as_qrcode\":\"0\",\"scan_qrcode\":\"0\",\"guess_linktype\":\"0\",\"link_target_options\":\"default\",\"rel\":\"\",\"link_title\":\"\",\"link_attributes\":\"\"}'),
                    ('user', " . $hidden_group_id  . ", 'user', 'user', 0, '0000-00-00 00:00:00', '2023-07-04 11:33:48', 95, 'coordinator', '2023-07-04 11:33:48', 95, 20, 0, '', 1, 0, 1, 0, '', 0, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[],\"my_table_data\":\"id\",\"update_on_edit\":\"0\",\"update_on_copy\":\"0\",\"user_use_social_plugin_profile\":\"0\",\"user_noselectionlabel\":\"\"}'),
                    ('time_date', " . $hidden_group_id  . ", 'date', 'time date', 0, '0000-00-00 00:00:00', '2023-07-04 11:33:48', 95, 'coordinator', '2023-07-04 11:33:48', 95, 20, 0, '', 1, 0, 1, 0, '', 0, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-xlarge\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[],\"date_showtime\":\"0\",\"date_time_format\":\"H:i\",\"date_which_time_picker\":\"wicked\",\"date_show_seconds\":\"0\",\"date_24hour\":\"1\",\"bootstrap_time_class\":\"input-medium\",\"placeholder\":\"dd\\\\/mm\\\\/yyyy\",\"date_store_as_local\":\"1\",\"date_table_format\":\"d\\\\\\\\\\\\/m\\\\\\\\\\\\/Y\",\"date_form_format\":\"d\\\\/m\\\\/Y\",\"date_defaulttotoday\":\"1\",\"date_alwaystoday\":\"0\",\"date_firstday\":\"1\",\"date_allow_typing_in_field\":\"1\",\"date_csv_offset_tz\":\"0\",\"date_advanced\":\"0\",\"date_allow_func\":\"\",\"date_allow_php_func\":\"\",\"date_observe\":\"\"}'),
                    ('id', " . $hidden_group_id  . ", 'internalid', 'id', 0, '2023-07-04 11:33:48', '2023-07-04 11:33:48', 95, 'coordinator', '2023-07-04 11:33:48', 95, 20, 0, '', 1, 0, 1, 0, '', 0, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[]}')";

				$this->db->setQuery($create_elements);
				$this->db->execute();

				$create_group = "INSERT INTO jos_fabrik_groups (name, css, label, published, created, created_by, created_by_alias, modified, modified_by, checked_out, checked_out_time, is_join, private, params) VALUES ('GROUP_FORM_UNIT_FIELDS', '', 'GROUP_FORM_UNIT_FIELDS', 1, '2023-07-04 11:33:48', 95, 'coordinator', '2023-07-04 11:33:48', 95, 0, '2023-07-04 11:33:48', 0, 0, '{\"split_page\":\"0\",\"list_view_and_query\":\"1\",\"access\":\"1\",\"intro\":\"\",\"outro\":\"\",\"repeat_group_button\":\"0\",\"repeat_template\":\"repeatgroup\",\"repeat_max\":\"\",\"repeat_min\":\"\",\"repeat_num_element\":\"\",\"repeat_error_message\":\"\",\"repeat_no_data_message\":\"\",\"repeat_intro\":\"\",\"repeat_add_access\":\"1\",\"repeat_delete_access\":\"1\",\"repeat_delete_access_user\":\"\",\"repeat_copy_element_values\":\"0\",\"group_columns\":\"1\",\"group_column_widths\":\"\",\"repeat_group_show_first\":\"1\",\"random\":\"0\",\"labels_above\":\"-1\",\"labels_above_details\":\"-1\"}')";
				$this->db->setQuery($create_group);
				$inserted = $this->db->execute();
				$group_id = $this->db->insertid();

				$query->clear()
					->insert('jos_fabrik_formgroup')
					->columns('form_id, group_id, ordering')
					->values($this->db->quote($form_id) . ',' . $this->db->quote($group_id) . ',1');
				$this->db->setQuery($query);
				$this->db->execute();

				$create_elements = "INSERT INTO jos_fabrik_elements (name, group_id, plugin, label, checked_out, checked_out_time, created, created_by, created_by_alias, modified, modified_by, width, height, `default`, hidden, eval, ordering, show_in_list_summary, filter_type, filter_exact_match, published, link_to_detail, primary_key, auto_increment, access, use_in_page_title, parent_id, params)
                     VALUES ('e_797_7973', " . $group_id  . ", 'field', 'ELEMENT_FIELD', 0, '0000-00-00 00:00:00', '2023-07-04 11:34:14', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '', 0, 1, 1, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"placeholder\":\"\",\"password\":0,\"maxlength\":255,\"disable\":0,\"readonly\":0,\"autocomplete\":1,\"speech\":0,\"advanced_behavior\":0,\"text_format\":\"text\",\"integer_length\":11,\"decimal_length\":2,\"field_use_number_format\":0,\"field_thousand_sep\":\",\",\"field_decimal_sep\":\".\",\"text_format_string\":\"\",\"field_format_string_blank\":1,\"text_input_mask\":\"\",\"text_input_mask_autoclear\":0,\"text_input_mask_definitions\":\"\",\"render_as_qrcode\":\"0\",\"scan_qrcode\":\"0\",\"guess_linktype\":\"0\",\"link_target_options\":\"default\",\"rel\":\"\",\"link_title\":\"\",\"link_attributes\":\"\"}'),
                     ('e_797_7974', " . $group_id  . ", 'textarea', 'ELEMENT_TEXTAREA', 0, '0000-00-00 00:00:00', '2023-07-04 11:34:18', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '', 0, 1, 2, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"textarea_placeholder\":\"\",\"height\":\"6\",\"use_wysiwyg\":\"0\",\"maxlength\":\"255\",\"textarea-showmax\":\"0\",\"width\":\"60\",\"wysiwyg_extra_buttons\":\"1\",\"textarea_field_type\":\"TEXT\",\"textarea_limit_type\":\"char\",\"textarea-tagify\":\"0\",\"textarea_tagifyurl\":\"\",\"textarea-truncate-where\":\"0\",\"textarea-truncate-html\":\"0\",\"textarea-truncate\":\"0\",\"textarea-hover\":\"1\",\"textarea_hover_location\":\"top\"}'),
                     ('e_797_7975', " . $group_id  . ", 'checkbox', 'ELEMENT_CHECKBOX', 0, '0000-00-00 00:00:00', '2023-07-04 11:34:34', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '', 0, 1, 5, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"sub_options\":{\"sub_values\":[1,2],\"sub_labels\":[\"SUBLABEL_797_7975_0\",\"SUBLABEL_797_7975_2\"]},\"options_split_str\":\"\",\"dropdown_populate\":\"\",\"ck_options_per_row\":\"1\",\"sub_default_value\":\"\",\"sub_default_label\":\"\",\"allow_frontend_addtocheckbox\":\"0\",\"chk-allowadd-onlylabel\":\"0\",\"chk-savenewadditions\":\"0\"}'),
                     ('e_797_7976', " . $group_id  . ", 'radiobutton', 'ELEMENT_RADIO', 0, '0000-00-00 00:00:00', '2023-07-04 11:34:57', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '', 0, 1, 6, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"sub_options\":{\"sub_values\":[1,2],\"sub_labels\":[\"SUBLABEL_797_7976_0\",\"SUBLABEL_797_7976_2\"]},\"options_split_str\":\"\",\"dropdown_populate\":\"\",\"options_per_row\":1,\"btnGroup\":0,\"rad-allowadd-onlylabel\":0,\"rad-savenewadditions\":0}'),
                     ('e_797_7977', " . $group_id  . ", 'dropdown', 'ELEMENT_DROPDOWN', 0, '0000-00-00 00:00:00', '2023-07-04 11:35:24', 95, 'coordinator', '2023-07-04 15:29:00', 62, 0, 0, '', 0, 1, 7, 1, '', 1, 1, 0, 0, 0, 1, 0, 0, '{\"sub_options\":{\"sub_values\":[\"Test avec une option \'\",\"2\",\"3\"],\"sub_labels\":[\"SUBLABEL_797_7977_0\",\"SUBLABEL_797_7977_2\",\"SUBLABEL_797_7977_3\"]},\"multiple\":\"0\",\"dropdown_multisize\":\"3\",\"allow_frontend_addtodropdown\":\"0\",\"dd-allowadd-onlylabel\":\"0\",\"dd-savenewadditions\":\"0\",\"options_split_str\":\"\",\"dropdown_populate\":\"\",\"advanced_behavior\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_in_rss_feed\":\"0\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"notempty-message\":[\"\"],\"notempty-validation_condition\":[\"\"],\"tip_text\":[\"\"],\"icon\":[\"\"],\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]}}'),
                     ('e_797_7978', " . $group_id  . ", 'databasejoin', 'ELEMENT_DBJOIN', 0, '0000-00-00 00:00:00', '2023-07-04 11:36:24', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '', 0, 1, 9, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"database_join_display_type\":\"dropdown\",\"join_db_name\":\"data_nationality\",\"join_key_column\":\"id\",\"join_val_column\":\"label_fr\",\"join_conn_id\":\"1\",\"database_join_where_sql\":\"order by {thistable}.id\",\"database_join_where_access\":\"1\",\"database_join_where_when\":\"3\",\"databasejoin_where_ajax\":\"0\",\"database_join_filter_where_sql\":\"\",\"database_join_show_please_select\":\"1\",\"database_join_noselectionvalue\":\"\",\"database_join_noselectionlabel\":\"PLEASE_SELECT\",\"placeholder\":\"\",\"databasejoin_popupform\":\"0\",\"fabrikdatabasejoin_frontend_add\":\"0\",\"join_popupwidth\":\"\",\"databasejoin_readonly_link\":\"0\",\"fabrikdatabasejoin_frontend_select\":\"0\",\"advanced_behavior\":\"0\",\"dbjoin_options_cper_row\":\"1\",\"dbjoin_multiselect_max\":\"0\",\"dbjoin_multilist_size\":\"6\",\"dbjoin_autocomplete_size\":\"20\",\"dbjoin_autocomplete_rows\":\"10\",\"dabase_join_label_eval\":\"\",\"join_desc_column\":\"\",\"dbjoin_autocomplete_how\":\"contains\",\"join_val_column_concat\":\"{thistable}.label_{shortlang}\",\"clean_concat\":\"0\"}'),
                     ('e_797_7979', " . $group_id  . ", 'display', 'ELEMENT_DISPLAY', 0, '0000-00-00 00:00:00', '2023-07-04 11:37:21', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, 'Ajoutez du texte personnalisé pour vos candidats', 0, 1, 4, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[],\"display_showlabel\":\"1\"}'),
                     ('e_797_7980', " . $group_id  . ", 'display', 'ELEMENT_DISPLAY_2', 0, '0000-00-00 00:00:00', '2023-07-04 11:38:03', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '<p>S''il vous plait taisez vous</p>', 0, 1, 3, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[],\"display_showlabel\":\"1\"}'),
                     ('e_797_7981', " . $group_id  . ", 'yesno', 'ELEMENT_YESNO', 0, '0000-00-00 00:00:00', '2023-07-04 11:38:30', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '', 0, 1, 11, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"yesno_default\":\"0\",\"yesno_icon_yes\":\"\",\"yesno_icon_no\":\"\",\"options_per_row\":\"4\",\"toggle_others\":\"0\",\"toggle_where\":\"\"}'),
                     ('e_797_7982', " . $group_id  . ", 'birthday', 'ELEMENT_BIRTHDAY', 0, '0000-00-00 00:00:00', '2023-07-04 11:38:54', 95, 'coordinator', '2023-07-04 11:39:13', 95, 0, 0, '', 0, 1, 12, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"birthday_daylabel\":\"\",\"birthday_monthlabel\":\"\",\"birthday_yearlabel\":\"\",\"birthday_yearopt\":\"\",\"birthday_yearstart\":\"1950\",\"birthday_forward\":\"0\",\"details_date_format\":\"d.m.Y\",\"details_dateandage\":\"0\",\"list_date_format\":\"d\\\\/m\\\\/Y\",\"list_age_format\":\"no\",\"empty_is_null\":\"1\"}'),
                     ('e_797_7983', " . $group_id  . ", 'date', 'ELEMENT_DATE', 0, '0000-00-00 00:00:00', '2023-07-04 11:39:12', 95, 'coordinator', '2023-07-04 11:39:23', 95, 0, 0, '', 0, 1, 13, 1, null, null, 1, 0, 0, 0, 1, 0, 0, '{\"show_in_rss_feed\":\"0\",\"bootstrap_class\":\"input-xlarge\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":{\"plugin\":[\"notempty\"],\"plugin_published\":[\"1\"],\"validate_in\":[\"both\"],\"validation_on\":[\"both\"],\"validate_hidden\":[\"0\"],\"must_validate\":[\"0\"],\"show_icon\":[\"1\"]},\"notempty-message\":[],\"notempty-validation_condition\":[],\"date_showtime\":\"0\",\"date_time_format\":\"H:i\",\"date_which_time_picker\":\"wicked\",\"date_show_seconds\":\"0\",\"date_24hour\":\"1\",\"bootstrap_time_class\":\"input-medium\",\"placeholder\":\"dd\\\\/mm\\\\/yyyy\",\"date_store_as_local\":\"1\",\"date_table_format\":\"d\\\\\\\\\\\\/m\\\\\\\\\\\\/Y\",\"date_form_format\":\"d\\\\/m\\\\/Y\",\"date_defaulttotoday\":\"1\",\"date_alwaystoday\":\"0\",\"date_firstday\":\"1\",\"date_allow_typing_in_field\":\"1\",\"date_csv_offset_tz\":\"0\",\"date_advanced\":\"0\",\"date_allow_func\":\"\",\"date_allow_php_func\":\"\",\"date_observe\":\"\"}'),
                     ('dropdown_multi', " . $group_id  . ", 'dropdown', 'Dropdown_multi', 0, '0000-00-00 00:00:00', '2023-07-05 07:50:04', 62, 'sysadmin', '0000-00-00 00:00:00', 0, 0, 0, '', 0, 0, 8, 1, '', 1, 1, 0, 0, 0, 1, 0, 0, '{\"sub_options\":{\"sub_values\":[\"0\",\"1\",\"2\"],\"sub_labels\":[\"Valeur 1\",\"Valeur 2\",\"Valeur 3\"],\"sub_initial_selection\":[\"0\"]},\"multiple\":\"1\",\"dropdown_multisize\":\"3\",\"allow_frontend_addtodropdown\":\"0\",\"dd-allowadd-onlylabel\":\"0\",\"dd-savenewadditions\":\"0\",\"options_split_str\":\"\",\"dropdown_populate\":\"\",\"advanced_behavior\":\"0\",\"bootstrap_class\":\"input-medium\",\"show_in_rss_feed\":\"0\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[]}'),
                     ('dbjoin_multi', " . $group_id  . ", 'databasejoin', 'Databasejoin multi', 0, '0000-00-00 00:00:00', '2023-07-05 09:13:02', 62, 'sysadmin', '0000-00-00 00:00:00', 0, 0, 0, '', 0, 0, 10, 1, '', 1, 1, 0, 0, 0, 1, 0, 0, '{\"database_join_display_type\":\"multilist\",\"join_conn_id\":\"1\",\"join_db_name\":\"data_departements\",\"join_key_column\":\"departement_id\",\"join_val_column\":\"departement_nom\",\"join_val_column_concat\":\"\",\"database_join_where_sql\":\"\",\"database_join_where_access\":\"1\",\"database_join_where_access_invert\":\"0\",\"database_join_where_when\":\"3\",\"databasejoin_where_ajax\":\"0\",\"databasejoin_where_ajax_default_eval\":\"\",\"database_join_filter_where_sql\":\"\",\"database_join_show_please_select\":\"1\",\"database_join_noselectionvalue\":\"\",\"database_join_noselectionlabel\":\"\",\"placeholder\":\"\",\"databasejoin_popupform\":\"\",\"fabrikdatabasejoin_frontend_add\":\"0\",\"join_popupwidth\":\"\",\"databasejoin_readonly_link\":\"0\",\"fabrikdatabasejoin_frontend_select\":\"0\",\"advanced_behavior\":\"1\",\"dbjoin_options_per_row\":\"4\",\"dbjoin_multiselect_max\":\"0\",\"dbjoin_multilist_size\":\"6\",\"dbjoin_autocomplete_size\":\"20\",\"dbjoin_autocomplete_rows\":\"10\",\"bootstrap_class\":\"input-large\",\"dabase_join_label_eval\":\"\",\"join_desc_column\":\"\",\"dbjoin_autocomplete_how\":\"contains\",\"clean_concat\":\"0\",\"show_in_rss_feed\":\"0\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[]}'),
                     ('cascadingdropdown', " . $group_id  . ", 'cascadingdropdown', 'Cascading dropdown', 0, '0000-00-00 00:00:00', '2023-07-05 09:49:35', 62, 'sysadmin', '2023-07-05 09:52:26', 62, 0, 0, '', 0, 0, 14, 0, '', 1, 1, 0, 0, 0, 1, 0, 0, '{\"cdd_display_type\":\"dropdown\",\"cascadingdropdown_connection\":\"1\",\"cascadingdropdown_table\":\"384\",\"cascadingdropdown_id\":\"jos_emundus_users___id\",\"cascadingdropdown_label\":\"jos_emundus_users___lastname\",\"cascadingdropdown_label_concat\":\"\",\"placeholder\":\"\",\"max-width\":\"\",\"cascadingdropdown_observe\":\"7966\",\"cascadingdropdown_key\":\"jos_emundus_users___id\",\"cascadingdropdown_showpleaseselect\":\"1\",\"cascadingdropdown_noselectionvalue\":\"\",\"cascadingdropdown_noselectionlabel\":\"\",\"cascadingdropdown_filter\":\"\",\"cdd_join_label_eval\":\"\",\"advanced_behavior\":\"0\",\"dbjoin_options_per_row\":\"1\",\"cascadingdropdown_readonly_link\":\"0\",\"bootstrap_class\":\"input-large\",\"show_in_rss_feed\":\"0\",\"show_label_in_rss_feed\":\"0\",\"use_as_rss_enclosure\":\"0\",\"rollover\":\"\",\"tipseval\":\"0\",\"tiplocation\":\"top-left\",\"labelindetails\":\"0\",\"labelinlist\":\"0\",\"comment\":\"\",\"edit_access\":\"1\",\"edit_access_user\":\"\",\"view_access\":\"1\",\"view_access_user\":\"\",\"list_view_access\":\"1\",\"encrypt\":\"0\",\"store_in_db\":\"1\",\"default_on_copy\":\"0\",\"can_order\":\"0\",\"alt_list_heading\":\"\",\"custom_link\":\"\",\"custom_link_target\":\"\",\"custom_link_indetails\":\"1\",\"use_as_row_class\":\"0\",\"include_in_list_query\":\"1\",\"always_render\":\"0\",\"icon_folder\":\"0\",\"icon_hovertext\":\"1\",\"icon_file\":\"\",\"icon_subdir\":\"\",\"filter_length\":\"20\",\"filter_access\":\"1\",\"full_words_only\":\"0\",\"filter_required\":\"0\",\"filter_build_method\":\"0\",\"filter_groupby\":\"text\",\"inc_in_adv_search\":\"1\",\"filter_class\":\"input-medium\",\"filter_responsive_class\":\"\",\"tablecss_header_class\":\"\",\"tablecss_header\":\"\",\"tablecss_cell_class\":\"\",\"tablecss_cell\":\"\",\"sum_on\":\"0\",\"sum_label\":\"Sum\",\"sum_access\":\"1\",\"sum_split\":\"\",\"avg_on\":\"0\",\"avg_label\":\"Average\",\"avg_access\":\"1\",\"avg_round\":\"0\",\"avg_split\":\"\",\"median_on\":\"0\",\"median_label\":\"Median\",\"median_access\":\"1\",\"median_split\":\"\",\"count_on\":\"0\",\"count_label\":\"Count\",\"count_condition\":\"\",\"count_access\":\"1\",\"count_split\":\"\",\"custom_calc_on\":\"0\",\"custom_calc_label\":\"Custom\",\"custom_calc_query\":\"\",\"custom_calc_access\":\"1\",\"custom_calc_split\":\"\",\"custom_calc_php\":\"\",\"validations\":[]}')
                ";

				$this->db->setQuery($create_elements);
				$this->db->execute();

				// fabrik joins
				$query->clear();
				$query->select('id')
					->from('#__fabrik_elements')
					->where('name = "e_797_7978"')
					->where('group_id = ' . $group_id);

				$this->db->setQuery($query);
				$dbjoin_id = $this->db->loadResult();

				$query->clear();
				$query->select('id')
					->from('#__fabrik_elements')
					->where('name = "cascadingdropdown"')
					->where('group_id = ' . $group_id);

				$this->db->setQuery($query);
				$ccd_id = $this->db->loadResult();

				$query->clear();
				$query->select('id')
					->from('#__fabrik_elements')
					->where('name = "dbjoin_multi"')
					->where('group_id = ' . $group_id);

				$this->db->setQuery($query);
				$dbjoin_multi_id = $this->db->loadResult();

				$insert_fabrik_joins = "INSERT INTO jos_fabrik_joins (list_id, element_id, join_from_table, table_join, table_key, table_join_key, join_type, group_id, params) 
                    VALUES (0, " . $dbjoin_id . ", '', 'data_nationality', 'e_797_7978', 'id', 'left', " . $group_id  . ", '{\"join-label\":\"label_fr\",\"type\":\"element\",\"pk\":\"`data_nationality`.`id`\"}'),
                    (0, " . $ccd_id . ", '', 'jos_emundus_users', 'cascadingdropdown', 'id', 'left', " . $group_id  . ", '{\"join-label\":\"lastname\",\"type\":\"element\",\"pk\":\"`jos_emundus_users`.`id`\"}'),
                    (" . $list_id . ", " . $dbjoin_multi_id . ", 'jos_emundus_unit_test_form', 'jos_emundus_unit_test_form_repeat_dbjoin_multi', 'dbjoin_multi', 'parent_id', 'left', 0, '{\"type\":\"repeatElement \",\"pk\":\"`jos_emundus_1001_00_repeat_dbjoin_multi`.`id`\"}')";

				$this->db->setQuery($insert_fabrik_joins);
				$this->db->execute();

				// create the table jos_emundus_unit_test_form
				$create_table = "CREATE TABLE `jos_emundus_unit_test_form` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `time_date` datetime DEFAULT CURRENT_TIMESTAMP,
                  `fnum` varchar(28) NOT NULL,
                  `user` int DEFAULT NULL,
                  `e_797_7973` text,
                  `e_797_7974` text,
                  `e_797_7975` text,
                  `e_797_7976` text,
                  `e_797_7977` text,
                  `e_797_7978` text,
                  `e_797_7979` text,
                  `e_797_7980` text,
                  `e_797_7981` text,
                  `e_797_7982` date DEFAULT NULL,
                  `e_797_7983` datetime DEFAULT NULL,
                  `dropdown_multi` text,
                  `dbjoin_multi` int DEFAULT NULL,
                  `cascadingdropdown` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `fnum` (`fnum`),
                  KEY `user` (`user`)
                )";

				$this->db->setQuery($create_table);
				$this->db->execute();

				$create_table = "CREATE TABLE `jos_emundus_unit_test_form_repeat_dbjoin_multi` (
                      `id` int NOT NULL AUTO_INCREMENT,
                      `parent_id` int DEFAULT NULL,
                      `dbjoin_multi` int DEFAULT NULL,
                      `params` text,
                      PRIMARY KEY (`id`),
                      KEY `fb_parent_fk_parent_id_INDEX` (`parent_id`),
                      KEY `fb_repeat_el_dbjoin_multi_INDEX` (`dbjoin_multi`)
                    )";

				$this->db->setQuery($create_table);
				$this->db->execute();
			}
		}

		return $form_id;
	}

	public function deleteSampleLocation($id)
	{
		$query = $this->db->getQuery(true);

		$query->delete('data_events_location')
			->where('id = ' . $id);
		$this->db->setQuery($query);
		return $this->db->execute();
	}

	public function deleteSampleEvent($id)
	{
		$query = $this->db->getQuery(true);

		$query->delete('jos_emundus_setup_events')
			->where('id = ' . $id);
		$this->db->setQuery($query);
		return $this->db->execute();
	}

	public function deleteSampleEventSlots($slots)
	{
		$query = $this->db->getQuery(true);

		$slots_ids = array_map(function($slot) {
			return $slot->id;
		}, $slots);

		$query->delete('jos_emundus_setup_availabilities')
			->where('slot IN (' . implode(',', $slots_ids) . ')');
		$this->db->setQuery($query);
		$this->db->execute();

		$query->clear()
			->delete('jos_emundus_setup_event_slots')
			->where('id IN (' . implode(',', $slots_ids) . ')');
		$this->db->setQuery($query);
		return $this->db->execute();
	}

	public function deleteSampleNotification($id)
	{
		$query = $this->db->getQuery(true);

		$query->delete('jos_emundus_setup_events_notifications')
			->where('event = ' . $id);
		$this->db->setQuery($query);
		return $this->db->execute();
	}

	public function createEvent($location_id,$user_id,$start = '2026-01-01 00:00:00', $end = '2026-01-01 06:00:00', $name = 'Event test',$available_for = 1,$campaigns = [],$programs = [], $users = [], $slot_duration = '30 minutes', $slot_break_every = 0, $slot_break_time = '0 minutes')
	{
		$m_events = new \EmundusModelEvents();

		$event = [
			'name' => $name,
			'color' => '#000000',
			'location' => $location_id,
			'is_conference_link' => 0,
			'conference_engine' => '',
			'link' => '',
			'generate_link_by' => 0,
			'manager' => null,
			'available_for' => $available_for,
			'campaigns' => $campaigns,
			'programs' => $programs,
			'user_id' => $user_id,
			'teams_subject' => ''
		];
		$event_id = $m_events->createEvent($event['name'], $event['color'], $event['location'], $event['is_conference_link'], $event['conference_engine'], $event['link'], $event['generate_link_by'], $event['manager'], $event['available_for'], $event['campaigns'], $event['programs'], $event['user_id'], $event['teams_subject']);

		$setup_slot = [
			'event_id' => $event_id,
			'slot_duration' => $slot_duration,
			'slot_break_every' => $slot_break_every,
			'slot_break_time' => $slot_break_time,
			'slots_availables_to_show' => 0,
			'slot_can_book_until' => '3 days',
			'slot_can_cancel' => 1,
			'slot_can_cancel_until' => '2028-01-01 date',
			'user_id' => $user_id,
		];
		$m_events->setupSlot($setup_slot['event_id'], $setup_slot['slot_duration'], $setup_slot['slot_break_every'], $setup_slot['slot_break_time'], $setup_slot['slots_availables_to_show'], $setup_slot['slot_can_book_until'], $setup_slot['slot_can_cancel'], $setup_slot['slot_can_cancel_until'], $setup_slot['user_id']);

		$event_slots = [];
		if(!empty($start) && !empty($end))
		{
			$event_slot  = [
				'start_date'    => $start,
				'end_date'      => $end,
				'room'          => null,
				'slot_capacity' => 1,
				'more_infos'    => '',
				'users'         => $users,
				'event_id'      => $event_id,
				'repeat_dates'  => []
			];
			$event_slots = $m_events->saveEventSlot($event_slot['start_date'], $event_slot['end_date'], $event_slot['room'], $event_slot['slot_capacity'], $event_slot['more_infos'], $event_slot['users'], $event_slot['event_id'], $event_slot['repeat_dates'], 0, 0, 1, [], $user_id);
		}
		$booking_notifications = [
			'applicant_notify' => 1 ,
		    'applicant_notify_email' => 1,
		    'applicant_recall' => 1,
		    'applicant_recall_frequency' => 1,
		    'applicant_recall_email' => 1,
		    'manager_recall' => 1,
		    'manager_recall_frequency' => 1,
		    'manager_recall_email' => 1,
		    'users_recall' => 1,
		    'users_recall_frequency' => 1,
		    'users_recall_email' => 1,
		];

		$m_events->saveBookingNotifications($event_id, $booking_notifications, $user_id);

		return ['event_id' => $event_id, 'event_slots' => $event_slots];
	}

	public function createSampleContact(string $email, string $firstname, string $lastname, string $phone = ''): int
	{
		$query = $this->db->getQuery(true);

		$contact = [
			'email' => $email,
			'firstname' => $firstname,
			'lastname' => $lastname,
			'phone_1' => $phone
		];

		$query->insert('jos_emundus_contacts')
			->columns(array_keys($contact))
			->values(implode(',', array_map([$this->db, 'quote'], array_values($contact))));

		$this->db->setQuery($query);
		if($this->db->execute())
		{
			return $this->db->insertid();
		}
		else
		{
			return 0;
		}
	}

	public function deleteSampleContact(int $id): bool
	{
		$query = $this->db->getQuery(true);

		$query->delete('jos_emundus_contacts')
			->where('id = ' . $id);
		$this->db->setQuery($query);
		return $this->db->execute();
	}
}