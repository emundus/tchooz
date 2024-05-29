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
use JLog;
use Joomla\CMS\Log\Log;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_SITE . '/components/com_emundus/models/programme.php';
require_once JPATH_SITE . '/components/com_emundus/models/profile.php';
require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';

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

	public function testCreateCampaign()
	{
		$new_campaign_id = $this->model->createCampaign([]);
		$this->assertEmpty($new_campaign_id, 'Assert can not create campaign without data');

		$new_campaign_id = $this->model->createCampaign(['limit_status' => 1, 'profile_id' => 9]);
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
			'profile_id'        => 9,
			'training'          => $this->dataset['program']['programme_code'],
			'year'              => '2022-2023',
			'published'         => 1,
			'is_limited'        => 0
		];

		$new_campaign_id = $this->model->createCampaign($inserting_datas);
		$this->assertGreaterThan(0, $new_campaign_id, 'Assert campaign creation works.');

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

		$deleted = $this->model->deleteCampaign([$new_campaign_id]);
		$this->assertTrue($deleted, 'Campaign deletion works properly');
	}

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
	}

	public function testGetAllCampaigns()
	{
		$campaigns = $this->model->getAllCampaigns();
		$this->assertIsArray($campaigns, 'La fonction de récupération des campagnes renvoie toujours un tableau');
	}

	public function testGetProgrammeByTraining()
	{
		$progam = $this->model->getProgrammeByTraining('');
		$this->assertEmpty($progam, 'Get programme by training without param returns null');
	}


	public function testCreateWorkflow()
	{
		$workflow_on_all = $this->model->createWorkflow(9, [0], 1, null, []);
		$this->assertNotEmpty($workflow_on_all, 'La création d\'un workflow devrait fonctionner');

		$this->assertFalse($this->model->canCreateWorkflow(9, [0], []), 'On ne devrait pas pouvoir créer un workflow sur le même statut.');
		$this->assertTrue($this->model->canCreateWorkflow(9, [1], []), 'On devrait pouvoir créer un workflow sur le même profile, mais un statut différent.');
		$this->assertTrue($this->model->canCreateWorkflow(9, [0], ['campaigns' => [1]]), 'On devrait pouvoir créer un workflow sur le même statut mais en spécifiant une campagne.');
		$this->assertTrue($this->model->canCreateWorkflow(9, [0], ['programs' => ['program-1']]), 'On devrait pouvoir créer un workflow sur le même statut mais en spécifiant une campagne.');

		// Datasets
		$workflow_on_program = $this->model->createWorkflow(9, [0], 1, null, ['programs' => [$this->dataset['program']['programme_code']]]);
		$this->assertNotEmpty($workflow_on_program);
		$this->assertFalse($this->model->canCreateWorkflow(9, [0], ['programs' => ['program-1', $this->dataset['program']['programme_code']]]), 'On ne devrait plus pouvoir créer un workflow sur le même statut et en spécifiant un progamme commun.');

		$new_campaign_id = $this->h_dataset->createSampleCampaign($this->dataset['program'], $this->dataset['coordinator']);

		if (!empty($new_campaign_id))
		{
			$this->assertTrue($this->model->canCreateWorkflow(9, [0], ['campaigns' => [$new_campaign_id]]), 'On devrait toujours pouvoir créer un workflow sur le même statut mais en spécifiant une campagne.');

			$workflow_on_campaign = $this->model->createWorkflow(9, [0], 1, null, ['campaigns' => [$new_campaign_id]]);
			$this->assertNotEmpty($workflow_on_campaign);
			$this->assertFalse($this->model->canCreateWorkflow(9, [0], ['campaigns' => [12, $new_campaign_id, 15]]), 'On ne devrait plus pouvoir créer un workflow sur le même statut-campagne.');
			$this->assertFalse($this->model->canCreateWorkflow(9, [0], ['programs' => ['test-emundus'], 'campaigns' => [$new_campaign_id]]), 'On ne devrait plus pouvoir créer un workflow sur le même statut-campagne. Même test avec des données de programme.');
		}
		else
		{
			Log::add('Warning, test canCreateWorkflow on campaign has not been launched', Log::WARNING, 'com_emundus.unittest');
		}

		// Clear datasets
		$this->model->deleteWorkflows();
		//
	}

	public function testDeleteWorkflow()
	{
		$this->assertTrue($this->model->deleteWorkflows(), 'La suppression de workflow fonctionne');
	}

	public function testGetCurrentCampaignWorkflow()
	{
		$query = $this->db->getQuery(true);

		$workflow_on_all       = $this->model->createWorkflow(9, [0], 1, null, []);
		$current_file_workflow = $this->model->getCurrentCampaignWorkflow($this->dataset['fnum']);
		$this->assertNotNull($current_file_workflow, 'La phase courante doit être non nulle.');
		$this->assertSame(intval($workflow_on_all), intval($current_file_workflow->id), 'Le dossier est impacté par le workflow qui n\'a ni campagne ni programme par défaut, mais est sur le même statut.');

		$workflow_on_program   = $this->model->createWorkflow(9, [0], 1, null, ['programs' => [$this->dataset['program']['programme_code']]]);
		$current_file_workflow = $this->model->getCurrentCampaignWorkflow($this->dataset['fnum']);
		$this->assertSame(intval($workflow_on_program), intval($current_file_workflow->id), 'Le dossier est impacté par le workflow qui a un programme et un statut commun.');

		$workflow_on_campaign  = $this->model->createWorkflow(9, [0], 1, null, ['campaigns' => [$this->dataset['campaign']]]);
		$current_file_workflow = $this->model->getCurrentCampaignWorkflow($this->dataset['fnum']);
		$this->assertSame(intval($workflow_on_campaign), intval($current_file_workflow->id), 'Le dossier est impacté par le workflow qui a une campagne et un statut commun.');

		$profile = $this->m_profile->getProfileByFnum($this->dataset['fnum']);
		$this->assertSame(intval($current_file_workflow->profile), intval($profile), 'La récupération de profile prend en compte le workflow');
		$profileByStatus = $this->m_profile->getProfileByStatus($this->dataset['fnum']);
		$this->assertSame(intval($current_file_workflow->profile), intval($profileByStatus['profile']));

		$new_workflow_id       = $this->model->createWorkflow(9, [1], 2, null, ['campaigns' => [$this->dataset['campaign']]]);
		$current_file_workflow = $this->model->getCurrentCampaignWorkflow($this->dataset['fnum']);
		$this->assertNotSame(intval($new_workflow_id), intval($current_file_workflow->id), 'Mon dossier au statut Brouillon n\'est pas impacté par la phase sur la même campagne mais sur le statut Envoyé');

		$query->clear()
			->update('#__emundus_campaign_candidature')
			->set('status = 1')
			->where('fnum LIKE ' . $this->db->quote($this->dataset['fnum']));

		$this->db->setQuery($query);
		$this->db->execute();

		$current_file_workflow = $this->model->getCurrentCampaignWorkflow($this->dataset['fnum'], 'Mon dossier au statut 1 récupère le workflow associé à sa campagne');
		$this->assertSame(intval($new_workflow_id), intval($current_file_workflow->id));

		$this->assertTrue($this->model->deleteWorkflows(), 'La suppression de workflow fonctionne');

		$this->assertObjectHasProperty('display_preliminary_documents', $current_file_workflow, 'Le workflow contient un attribut "Afficher les Documents à télécharger"');
		$this->assertSame(0, (int) $current_file_workflow->display_preliminary_documents, 'Le workflow contient un attribut "Afficher les Documents à télécharger" à 0 par défaut');

		$this->assertObjectHasProperty('specific_documents', $current_file_workflow, 'Le workflow contient un attribut "Documents spécifique"');
		$this->assertSame(0, (int) $current_file_workflow->specific_documents, 'Le workflow contient un attribut "Documents spécifique" à 0 par défaut');

		$this->assertObjectHasProperty('documents', $current_file_workflow, 'Le workflow contient des documents');
		$this->assertSame([], $current_file_workflow->documents, 'Le workflow contient un tableau vide par défaut');
	}

	function testGetAllCampaignWorkflows()
	{
		$this->assertEmpty($this->model->getAllCampaignWorkflows(0), 'Pas de workflow renvoyés si la campagne n\'existe pas.');

		$this->assertEmpty($this->model->getAllCampaignWorkflows($this->dataset['campaign']), 'Pas encore de workflow sur une nouvelle campagne, nouveau programme');

		$workflow_on_program = $this->model->createWorkflow(9, [0], 1, null, ['programs' => [$this->dataset['program']['programme_code']]]);
		$this->assertSame(1, sizeof($this->model->getAllCampaignWorkflows($this->dataset['campaign'])), 'getAllCampaignWorkflows renvoie 1 workflow à la création du workflow sur le programme de la campagne');

		$workflow_on_campaign_same_state = $this->model->createWorkflow(9, [0], 1, null, ['campaigns' => [$this->dataset['campaign']]]);
		$this->assertSame(1, sizeof($this->model->getAllCampaignWorkflows($this->dataset['campaign'])), 'getAllCampaignWorkflows renvoie 1 seul workflow à la création du workflow sur la campagne avec le même statut d\'entrée que le workflow précédent');

		$this->model->createWorkflow(9, [1], 1, null, ['programs' => [$this->dataset['program']['programme_code']]]);
		$this->assertSame(2, sizeof($this->model->getAllCampaignWorkflows($this->dataset['campaign'])));

		// Clear datasets
		$this->model->deleteWorkflows();
		//
	}

	function testpinCampaign()
	{
		$pinned = $this->model->pinCampaign(9999);
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

	function testduplicateCampaign()
	{
		$duplicated = $this->model->duplicateCampaign($this->dataset['campaign']);
		$this->assertTrue($duplicated, 'La campagne a bien été dupliquée');

		$duplicated = $this->model->duplicateCampaign(0);
		$this->assertFalse($duplicated, 'La campagne 0 n\'existe pas, donc on ne peut pas la dupliquer');
	}
}