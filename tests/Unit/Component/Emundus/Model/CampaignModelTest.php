<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use DateTime;
use EmundusModelProfile;
use EmundusModelProgramme;
use Exception;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\User\UserCategoryEntity;
use Tchooz\Repositories\User\UserCategoryRepository;

require_once JPATH_SITE . '/components/com_emundus/models/programme.php';
require_once JPATH_SITE . '/components/com_emundus/models/profile.php';
require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelCampaign
 */
class CampaignModelTest extends UnitTestCase
{
	/**
	 * @var    EmundusModelProgramme
	 * @since  4.2.0
	 */
	private $m_programme;

	/**
	 * @var    EmundusModelProfile
	 * @since  4.2.0
	 */
	private $m_profile;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('campaign', $data, $dataName, 'EmundusModelCampaign');

		$this->m_programme = new EmundusModelProgramme();
		$this->m_profile   = new EmundusModelProfile();
	}

	public function testCampaignWorkflowDatabase()
	{
		/**
		 * Table emundus_campaign_workflow should exists
		 */
		$query = $this->db->getQuery(true);
		$query->select('*')
			->from($this->db->quoteName('#__emundus_campaign_workflow'));

		try
		{
			$this->db->setQuery($query);
			$this->db->loadObjectList();
			$table_exists = true;
		}
		catch (Exception $e)
		{
			$table_exists = false;
		}

		$this->assertTrue($table_exists, 'Table #__emundus_campaign_workflow should exists');

		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_campaign_workflow'))
			->where($this->db->quoteName('display_preliminary_documents') . ' IS NULL')
			->orWhere($this->db->quoteName('specific_documents') . ' IS NULL');

		try
		{
			$this->db->setQuery($query);
			$this->db->loadObjectList();
			$columns_exists = true;
		}
		catch (Exception $e)
		{
			$columns_exists = false;
		}

		$this->assertTrue($columns_exists, 'Table #__emundus_campaign_workflow should have 2 new columns display_preliminary_documents and specific_documents');

		$query->clear()
			->select('*')
			->from($this->db->quoteName('#__emundus_campaign_workflow_repeat_documents'));

		try
		{
			$this->db->setQuery($query);
			$this->db->loadObjectList();
			$table_exists = true;
		}
		catch (Exception $e)
		{
			$table_exists = false;
		}

		$this->assertTrue($table_exists, 'Table #__emundus_campaign_workflow_repeat_documents should exists');
	}

	/**
	 * @covers EmundusModelCampaign::createDocument
	 *
	 * @since version 1.0.0
	 */
	public function testCreateDocument()
	{
		$document = [
			'name' => [
				'fr' => ''
			],
		];
		$types    = [''];

		$created = $this->model->createDocument($document, $types, null, 9);
		$this->assertFalse($created['status'], 'Assert impossible to create document with empty name');

		$document['name']['fr'] = 'Test';
		$created                = $this->model->createDocument($document, $types, null, 9);
		$this->assertFalse($created['status'], 'Assert impossible to create document with empty types');
	}

	/**
	 * @covers EmundusModelCampaign::createCampaign
	 *
	 * @since version 1.0.0
	 */
	public function testCreateCampaign()
	{
		$new_campaign_id = $this->model->createCampaign([], $this->dataset['coordinator']);
		$this->assertEmpty($new_campaign_id, 'Assert can not create campaign without data');

		$new_campaign_id = $this->model->createCampaign(['limit_status' => 1, 'profile_id' => 1000], $this->dataset['coordinator']);
		$this->assertEmpty($new_campaign_id, 'Assert can not create campaign without label');

		$start_date = new DateTime();
		$start_date->modify('-1 day');

		$end_date = new DateTime();
		$end_date->modify('+1 year');

		$inserting_datas = [
			'label'             => json_encode(['fr' => 'Campagne test unitaire', 'en' => 'Campagne test unitaire']),
			'description'       => 'Lorem ipsum',
			'short_description' => 'Lorem ipsum',
			'start_date'        => $start_date->format('Y-m-d H:i:s'),
			'end_date'          => $end_date->format('Y-m-d H:i:s'),
			'profile_id'        => 1000,
			'training'          => $this->dataset['program']['programme_code'],
			'year'              => '2022-2023',
			'published'         => 1,
			'is_limited'        => 0
		];

		$new_campaign_id = $this->model->createCampaign($inserting_datas, $this->dataset['coordinator']);
		$this->assertGreaterThan(0, $new_campaign_id, 'Assert campaign creation works.');

		// Testing create campaign with user categories values
		$userCategoryRepository = new UserCategoryRepository();
		$categoryEntity1 = new UserCategoryEntity(
			id: 0,
			label: 'Test Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);
		$categoryEntity2 = new UserCategoryEntity(
			id: 0,
			label: 'Another Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);
		$category1 = $userCategoryRepository->save($categoryEntity1);
		$category2 = $userCategoryRepository->save($categoryEntity2);

		$inserting_datas = [
			'label'             => json_encode(['fr' => 'Campagne test unitaire', 'en' => 'Campagne test unitaire']),
			'description'       => 'Lorem ipsum',
			'short_description' => 'Lorem ipsum',
			'start_date'        => $start_date->format('Y-m-d H:i:s'),
			'end_date'          => $end_date->format('Y-m-d H:i:s'),
			'profile_id'        => 1000,
			'training'          => $this->dataset['program']['programme_code'],
			'year'              => '2022-2023',
			'published'         => 1,
			'is_limited'        => 0,
			'usercategories'   => [$category1->getId(), $category2->getId()]
		];

		$new_campaign_id_2 = $this->model->createCampaign($inserting_datas, $this->dataset['coordinator']);
		$this->assertGreaterThan(0, $new_campaign_id, 'Assert campaign creation works.');

		$user_categories = $this->model->getCampaignUserCategoriesValues($new_campaign_id_2);
		$this->assertCount(2, $user_categories, 'Assert campaign is created with user categories values');

		$program = $this->model->getProgrammeByCampaignID($new_campaign_id);
		$this->assertNotEmpty($program, 'Getting program from campaign id works');
		$this->assertSame($program['code'], $this->dataset['program']['programme_code'], 'The program code used in creation is retrieved when getting program by the new campaign id');

		$program_by_training = $this->model->getProgrammeByTraining($program['code']);
		$this->assertNotEmpty($program_by_training->id, 'Assert getting program by his training code works');

		$campaigns_by_program    = $this->model->getCampaignsByProgramId($program_by_training->id);
		$campaign_ids_by_program = [];
		foreach ($campaigns_by_program as $campaign)
		{
			$campaign_ids_by_program[] = $campaign->id;
		}
		$this->assertTrue(in_array($new_campaign_id, $campaign_ids_by_program), 'Assert campaign is found in getCampaignsByProgramId function');

		$this->assertTrue($this->model->unpublishCampaign([$new_campaign_id]), 'Assert unpublish campaign works');
		$this->assertTrue($this->model->publishCampaign([$new_campaign_id]), 'Assert publish campaign works');
		$this->assertTrue($this->model->pinCampaign($new_campaign_id), 'Assert pin campaign works properly');

		$deleted = $this->model->deleteCampaign([$new_campaign_id, $new_campaign_id_2]);
		$this->assertTrue($deleted, 'Campaign deletion works properly');

		$userCategoryRepository->delete($category1->getId());
		$userCategoryRepository->delete($category2->getId());
	}

	/**
	 * @covers EmundusModelCampaign::updateCampaign
	 *
	 * @since version 1.0.0
	 */
	public function testUpdateCampaign()
	{
		$updated = $this->model->updateCampaign([], 1);
		$this->assertFalse($updated, 'Update campaign with empty data does nothing');

		$updated = $this->model->updateCampaign(['label' => ['fr' => 'Mise à jour de campagne TU', 'en' => 'Mise à jour de campagne TU']], 0);
		$this->assertFalse($updated, 'Update campaign with empty campaign_id does nothing');

		$updated = $this->model->updateCampaign(['start_date' => null], 0);
		$this->assertFalse($updated, 'Update campaign with empty data start_date stops the update');

		$updated = $this->model->updateCampaign(['end_date' => null], 0);
		$this->assertFalse($updated, 'Update campaign with empty data end_date stops the update');

		// Testing update campaign with user categories values
		if(!class_exists('EmundusModelSettings'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/settings.php';
		}
		$m_settings = new \EmundusModelSettings();
		$m_settings->updateEmundusParam('emundus', 'enable_user_categories', 1, $this->dataset['coordinator']);
		$userCategoryRepository = new UserCategoryRepository();
		$categoryEntity1 = new UserCategoryEntity(
			id: 0,
			label: 'Test Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);
		$categoryEntity2 = new UserCategoryEntity(
			id: 0,
			label: 'Another Category',
			created_by: 1,
			created_at: date('Y-m-d H:i:s'),
			published: 1
		);
		$category1 = $userCategoryRepository->save($categoryEntity1);
		$category2 = $userCategoryRepository->save($categoryEntity2);

		$updated = $this->model->updateCampaign(['start_date' => date('Y-m-d H:i:s'), 'end_date' => date('Y-m-d H:i:s'), 'usercategories' => [$category1->getId(), $category2->getId()]], $this->dataset['campaign'], $this->dataset['coordinator']);
		$this->assertTrue($updated, 'Update campaign with user categories values works');

		$user_categories = $this->model->getCampaignUserCategoriesValues($this->dataset['campaign']);
		$this->assertCount(2, $user_categories, 'Assert campaign is updated with user categories values');

		$updated = $this->model->updateCampaign(['start_date' => date('Y-m-d H:i:s'), 'end_date' => date('Y-m-d H:i:s'), 'usercategories' => []], $this->dataset['campaign'], $this->dataset['coordinator']);
		$this->assertTrue($updated, 'Update campaign with empty user categories values works');
		$user_categories = $this->model->getCampaignUserCategoriesValues($this->dataset['campaign']);
		$this->assertCount(0, $user_categories, 'Assert campaign user categories values are deleted when updating with empty array');

		$userCategoryRepository->delete($category1->getId());
		$userCategoryRepository->delete($category2->getId());
		$m_settings->updateEmundusParam('emundus', 'enable_user_categories', 0, $this->dataset['coordinator']);
	}

	/**
	 * @covers EmundusModelCampaign::getAllCampaigns
	 *
	 * @since version 1.0.0
	 */
	public function testGetAllCampaigns()
	{
		$campaigns = $this->model->getAllCampaigns();
		$this->assertIsArray($campaigns, 'La fonction de récupération des campagnes renvoie toujours un tableau');
	}

	/**
	 * @covers EmundusModelCampaign::getProgrammeByTraining
	 *
	 * @since version 1.0.0
	 */
	public function testGetProgrammeByTraining()
	{
		$progam = $this->model->getProgrammeByTraining('');
		$this->assertEmpty($progam, 'Get programme by training without param returns null');
	}

	/**
	 * @covers EmundusModelCampaign::pinCampaign
	 *
	 * @since version 1.0.0
	 */
	function testpinCampaign()
	{
		$pinned = $this->model->pinCampaign(999999);
		$this->assertFalse($pinned, 'La campagne 9999 n\'existe pas, donc on ne peut pas la mettre en avant');

		$pinned = $this->model->pinCampaign($this->dataset['campaign']);
		$this->assertTrue($pinned, 'La campagne existe, on peut la mettre en avant');

		$campaign = $this->model->getCampaignByID($this->dataset['campaign']);
		$this->assertSame(1, (int) $campaign['pinned'], 'La campagne est bien mise en avant');

		$pinned          = $this->model->pinCampaign($this->dataset['campaign']);

		// assert new campaign is pinned
		$campaign = $this->model->getCampaignByID($this->dataset['campaign']);
		$this->assertSame(1, (int) $campaign['pinned'], 'La nouvelle campagne est mise en avant');

		// on duplicate campaign, pinned is not duplicated
		$duplicated = $this->model->duplicateCampaign($this->dataset['campaign']);
		$this->assertTrue($duplicated, 'La campagne a bien été dupliquée');

		// get the last campaign

		$query = $this->db->getQuery(true);
		$query->select('id')
			->from('#__emundus_setup_campaigns')
			->order('id DESC')
			->setLimit(1);
		$this->db->setQuery($query);
		$last_campaign_id = $this->db->loadResult();

		$campaign = $this->model->getCampaignByID($last_campaign_id);
		$this->assertEmpty($campaign['pinned'], 'La nouvelle campagne dupliquée n\'est pas mise en avant');
	}

	/**
	 * @covers EmundusModelCampaign::unpinCampaign
	 *
	 * @since version 1.0.0
	 */
	function testunpinCampaign()
	{
		$unpinned = $this->model->unpinCampaign(0);
		$this->assertFalse($unpinned, 'La campagne 0 n\'existe pas, donc on ne peut pas la retirer de la mise en avant');

		$pinned   = $this->model->pinCampaign($this->dataset['campaign']);
		$unpinned = $this->model->unpinCampaign($this->dataset['campaign']);

		$this->assertTrue($unpinned, 'La campagne existe, on peut la retirer de la mise en avant');

		$campaign = $this->model->getCampaignByID($this->dataset['campaign']);
		$this->assertSame(0, (int) $campaign['pinned'], 'La campagne n\'est plus mise en avant');

		$this->assertFalse($this->model->unpinCampaign(['svsfg', 'dsgdfg', 'dsg']), 'Un tableau mal formé ne peut pas être passé en paramètre');
	}

	/**
	 * @covers EmundusModelCampaign::editDocumentDropfile
	 *
	 * @since version 1.0.0
	 */
	function testeditDocumentDropfile()
	{
		$query = $this->db->getQuery(true);

		$query->insert($this->db->quoteName('#__dropfiles_files'))
			->columns($this->db->quoteName(['title', 'ext', 'file', 'state', 'catid', 'ordering', 'size']))
			->values("'test', 'pdf', 'test.pdf', 1, 0, 0, 0");

		$this->db->setQuery($query);
		$this->db->execute();
		$document_id = $this->db->insertid();

		$updated = $this->model->editDocumentDropfile($document_id, '');
		$this->assertFalse($updated, 'Le nom du document ne peut pas être vide');

		$too_long_name = 'testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttesttest';
		$updated       = $this->model->editDocumentDropfile($document_id, $too_long_name);

		$this->assertTrue($updated, 'Le nom du document a été mis à jour');

		$updated_document = $this->model->getDropfileDocument($document_id);
		$this->assertSame(200, strlen($updated_document->title), 'Le nom du document a été tronqué à 200 caractères');

		// Clear datasets
		$query->clear()
			->delete($this->db->quoteName('#__dropfiles_files'))
			->where($this->db->quoteName('id') . ' = ' . $document_id);
		$this->db->setQuery($query);
		$this->db->execute();
		//
	}

	/**
	 * @covers EmundusModelCampaign::duplicateCampaign
	 *
	 * @since version 1.0.0
	 */
	function testduplicateCampaign()
	{
		$duplicated = $this->model->duplicateCampaign($this->dataset['campaign']);
		$this->assertTrue($duplicated, 'La campagne a bien été dupliquée');

		$duplicated = $this->model->duplicateCampaign(0);
		$this->assertFalse($duplicated, 'La campagne 0 n\'existe pas, donc on ne peut pas la dupliquer');
	}

	function testGetCampaignUserCategoriesValues()
	{
		$values = $this->model->getCampaignUserCategoriesValues(0);
		$this->assertEmpty($values, 'Aucun valeur de catégorie utilisateur pour une campagne inexistante');

		$values = $this->model->getCampaignUserCategoriesValues($this->dataset['campaign']);
		$this->assertIsArray($values, 'Récupération des valeurs de catégorie utilisateur pour une campagne existante');
	}
}