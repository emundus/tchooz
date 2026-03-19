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
use Tchooz\Entities\Upload\UploadEntity;

require_once JPATH_BASE . '/components/com_emundus/helpers/cache.php';

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelSettings
 */
class SettingsModelTest extends UnitTestCase
{
	private $config;
	
	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct('settings', $data, $dataName, 'EmundusModelSettings');

		$this->config = Factory::getApplication()->getConfig();
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

	/**
	 * @covers EmundusModelSettings::getStatus
	 *
	 * @since version 1.0.0
	 */
	public function testgetStatus()
	{
		$all_status = $this->model->getStatus();

		$this->assertIsArray($all_status);
		$this->assertNotEmpty($all_status, 'La récupération des status fonctionne');
	}

	/**
	 * @covers EmundusModelSettings::createStatus
	 *
	 * @since version 1.0.0
	 */
	public function testcreateStatus()
	{
		$status = $this->model->createStatus();
		$this->assertNotNull($status, 'La création d\'un status fonctionne');

		$this->assertGreaterThan(0, $status->id, 'La création d\'un status fonctionne');
	}

	/**
	 * @covers EmundusModelSettings::getTags
	 *
	 * @since version 1.0.0
	 */
	public function testgetTags()
	{
		$all_tags = $this->model->getTags();

		$this->assertIsArray($all_tags);
		$this->assertNotEmpty($all_tags, 'La récupération des étiquettes fonctionne');
	}

	/**
	 * @covers EmundusModelSettings::createTag
	 *
	 * @since version 1.0.0
	 */
	public function testcreateTag()
	{
		$tag = $this->model->createTag();
		$this->assertNotNull($tag, 'La création d\'une étiquette fonctionne');

		$this->assertGreaterThan(0, $tag->id, 'La création d\'une étiquette fonctionne');
		$this->assertSame($tag->label, 'Nouvelle étiquette', 'Le tag a un titre par défaut');
	}

	/**
	 * @covers EmundusModelSettings::updateTags
	 *
	 * @since version 1.0.0
	 */
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

	/**
	 * @covers EmundusModelSettings::deleteTag
	 *
	 * @since version 1.0.0
	 */
	public function testdeleteTag()
	{
		$tag    = $this->model->createTag();
		$delete = $this->model->deleteTag($tag->id);
		$this->assertTrue($delete, 'La suppression d\'une étiquette fonctionne');

		$delete = $this->model->deleteTag(0);
		$this->assertFalse($delete, 'On ne peut pas supprimer une étiquette qui n\'existe pas');
	}

	/**
	 * @covers EmundusModelSettings::getHomeArticle
	 *
	 * @since version 1.0.0
	 */
	public function testgetHomeArticle()
	{
		$article = $this->model->getHomeArticle();

		$this->assertNotNull($article, 'La récupération de l\'article d\'accueil fonctionne');
	}

	/**
	 * @covers EmundusModelSettings::getRgpdArticles
	 *
	 * @since version 1.0.0
	 */
	public function testgetRgpdArticles()
	{
		$articles = $this->model->getRgpdArticles();

		$this->assertNotEmpty($articles, 'La récupération des articles RGPD fonctionne');

		$this->assertSame(5, count($articles), 'Je récupère 4 articles RGPD. (Cookies, mentions légales, politique de confidentialité et conditions générales d\'utilisation et Gestion des droits et Accessibilité)');

		if (empty($articles[0]->id)) {
			$this->assertNotEmpty($articles[0]->alias, 'Si le paramètre du module n\'est pas défini on récupère un alias par défaut');
		}
	}

	/**
	 * @covers EmundusModelSettings::publishArticle
	 *
	 * @since version 1.0.0
	 */
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

	public function testSwitchUsercategoryElement()
	{
		$result = $this->model->switchUsercategoryElement(true);
		$this->assertTrue($result);

		$result = $this->model->switchUsercategoryElement(false);
		$this->assertTrue($result);
	}

