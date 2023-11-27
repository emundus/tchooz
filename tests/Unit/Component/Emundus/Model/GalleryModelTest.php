<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\Tests\Unit\UnitTestCase;

class GalleryModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('gallery', $data, $dataName, 'EmundusModelGallery');
	}

	public function testCreateGallery()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];

		// 1. En tant que gestionnaire je peux créer un catalogue
		$gallery_id = $this->model->createGallery($data);
		$this->assertNotEquals(0,$gallery_id);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 2. En tant que développeur je veux qu'une vue SQL soit créée pour chaque catalogue
		$query = 'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = "' . $this->app->get('db') . '" AND `TABLE_TYPE` = "VIEW"';
		$this->db->setQuery($query);
		$views = $this->db->loadColumn();
		$this->assertContains('jos_emundus_gallery_' . $gallery->list_id, $views);

		// 3. En tant que développeur je veux m'assurer que la liste Fabrik a bien été créee
		$query = $this->db->getQuery(true);
		$query
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__fabrik_lists'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($gallery->list_id));
		$this->db->setQuery($query);
		$this->assertNotEmpty($this->db->loadResult());

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testDeleteGallery()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 1. En tant que gestionnaire je veux supprimer un catalogue
		$this->assertTrue($this->model->deleteGallery($gallery->id));

		// 2. En tant que développeur je veux m'assurer que la vue SQL a bien été supprimée
		$query = 'SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = "' . $this->app->get('db') . '" AND `TABLE_TYPE` = "VIEW"';
		$this->db->setQuery($query);
		$views = $this->db->loadColumn();
		$this->assertNotContains('jos_emundus_gallery_' . $gallery->list_id, $views);

		// 3. En tant que développeur je veux m'assurer que la liste Fabrik a bien été supprimée
		$query = $this->db->getQuery(true);
		$query
			->select($this->db->quoteName('id'))
			->from($this->db->quoteName('#__fabrik_lists'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($gallery->list_id));
		$this->db->setQuery($query);
		$this->assertEmpty($this->db->loadResult());

		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetGalleries()
	{
		// 1. Au déploiement, la liste des catalogues est vide
		$this->assertEmpty($this->model->getGalleries()['datas']);

		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		// 2. En tant que gestionnaire je veux pouvoir récupérer la liste des catalogues
		$this->assertNotEmpty($this->model->getGalleries()['datas']);

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetGalleryById()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		// 1. En tant que gestionnaire je veux accéder aux détails d'un catalogue
		$gallery = $this->model->getGalleryById($gallery_id);
		$this->assertEquals($gallery_id, $gallery->id);

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetGalleryByList()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 1. En tant que développeur je veux pouvoir récupérer les données d'un catalogue via l'identifiant de la liste Fabrik
		$gallery = $this->model->getGalleryByList($gallery->list_id);
		$this->assertEquals($gallery_id, $gallery->id);

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetElements()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 1. En tant que gestionnaire je veux pouvoir visualiser les élements de formulaire que je peux associer
		$elements = $this->model->getElements($gallery->campaign_id,$gallery->list_id);
		$this->assertNotEmpty($elements);

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testGetAttachments()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 1. En tant que gestionnaire je veux pouvoir visualiser les documents que je peux associer
		$attachments = $this->model->getAttachments($gallery->campaign_id);
		$this->assertNotEmpty($attachments);

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testUpdateAttribute()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		$elements = $this->model->getElements($gallery->campaign_id,$gallery->list_id);

		// 1. En tant que gestionnaire je veux pouvoir associer un élément au titre des vignettes de mon catalogue
		$this->assertTrue($this->model->updateAttribute($gallery->id, 'title', $elements[0]['elements'][0]->fullname));

		// 2. Par défaut mon catalogue n'est pas ouvert au vote
		$this->assertEquals(0,$gallery->is_voting);

		// 3. En tant que gestionnaire je veux ouvrir mon catalogue au vote
		$this->assertTrue($this->model->updateAttribute($gallery->id, 'is_voting', 1));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testUpdateList()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 1. En tant que gestionnaire je veux modifier le nom de mon catalogue
		$this->assertTrue($this->model->updateList($gallery->list_id, 'label', 'Catalogue de test 2'));

		// 2. En tant que développeur je ne veux pas qu'il puisse modifier un autre attribut que label et introduction
		$this->assertFalse($this->model->updateList($gallery->list_id, 'params', '{}'));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testEditPrefilter()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 1. En tant que gestionnaire je veux afficher seulement les dossiers au statut envoyé sur le catalogue
		$this->assertTrue($this->model->editPrefilter($gallery->list_id, 1));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testAddTab()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		// 1. En tant que gestionnaire je veux Ajouter un onglet dans la vue détails de mon catalogue
		$tab_id = $this->model->addTab($gallery->id, 'Onglet N°2');
		$this->assertNotEmpty($tab_id);

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testAddField()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		$elements = $this->model->getElements($gallery->campaign_id,$gallery->list_id);

		$tab_id = $this->model->addTab($gallery->id, 'Onglet N°2');

		// 1. En tant que gestionnaire je veux ajouter un élément dans l'onglet de mon catalogue
		$this->assertTrue($this->model->addField($tab_id, $elements[0]['elements'][0]->fullname));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testRemoveField()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);

		$elements = $this->model->getElements($gallery->campaign_id,$gallery->list_id);

		$tab_id = $this->model->addTab($gallery->id, 'Onglet N°2');
		$this->model->addField($tab_id, $elements[0]['elements'][0]->fullname);

		// 1. En tant que gestionnaire je veux ajouter un élément dans l'onglet de mon catalogue
		$this->assertTrue($this->model->removeField($tab_id, $elements[0]['elements'][0]->fullname));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testUpdateTabTitle()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);


		$tab_id = $this->model->addTab($gallery->id, 'Onglet N°2');

		// 1. En tant que gestionnaire je veux modifier le titre d'un onglet de mon catalogue
		$this->assertTrue($this->model->updateTabTitle($tab_id, 'Onglet N°3'));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testDeleteTab()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];
		$gallery_id = $this->model->createGallery($data);

		$gallery = $this->model->getGalleryById($gallery_id);
		$tab_id = $this->model->addTab($gallery->id, 'Onglet N°2');

		// 1. En tant que gestionnaire je veux supprimer un onglet de mon catalogue
		$this->assertTrue($this->model->deleteTab($tab_id));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}

	public function testUpdateFieldsOrder()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign = $this->h_dataset->createSampleCampaign($program);

		$data = [
			'gallery_name' => 'Catalogue de test',
			'campaign_id' => $campaign
		];

		$gallery_id = $this->model->createGallery($data);
		$gallery = $this->model->getGalleryById($gallery_id);

		$tab_id = $this->model->addTab($gallery->id, 'Onglet N°2');

		$elements = $this->model->getElements($gallery->campaign_id,$gallery->list_id);

		$this->model->addField($tab_id, $elements[0]['elements'][0]->fullname);
		$this->model->addField($tab_id, $elements[0]['elements'][1]->fullname);

		// 1. En tant que gestionnaire je veux modifier l'ordre des éléments dans un onglet de mon catalogue
		$this->assertTrue($this->model->updateFieldsOrder($tab_id, implode(',',[$elements[0]['elements'][1]->fullname, $elements[0]['elements'][0]->fullname])));

		$this->model->deleteGallery($gallery_id);
		$this->h_dataset->deleteSampleCampaign($campaign);
		$this->h_dataset->deleteSampleProgram($program['programme_id']);
	}
}