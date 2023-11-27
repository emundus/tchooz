<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use EmundusHelperCache;
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_BASE . '/components/com_emundus/helpers/cache.php';

class SettingsModelTest extends UnitTestCase
{
	private $config;
	
	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct('settings', $data, $dataName, 'EmundusModelSettings');

		$this->config = Factory::getConfig();
		$this->config->set('cache_handler', 'file');
	}

	/**
	 * @return void
	 * @covers EmundusModelSettings::getOnboardingLists
	 */
	public function testgetOnboardingLists()
	{
		$this->config->set('caching', 1);

		$lists = $this->model->getOnboardingLists();
		$this->assertNotEmpty($lists);

		// lists should contain at least 3 entries (campaigns, forms and emails)
		$this->assertGreaterThanOrEqual(3, count($lists));

		// if cache is enabled, lists should be cached
		$h_cache      = new EmundusHelperCache();
		$lists_cached = $h_cache->get('onboarding_lists');
		$this->assertNotEmpty($lists_cached);
		$this->assertSame($lists, $lists_cached);
	}

	public function testgetStatus()
	{
		$all_status = $this->model->getStatus();

		$this->assertIsArray($all_status);
		$this->assertNotEmpty($all_status, 'La récupération des status fonctionne');
	}

	public function testcreateStatus()
	{
		$status = $this->model->createStatus();
		$this->assertNotNull($status, 'La création d\'un status fonctionne');

		$this->assertGreaterThan(0, $status->id, 'La création d\'un status fonctionne');
	}

	public function testgetTags()
	{
		$all_tags = $this->model->getTags();

		$this->assertIsArray($all_tags);
		$this->assertNotEmpty($all_tags, 'La récupération des étiquettes fonctionne');
	}

	public function testcreateTag()
	{
		$tag = $this->model->createTag();
		$this->assertNotNull($tag, 'La création d\'une étiquette fonctionne');

		$this->assertGreaterThan(0, $tag->id, 'La création d\'une étiquette fonctionne');
		$this->assertSame($tag->label, 'Nouvelle étiquette', 'Le tag a un titre par défaut');
	}

	public function testupdateTags()
	{
		$tag   = $this->model->createTag();
		$label = 'Nouvelle étiquette modifiée';

		$update = $this->model->updateTags($tag->id, $label, 'lightblue');
		$this->assertTrue($update, 'La modification d\'une étiquette fonctionne');

		$tags       = $this->model->getTags();
		$tags_found = array_filter($tags, function ($t) use ($tag) {
			return $t->id == $tag->id;
		});
		$tag_found  = current($tags_found);

		$this->assertSame($label, $tag_found->label, 'Le titre de l\'étiquette a été modifié');
		$this->assertSame('label-lightblue', $tag_found->class, 'Le titre de l\'étiquette a été modifié');
	}

	public function testdeleteTag()
	{
		$tag    = $this->model->createTag();
		$delete = $this->model->deleteTag($tag->id);
		$this->assertTrue($delete, 'La suppression d\'une étiquette fonctionne');

		$delete = $this->model->deleteTag(0);
		$this->assertFalse($delete, 'On ne peut pas supprimer une étiquette qui n\'existe pas');
	}

	public function testgetHomeArticle()
	{
		$article = $this->model->getHomeArticle();

		$this->assertNotNull($article, 'La récupération de l\'article d\'accueil fonctionne');
	}

	public function testgetRgpdArticles()
	{
		$articles = $this->model->getRgpdArticles();

		$this->assertNotEmpty($articles, 'La récupération des articles RGPD fonctionne');

		$this->assertSame(4, count($articles), 'Je récupère 4 articles RGPD. (Cookies, mentions légales, politique de confidentialité et conditions générales d\'utilisation et Gestion des droits)');

		if (empty($articles[0]->id)) {
			$this->assertNotEmpty($articles[0]->alias, 'Si le paramètre du module n\'est pas défini on récupère un alias par défaut');
		}
	}

	public function testpublishArticle()
	{
		$articles = $this->model->getRgpdArticles();

		foreach ($articles as $article) {
			if (empty($article->id)) {
				$publish = $this->model->publishArticle(0, $article->alias);
				$this->assertTrue($publish, 'La dépublication d\'un article RGPD fonctionne');
			}
			else {
				$publish = $this->model->publishArticle(0, $article->id);
				$this->assertTrue($publish, 'La dépublication d\'un article RGPD fonctionne');
			}
		}
	}
}