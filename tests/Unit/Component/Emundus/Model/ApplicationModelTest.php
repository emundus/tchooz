<?php

/**
 * @package         Joomla.UnitTest
 * @subpackage      Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Component\Emundus\Model;

use EmundusModelApplication;
use Joomla\Tests\Unit\UnitTestCase;
use stdClass;

class ApplicationModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('application', $data, $dataName, 'EmundusModelApplication');
	}

	/**
	 * @group   application
	 *
	 * @return  void
	 *
	 * @since   4.2.0
	 */
	public function testGetApplicantInfos()
	{
		$applicant_infos = $this->model->getApplicantInfos(0, []);
		$this->assertSame([], $applicant_infos);
	}

	public function testGetUserAttachmentsByFnum()
	{
		if (!defined('EMUNDUS_PATH_ABS'))
		{
			define('EMUNDUS_PATH_ABS', JPATH_ROOT);
		}

		$attachments = $this->model->getUserAttachmentsByFnum('');
		$this->assertSame([], $attachments);

		// Datasets
		$user_id             = $this->h_dataset->createSampleUser(9, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $user_id);
		//

		$attachments = $this->model->getUserAttachmentsByFnum($fnum);
		$this->assertEmpty($attachments);

		// Datasets
		$first_attachment_id  = $this->h_dataset->createSampleAttachment();
		$second_attachment_id = $this->h_dataset->createSampleAttachment();
		$first_upload         = $this->h_dataset->createSampleUpload($fnum, $campaign_id, $user_id, $first_attachment_id);
		$second_upload        = $this->h_dataset->createSampleUpload($fnum, $campaign_id, $user_id, $second_attachment_id);
		//

		$attachments = $this->model->getUserAttachmentsByFnum($fnum, '', null, false, $user_id_coordinator);
		$this->assertNotEmpty($attachments);
		$this->assertSame(count($attachments), 2);


		// attachments should contain 1 element with existsOnServer = false
		$current_attachment = $attachments[0];
		$this->assertSame($current_attachment->existsOnServer, false);

		// attachments should contain profiles attribute
		$this->assertObjectHasProperty('profiles', $current_attachment);

		// if i use search parameter, only pertinent attachments should be returned
		$search      = $attachments[0]->value;
		$attachments = $this->model->getUserAttachmentsByFnum($fnum, $search, null, false, $user_id_coordinator);
		$this->assertNotEmpty($attachments);
		$this->assertSame($attachments[0]->value, $search);
		$this->assertSame(count($attachments), 1);

		// Clear datasets
		$this->h_dataset->deleteSampleUser($user_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		$this->h_dataset->deleteSampleAttachment($first_attachment_id);
		$this->h_dataset->deleteSampleAttachment($second_attachment_id);
		//
	}

	public function testuploadAttachment()
	{
		$upload = $this->model->uploadAttachment([]);
		$this->assertSame($upload, false);

		$user_id             = $this->h_dataset->createSampleUser(9, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $user_id);
		$attachment_id       = $this->h_dataset->createSampleAttachment();

		$data          = [];
		$data['key']   = ['fnum', 'user_id', 'campaign_id', 'attachment_id', 'filename', 'local_filename', 'timedate', 'can_be_deleted', 'can_be_viewed'];
		$data['value'] = [$fnum, $user_id, $campaign_id, $attachment_id, 'test.pdf', 'test.pdf', date('Y-m-d H:i:s'), 1, 1];

		$upload = $this->model->uploadAttachment($data);
		$this->assertGreaterThan(0, $upload);

		// Clear datasets
		$this->h_dataset->deleteSampleUser($user_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		$this->h_dataset->deleteSampleAttachment($attachment_id);
		//
	}

	public function testgetTabs()
	{
		$tabs = $this->model->getTabs(0);
		$this->assertSame([], $tabs);
	}

	public function testdeleteTab()
	{
		$deleted = $this->model->deleteTab(0, 0);
		$this->assertSame(false, $deleted);
	}

	public function testmoveToTab()
	{
		$moved = $this->model->moveToTab(0, 0);
		$this->assertSame(false, $moved);
	}

	public function testupdateTabs()
	{
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');

		$updated = $this->model->updateTabs([], 0);
		$this->assertSame(false, $updated, 'No tabs to update');

		$updated = $this->model->updateTabs([], $user_id_coordinator);
		$this->assertSame(false, $updated, 'No tabs to update');

		$tab           = new stdClass();
		$tab->id       = 999;
		$tab->name     = 'Test';
		$tab->ordering = 1;

		$updated = $this->model->updateTabs([['id' => 1, 'name' => 'Test', 'ordering' => 1]], 0);
		$this->assertSame(false, $updated, 'Missing user id');

		$updated = $this->model->updateTabs([['id' => 1, 'name' => 'Test', 'ordering' => 1]], $user_id_coordinator);
		$this->assertSame(false, $updated,);

		$tab->id = $this->model->createTab('Test', $user_id_coordinator);
		$this->assertNotEmpty($tab->id);

		$updated = $this->model->updateTabs([$tab], $user_id_coordinator);
		$this->assertSame(true, $updated, 'Tab updated');

		$origin_tab_id = $tab->id;
		$tab->id = $tab->id . ' OR 1=1';
		$updated = $this->model->updateTabs([$tab], 0);
		$this->assertSame(false, $updated, 'SQL Injection impossible');

		// Clear datasets
		$this->model->deleteTab($origin_tab_id, $user_id_coordinator);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		//
	}

	/**
	 * @covers EmundusModelApplication::isTabOwnedByUser
	 * @return void
	 */
	public function testisTabOwnedByUser()
	{
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');

		$owned = $this->model->isTabOwnedByUser(0, $user_id_coordinator);
		$this->assertSame(false, $owned, 'An invalid tab id should return false');

		$owned = $this->model->isTabOwnedByUser(1);
		$this->assertSame(false, $owned, 'An invalid user id should return false');

		$tab           = new stdClass();
		$tab->name     = 'Unit Test ' . time();
		$tab->ordering = 9999;
		$tab->id       = $this->model->createTab('Test', $user_id_coordinator);
		$this->assertNotEmpty($tab->id);

		$owned = $this->model->isTabOwnedByUser($tab->id, $user_id_coordinator);
		$this->assertSame(true, $owned, 'Tab is owned by user');

		$owned = $this->model->isTabOwnedByUser($tab->id, 0);
		$this->assertSame(false, $owned, 'Tab is not owned by user');

		$owned = $this->model->isTabOwnedByUser(9999 . ' OR 1=1', $user_id_coordinator);
		$this->assertSame(false, $owned, 'SQL Injection impossible');

		// Clear datasets
		$this->model->deleteTab($tab->id, $user_id_coordinator);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		//
	}

	/**
	 * @covers EmundusModelApplication::applicantCustomAction
	 * @return void
	 */
	public function testapplicantCustomAction()
	{
		$done = $this->model->applicantCustomAction(0, '');
		$this->assertSame($done, false, 'applicantCustomAction should return false if action and fnum are empty');

		$done = $this->model->applicantCustomAction(0, $this->dataset['fnum']);
		$this->assertSame($done, false, 'applicantCustomAction should return false if action is empty');

		// get module params
		$query = $this->db->createQuery();
		$query->select('id, params')
			->from('#__modules')
			->where('module LIKE ' . $this->db->quote('mod_emundus_applications'))
			->where('published = 1');

		$this->db->setQuery($query);
		$module = $this->db->loadAssoc();
		$params = json_decode($module['params'], true);

		$params['mod_em_application_custom_actions'] = [
			'mod_em_application_custom_actions1' => [
				'mod_em_application_custom_action_new_status' => 1,
				'mod_em_application_custom_action_status'     => [0]
			]
		];

		// update module params
		$query = $this->db->getQuery(true);
		$query->update('#__modules')
			->set('params = ' . $this->db->quote(json_encode($params)))
			->where('id = ' . $this->db->quote($module['id']));

		$this->db->setQuery($query);
		$this->db->execute();

		$done = $this->model->applicantCustomAction(0, $this->dataset['fnum']);
		$this->assertSame($done, false, 'applicantCustomAction should return false if action is not found in module params');

		$done = $this->model->applicantCustomAction('mod_em_application_custom_actions1', $this->dataset['fnum']);
		$this->assertTrue($done, 'Custom action should be done because file is in correct status');

		$done = $this->model->applicantCustomAction('mod_em_application_custom_actions1', $this->dataset['fnum']);
		$this->assertFalse($done, 'Action should no longer work because file status has changed');

		// Clear datasets
		$query->clear()
			->update('#__modules')
			->set('params = ' . $this->db->quote($module['params']))
			->where('id = ' . $this->db->quote($module['id']));
		$this->db->setQuery($query);
		$this->db->execute();
		//
	}

	public function testgetApplicationMenu()
	{
		$menus = $this->model->getApplicationMenu($this->dataset['coordinator']);
		$this->assertNotEmpty($menus, 'A coordinator should have access to the application menu');

		$menus = $this->model->getApplicationMenu($this->dataset['applicant']);
		$this->assertEmpty($menus, 'An applicant should not have access to the application menu');
	}
}
