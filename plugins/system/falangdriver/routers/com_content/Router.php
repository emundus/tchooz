<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Falang\Component\Content\Site\Service;

\defined('_JEXEC') or die;


use FalangManager;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Component\Content\Site\Service\Router;
use Joomla\Database\DatabaseInterface;

/**
 * Routing class of com_content
 *
 * @since  3.3
 */
class FalangRouter extends Router
{
    /**
     * The category factory
     *
     * @var CategoryFactoryInterface
     *
     * @since  4.0.0
     */
    private $categoryFactory;

    /**
     * The category cache
     *
     * @var  array
     *
     * @since  4.0.0
     */
    private $categoryCache = [];


    public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
    {
        parent::__construct($app,$menu,$categoryFactory,$db);

        $this->categoryFactory = $categoryFactory;

    }

    /**
     * Method to get the segment(s) for a category
     *
     * @param   string  $id     ID of the category to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getCategorySegment($id, $query)
    {
        $category = $this->getCategories(['access' => true])->get($id,true);

        if ($category) {
            $path    = array_reverse($category->getPath(), true);
            $path[0] = '1:root';

            if ($this->noIDs) {
                foreach ($path as &$segment) {
                    list($id, $segment) = explode(':', $segment, 2);
                }
            }

            return $path;
        }

        return [];
    }

    /**
     * Method to get categories from cache
     *
     * @param   array  $options   The options for retrieving categories
     *
     * @return  CategoryInterface  The object containing categories
     *
     * @since   4.0.0
     */
    private function getCategories(array $options = []): CategoryInterface
    {
        $key = serialize($options);

        if (!isset($this->categoryCache[$key])) {
            $this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
        }

        return $this->categoryCache[$key];
    }

    public function getArticleSegment($id, $query)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        if (!strpos($id, ':'))
        {
            $dbquery = $db->getQuery(true);
            //add id in the query to use falang override query
            $dbquery->select($dbquery->qn(array('alias', 'id')))
                ->from($dbquery->qn('#__content'))
                ->where('id = ' . $dbquery->q($id));
            $db->setQuery($dbquery);
            $id .= ':' . $db->loadResult();
        }

        if ($this->noIDs)
        {
            list($void, $segment) = explode(':', $id, 2);

            return array($void => $segment);
        }

        return array((int) $id => $id);
    }






	/**
	 * Method to get the segment(s) for an article
     *
     * @update 5.9 fix bug 2 articles with the same alias in 2 categories
     *             path on second article was not found => 404
	 *
	 * @param   string  $segment  Segment of the article to retrieve the ID for
	 * @param   array   $query    The request that is parsed right now
	 *
	 * @return  mixed   The id of this item or false
	 */
    public function getArticleId($segment, $query)
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if ($this->noIDs)
        {
            $lang         = Factory::getLanguage()->getTag();
            $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

            //look in Falang Table
            if ($default_lang != $lang)
            {
                $fManager = FalangManager::getInstance();
                $id_lang  = $fManager->getLanguageID($lang);

                $dbQuery = $db->getQuery(true);
                $dbQuery->select('fc.reference_id')
                    ->from('#__falang_content fc')
                    ->where('fc.value = ' . $dbQuery->q($segment))
                    ->where('fc.language_id = ' . $dbQuery->q($id_lang))
                    ->where('fc.reference_field = ' . $dbQuery->q('alias'))
                    ->where('fc.published = 1')
                    ->where('fc.reference_table = ' . $dbQuery->q('content'));

                $db->setQuery($dbQuery);
                $db->execute();
                $num_rows = $db->getNumRows();
                //case no alias translated by falang need to find the content alias
                //TODO filter by catid too in case
                if (empty($num_rows)){
                    //get category
                    if (isset($query['view']) && $query['view'] == 'category'){
                        $cat_id = $query['id'];
                    }

                    $dbQuery = $db->getQuery(true);
                    $dbQuery->select('c.id');
                    $dbQuery->from('#__content c');
                    $dbQuery->where('c.alias = ' . $dbQuery->q($segment));

                    if (isset($cat_id)){
                        $dbQuery->where('c.catid = ' . $dbQuery->q($cat_id));
                    }

                    $db->setQuery($dbQuery);
                    $db->execute();
                    $article_id = $db->loadResult();
                    return (int) $article_id;
                }
                //most case only 1 alias
                if (isset($num_rows) && $num_rows == 1){
                    $article_id = $db->loadResult();
                    if (isset($article_id))
                    {
                        return (int) $article_id;
                    }
                } else {
                    $article_ids = $db->loadObjectList();
                    //2 alias with the same name look the right one
                    foreach ($article_ids as $id){
                        $dbquery = $db->getQuery(true);
                        $dbquery->select($dbquery->qn('id'))
                            ->from($dbquery->qn('#__content'))
                            ->where('id = ' . $dbquery->q($id->reference_id))
                            ->where('catid = ' . $dbquery->q($query['id']));
                        $db->setQuery($dbquery);
                        $db->execute();
                        $num_rows = $db->getNumRows();
                        if ($num_rows != 0){
                            return (int) $db->loadResult();
                        }
                    }
                }
            } else	{
                $dbquery = $db->getQuery(true);
                $dbquery->select($dbquery->qn('id'))
                    ->from($dbquery->qn('#__content'))
                    ->where('alias = ' . $dbquery->q($segment))
                    ->where('catid = ' . $dbquery->q($query['id']));
                $db->setQuery($dbquery);

                return (int) $db->loadResult();
            }
        }

        return (int) $segment;
    }
}
