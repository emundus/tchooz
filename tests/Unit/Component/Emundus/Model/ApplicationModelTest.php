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
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use stdClass;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelApplication
 */
class ApplicationModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('application', $data, $dataName, 'EmundusModelApplication');
	}

	/**
	 * @group   application
	 * @covers EmundusModelApplication::getApplicantInfos
	 *
	 * @since version 2.0.0
	 */
	public function testGetApplicantInfos()
	{
		$applicant_email = 'userunittest' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);

		$applicant_infos = $this->model->getApplicantInfos(0, []);
		$this->assertSame([], $applicant_infos, 'getApplicantInfos should return an empty array if the applicant is not found');

		$applicant_infos = $this->model->getApplicantInfos($applicant, ['u.email', 'u.name']);
		$this->assertNotEmpty($applicant_infos, 'getApplicantInfos should return an array of user information');
		$this->assertArrayHasKey('email', $applicant_infos, 'getApplicantInfos should return an array with email property');
		$this->assertArrayHasKey('name', $applicant_infos, 'getApplicantInfos should return an array with name property');
		$this->assertSame($applicant_infos['email'], $applicant_email, 'getApplicantInfos should return the correct email');

		$applicant_infos = $this->model->getApplicantInfos($applicant, ['u.column_not_existing', 'u.name', 'u.id']);
		$this->assertEmpty($applicant_infos, 'getApplicantInfos should return an empty array if a column does not exist');

		$applicant_infos = $this->model->getApplicantInfos($applicant, 'u.name');
		$this->assertNotEmpty($applicant_infos, 'getApplicantInfos should return an array of user information event if columns are not in an array');

		// Clear datasets
		$this->h_dataset->deleteSampleUser($applicant);
		//
	}

	/**
	 * @group  application
	 * @covers EmundusModelApplication::getUserCampaigns
	 *
	 * @since version 2.0.0
	 */
	public function testGetUserCampaigns()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$user_campaigns = $this->model->getUserCampaigns($applicant);
		$this->assertSame([], $user_campaigns, 'getUserCampaigns should return an empty array if the applicant has no files');

		$program = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$applicant_file = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$user_campaigns = $this->model->getUserCampaigns($applicant);
		$this->assertNotEmpty($user_campaigns, 'getUserCampaigns should return an array of user campaigns');

		$user_campaigns = $this->model->getUserCampaigns($applicant, $campaign_id);
		$this->assertIsObject($user_campaigns, 'getUserCampaigns should return an object of user campaign pass');

		$second_campaign_id = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$second_applicant_file = $this->h_dataset->createSampleFile($second_campaign_id, $applicant);

		$user_campaigns = $this->model->getUserCampaigns($applicant);
		$this->assertSame(2,count($user_campaigns), 'getUserCampaigns should return an array with 2 user campaigns');
		$this->assertSame($user_campaigns[0]->id, $campaign_id, 'getUserCampaigns should return the correct campaign id');
		$this->assertSame($user_campaigns[1]->id, $second_campaign_id, 'getUserCampaigns should return the correct campaign id');

		$unpublish_campaign = [
			'id' => $campaign_id,
			'published' => 0,
		];
		$unpublish_campaign = (object) $unpublish_campaign;
		$this->db->updateObject('#__emundus_setup_campaigns', $unpublish_campaign, 'id');

		$user_campaigns = $this->model->getUserCampaigns($applicant, null, true);
		$this->assertSame(1,count($user_campaigns), 'getUserCampaigns should return an array with 1 user campaign after unpublish one');

		// Clear datasets
		$this->h_dataset->deleteSampleFile($applicant_file);
		$this->h_dataset->deleteSampleFile($second_applicant_file);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		//
	}

	/**
	 * @group application
	 * @covers EmundusModelApplication::getCampaignByFnum
	 *
	 * @since version 2.0.0
	 */
	public function testGetCampaignByFnum()
	{
		$campaign = $this->model->getCampaignByFnum('');
		$this->assertSame([], $campaign);

		// Datasets
		$user_id             = $this->h_dataset->createSampleUser(1000, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $user_id);
		//

		$campaign = $this->model->getCampaignByFnum($fnum);
		$this->assertNotEmpty($campaign);
		$this->assertSame($campaign[0]->id, $campaign_id);

		// Clear datasets
		$this->h_dataset->deleteSampleUser($user_id);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		//
	}

	/**
	 * @group application
	 * @covers EmundusModelApplication::getUserAttachments
	 *
	 * @since version 2.0.0
	 */
	public function testGetUserAttachments()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$attachments = $this->model->getUserAttachments($applicant);
		$this->assertSame([], $attachments, 'getUserAttachments should return an empty array if the applicant has no files');

		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $applicant);
		$attachment_id       = $this->h_dataset->createSampleAttachment();
		$upload              = $this->h_dataset->createSampleUpload($fnum, $campaign_id, $applicant, $attachment_id);

		$attachments = $this->model->getUserAttachments($applicant);
		$this->assertNotEmpty($attachments, 'getUserAttachments should return an array of user attachments');

		$attachments = $this->model->getUserAttachments(0);
		$this->assertSame([], $attachments, 'getUserAttachments should return an empty array if the applicant does not exist');

		// Clear datasets
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleUser($user_id_coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		$this->h_dataset->deleteSampleAttachment($attachment_id);
		$this->h_dataset->deleteSampleUpload($upload);
		//
	}

	/**
	 * @group application
	 * @covers EmundusModelApplication::getUserAttachmentsByFnum
	 *
	 * @since version 2.0.0
	 */
	public function testGetUserAttachmentsByFnum()
	{
		if (!defined('EMUNDUS_PATH_ABS'))
		{
			define('EMUNDUS_PATH_ABS', JPATH_ROOT);
		}

		$attachments = $this->model->getUserAttachmentsByFnum('');
		$this->assertSame([], $attachments);

		// Datasets
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$user_id             = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$user_id_coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);
		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $user_id_coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $user_id_coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $user_id);
		//

		$attachments = $this->model->getUserAttachmentsByFnum($fnum, '', null, false, $user_id_coordinator);
		$this->assertEmpty($attachments);

		// Datasets
		$first_attachment_id  = $this->h_dataset->createSampleAttachment();
		$second_attachment_id = $this->h_dataset->createSampleAttachment();
		// Insert these attachments in the database
		$first_attachment_to_profile = [
			'profile_id' => 1000,
			'attachment_id' => $first_attachment_id,
			'mandatory' => 1,
			'ordering' => 1,
		];
		$first_attachment_to_profile = (object) $first_attachment_to_profile;
		$this->db->insertObject('#__emundus_setup_attachment_profiles', $first_attachment_to_profile, 'id');
		$second_attachment_to_profile = [
			'profile_id' => 1000,
			'attachment_id' => $first_attachment_id,
			'mandatory' => 1,
			'ordering' => 1,
		];
		$second_attachment_to_profile = (object) $second_attachment_to_profile;
		$this->db->insertObject('#__emundus_setup_attachment_profiles', $second_attachment_to_profile, 'id');

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

	/**
	 * @group application
	 * @covers EmundusModelApplication::getUsersComments
	 *
	 * @since version 2.0.0
	 */
	public function testGetUsersComments()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$comments = $this->model->getUsersComments($applicant);
		$this->assertSame([], $comments, 'getUsersComments should return an empty array if the applicant has no comments');

		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$comment = $this->h_dataset->createSampleComment($fnum, $applicant, $coordinator);

		$comments = $this->model->getUsersComments($applicant);
		$this->assertNotEmpty($comments, 'getUsersComments should return an array of user comments');

		// Clear datasets
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleComment($comment);
		//
	}

	/**
	 * @group application
	 * @covers EmundusModelApplication::getComment
	 *
	 * @since version 2.0.0
	 */
	public function testGetComment()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$comment = $this->model->getComment(0);
		$this->assertSame([], $comment, 'getComment should return an empty array if the comment does not exist');

		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$comment_id = $this->h_dataset->createSampleComment($fnum, $applicant, $coordinator);

		$comment = $this->model->getComment($comment_id);
		$this->assertNotEmpty($comment, 'getComment should return an array of comment information');

		// Clear datasets
		$this->h_dataset->deleteSampleComment($comment_id);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleUser($coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		//
	}

	/**
	 * @group application
	 * @covers EmundusModelApplication::getTag
	 *
	 * @since version 2.0.0
	 */
	public function testGetTag()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$tag = $this->model->getTag(0);
		$this->assertSame([], $tag, 'getTag should return an empty array if the tag does not exist');

		$tag_id = $this->h_dataset->createSampleTag();
		$assoc_tag = [
			'fnum' => $fnum,
			'id_tag' => $tag_id,
			'date_time' => Factory::getDate()->toSql(),
			'user_id' => $coordinator
		];
		$assoc_tag = (object) $assoc_tag;
		$this->db->insertObject('#__emundus_tag_assoc', $assoc_tag, 'id');
		$assoc_tag_id = $this->db->insertid();

		$tag = $this->model->getTag($assoc_tag_id);
		$this->assertNotEmpty($tag, 'getTag should return an array of tag information');
		$this->assertSame($tag['id_tag'], $tag_id, 'getTag should return the correct tag id');
		$this->assertSame($tag['fnum'], $fnum, 'getTag should return the correct fnum');

		// Clear datasets
		$this->h_dataset->deleteSampleTag($tag_id);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleUser($coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		//
	}

	/**
	 * @group application
	 * @covers EmundusModelApplication::getFileComments
	 *
	 * @since version 2.0.0
	 */
	public function testGetFileComments()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$comments = $this->model->getFileComments(0);
		$this->assertSame([], $comments, 'getFileComments should return an empty array if the file does not exist');

		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$comment_id = $this->h_dataset->createSampleComment($fnum, $applicant, $coordinator);
		$comments = $this->model->getFileComments($fnum);
		$this->assertNotEmpty($comments, 'getFileComments should return an array of file comments');

		$this->assertSame('Test unitaire', $comments[0]->reason, 'getFileComments should return the correct reason');
		$this->assertSame('Commentaire pour un test unitaire', $comments[0]->comment, 'getFileComments should return the correct comment (warning: key is comment and NOT comment_body');
		$this->assertSame($fnum, $comments[0]->fnum, 'getFileComments should return the correct fnum');

		// Clear datasets
		$this->h_dataset->deleteSampleComment($comment_id);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleUser($coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		//
	}

	/**
	 * @group application
	 * @covers EmundusModelApplication::getFileOwnComments
	 *
	 * @since version 2.0.0
	 */
	public function testGetFileOwnComments()
	{
		$applicant_email = 'applicant' . rand(0, 1000) . '@emundus.test.fr';
		$coordinator_email = 'coordinator' . rand(0, 1000) . '@emundus.test.fr';
		$applicant = $this->h_dataset->createSampleUser(1000, $applicant_email);
		$coordinator = $this->h_dataset->createSampleUser(2, $coordinator_email);

		$comments = $this->model->getFileOwnComments(0,0);
		$this->assertEmpty($comments, 'getFileComments should return an empty array if the file does not exist');

		$program             = $this->h_dataset->createSampleProgram('Programme Test Unitaire', $coordinator);
		$campaign_id         = $this->h_dataset->createSampleCampaign($program, $coordinator);
		$fnum                = $this->h_dataset->createSampleFile($campaign_id, $applicant);

		$comment_id = $this->h_dataset->createSampleComment($fnum, $applicant, $coordinator);
		$comments = $this->model->getFileOwnComments($fnum,0);
		$this->assertEmpty($comments, 'getFileComments should return an empty array if the user does not exist');

		$comments = $this->model->getFileOwnComments($fnum,$applicant);
		$this->assertEmpty($comments, 'getFileComments should return an empty array if the user has no comments');

		$comments = $this->model->getFileOwnComments($fnum,$coordinator);
		$this->assertNotEmpty($comments, 'getFileComments should return an array of file comments');

		$this->assertSame('Test unitaire', $comments[0]->reason, 'getFileComments should return the correct reason');
		$this->assertSame('Commentaire pour un test unitaire', $comments[0]->comment, 'getFileComments should return the correct comment (warning: key is comment and NOT comment_body');
		$this->assertSame($fnum, $comments[0]->fnum, 'getFileComments should return the correct fnum');

		// Clear datasets
		$this->h_dataset->deleteSampleComment($comment_id);
		$this->h_dataset->deleteSampleUser($applicant);
		$this->h_dataset->deleteSampleUser($coordinator);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
		//
	}

	public function testuploadAttachment()
	{
		$upload = $this->model->uploadAttachment([]);
		$this->assertSame($upload, false);

		$user_id             = $this->h_dataset->createSampleUser(1000, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
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
		$done = $this->model->applicantCustomAction(0, '', 0, false, $this->dataset['applicant']);
		$this->assertSame($done, false, 'applicantCustomAction should return false if action and fnum are empty');

		$done = $this->model->applicantCustomAction(0, $this->dataset['fnum'], 0, false,  $this->dataset['applicant']);
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

		$done = $this->model->applicantCustomAction(0, $this->dataset['fnum'], 0, false,  $this->dataset['applicant']);
		$this->assertSame($done, false, 'applicantCustomAction should return false if action is not found in module params');

		$done = $this->model->applicantCustomAction('mod_em_application_custom_actions1', $this->dataset['fnum'], 0, false,  $this->dataset['applicant']);
		$this->assertTrue($done, 'Custom action should be done because file is in correct status');

		$done = $this->model->applicantCustomAction('mod_em_application_custom_actions1', $this->dataset['fnum'], 0, false,  $this->dataset['applicant']);
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