	public function testEnableDisableUserCategoryPlugin()
	{
		$query = $this->db->getQuery(true);

		$result = $this->model->enableDisableUserCategoryPlugin(true);
		$this->assertTrue($result);

		$query->select('enabled')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('emundus_user_category'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
		$this->db->setQuery($query);
		$state = $this->db->loadResult();

		$this->assertSame(1, $state, 'L\'activation du plugin Emundus - User Type fonctionne');

		$result = $this->model->enableDisableUserCategoryPlugin(false);
		$this->assertTrue($result);

		$query->clear()
			->select('enabled')
			->from($this->db->quoteName('#__extensions'))
			->where($this->db->quoteName('element') . ' = ' . $this->db->quote('emundus_user_category'))
			->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('system'));
		$this->db->setQuery($query);
		$state = $this->db->loadResult();

		$this->assertSame(0, $state, 'La désactivation du plugin Emundus - User Type fonctionne');
	}

	/**
	 * @covers EmundusModelSettings::getFileThumbnail
	 */
	public function testGetFileThumbnail(): void
	{
		$assetPath =  JPATH_BASE . '/tests/assets/pdf_test_file.pdf';
		$this->assertFileExists($assetPath, 'Test PDF asset should exist');

		$userId   = $this->dataset['applicant'];
		$filename = 'pdf_test_file.pdf';

		$targetDir  = JPATH_SITE . '/images/emundus/files/' . $userId;
		$targetPath = $targetDir . '/' . $filename;

		$upload = new UploadEntity(
			0,
			$userId,
			$this->dataset['fnum'],
			$this->h_dataset->createSampleAttachment(),
			$filename,
			'Test file description',
			'local_testfile.pdf'
		);

		$this->assertFileDoesNotExist($targetPath, 'Target test file already exists; refusing to overwrite');

		if (!is_dir($targetDir))
		{
			$this->assertTrue(mkdir($targetDir), 'Failed to create target directory for test file');
		}

		$this->assertTrue(copy($assetPath, $targetPath), 'Failed to copy asset PDF into internal path');

		try
		{
			$thumb = $this->model->getFileThumbnail($upload, 1);

			$this->assertIsString($thumb);
			$this->assertNotEmpty($thumb, 'Thumbnail base64 should not be empty');

			$decoded = base64_decode($thumb, true);
			$this->assertNotFalse($decoded, 'Thumbnail should be valid base64');
		}
		finally
		{
			if (is_file($targetPath))
			{
				unlink($targetPath);
			}

			if (is_dir($targetDir))
			{
				$files = array_values(array_diff(scandir($targetDir) ?: [], ['.', '..']));
				if (count($files) === 0)
				{
					rmdir($targetDir);
				}
			}
		}
	}

	/**
	 * @covers EmundusModelSettings::getFileInfosFromUploadEntity
	 */
	public function testGetFileInfosFromUploadEntity(): void
	{
		$assetPath = JPATH_BASE . '/tests/assets/pdf_test_file.pdf';
		$this->assertFileExists($assetPath);

		$userId   = $this->dataset['applicant'];
		$filename = 'pdf_test_file.pdf';

		$targetDir  = JPATH_SITE . '/images/emundus/files/' . $userId;
		$targetPath = $targetDir . '/' . $filename;

		$upload = new UploadEntity(
			0,
			$userId,
			$this->dataset['fnum'],
			$this->h_dataset->createSampleAttachment(),
			$filename,
			'Test file description',
			'local_testfile.pdf'
		);

		$upload->setThumbnail(base64_encode('dummy-thumb'));

		$this->assertFileDoesNotExist($targetPath);

		if (!is_dir($targetDir))
		{
			$this->assertTrue(mkdir($targetDir), 'Failed to create target directory for test file');
		}
		$this->assertTrue(copy($assetPath, $targetPath));

		try
		{
			$infos = $this->model->getFileInfosFromUploadEntity($upload);

			$this->assertIsArray($infos);
			$this->assertArrayHasKey('thumbnail', $infos);
			$this->assertArrayHasKey('pages_length', $infos);
			$this->assertArrayHasKey('text', $infos);

			$this->assertGreaterThanOrEqual(1, $infos['pages_length']);
			$this->assertIsString($infos['text']);
		}
		finally
		{
			if (is_file($targetPath))
			{
				unlink($targetPath);
			}
			if (is_dir($targetDir))
			{
				$files = array_values(array_diff(scandir($targetDir) ?: [], ['.', '..']));
				if (count($files) === 0)
				{
					rmdir($targetDir);
				}
			}
		}
	}
}