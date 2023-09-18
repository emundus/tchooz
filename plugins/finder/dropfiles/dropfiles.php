<?php
/**
 * Dropfiles
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Dropfiles
 * @copyright Copyright (C) 2013 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2013 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Source code from Joomla's content search plugin
 */

//-- No direct access
defined('_JEXEC') || die('=;)');

JLoader::register(
    'Joomla\Component\Dropfiles\Site\Helper\RouteHelper',
    JPATH_ROOT . '/components/com_dropfiles/helpers/RouteHelper.php'
);

if (!class_exists('DropfilesFilesHelper')) {
    require_once JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/files.php';
}

if (!class_exists('DropfilesModelFrontfile')) {
    require_once JPATH_ROOT . '/components/com_dropfiles/models/frontfile.php';
}

if (!class_exists('DropfilesModelFrontcategory')) {
    require_once JPATH_ROOT . '/components/com_dropfiles/models/frontcategory.php';
}

use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Component\Dropfiles\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;

/**
 * Finder adapter for Dropfiles.
 */
class PlgFinderDropfiles extends Adapter
{
    /**
     * The plugin identifier.
     *
     * @var string
     */
    protected $context = 'Dropfiles';

    /**
     * The extension name.
     *
     * @var string
     */
    protected $extension = 'com_dropfiles';

    /**
     * The sublayout to use when rendering the results.
     *
     * @var string
     */
    protected $layout = 'dropfiles';

    /**
     * The type of content that the adapter indexes.
     *
     * @var string
     */
    protected $type_title = 'Dropfiles';

    /**
     * The table name.
     *
     * @var string
     */
    protected $table = '#__dropfiles_files';

    /**
     * The field the published state is stored in.
     *
     * @var string
     */
    protected $state_field = 'published';

    /**
     * Load the language file on instantiation.
     *
     * @var boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Method to setup the indexer to be run.
     *
     * @return boolean  True on success.
     */
    protected function setup()
    {
        return true;
    }
    /**
     * Method to update the item link information when the item category is
     * changed. This is fired when the item category is published or unpublished
     * from the list view.
     *
     * @param string  $extension The extension whose category has been updated.
     * @param array   $pks       A list of primary key ids of the content that has changed state.
     * @param integer $value     The value of the state that the content has been changed to.
     *
     * @return void
     */
    public function onFinderCategoryChangeState($extension, $pks, $value)
    {
        // Make sure we're handling com_dropfiles categories
        if ($extension === 'com_dropfiles') {
            $this->categoryStateChange($pks, $value);
        }
    }

    /**
     * Method to remove the link information for items that have been deleted.
     *
     * This event will fire when dropfiles are deleted and when an indexed item is deleted.
     *
     * @param string $context The context of the action being performed.
     * @param Table  $table   A Table object containing the record to be deleted
     *
     * @return void
     *
     * @throws Exception On database error.
     */
    public function onFinderAfterDelete($context, $table): void // phpcs:ignore PHPCompatibility.FunctionDeclarations.NewReturnTypeDeclarations.voidFound -- For reasons.
    {
        if ($context === 'com_dropfiles.file') {
            $id = $table->id;
        } elseif ($context === 'com_finder.index') {
            $id = $table->link_id;
        } else {
            return;
        }

        // Remove the items.
        $this->remove($id);
    }

    /**
     * Method to update the link information for items that have been changed
     * from outside the edit screen. This is fired when the item is published,
     * unpublished, archived, or unarchived from the list view.
     *
     * @param string  $context The context for the content passed to the plugin.
     * @param array   $pks     A list of primary key ids of the content that has changed state.
     * @param integer $value   The value of the state that the content has been changed to.
     *
     * @return void
     */
    public function onFinderChangeState($context, $pks, $value)
    {
        // We only want to handle dropfiles here
        if ($context === 'com_dropfiles.file') {
            $this->itemStateChange($pks, $value);
        }

        // Handle when the plugin is disabled
        if ($context === 'com_plugins.plugin' && $value === 0) {
            $this->pluginDisable($pks);
        }
    }

    /**
     * Method to index an item. The item must be a Result object.
     *
     * @param Result $item The item to index as a Result object.
     *
     * @return void
     *
     * @throws Exception On database error.
     */
    protected function index(Result $item)
    {
        // Check if the extension is enabled
        if (ComponentHelper::isEnabled($this->extension) === false) {
            return;
        }

        $item->setLanguage();
        $item->context = 'com_dropfiles.file';

        // Initialize the item parameters.
        $registry = new Registry($item->params);
        $item->params = clone ComponentHelper::getParams('com_dropfiles', true);
        $item->params->merge($registry);

        $item->metadata = new Registry($item->metadata);

        // Trigger the onContentPrepare event.
        $item->summary = Helper::prepareContent($item->summary, $item->params, $item);
        $item->body    = Helper::prepareContent($item->body, $item->params, $item);

        // Create a URL as identifier to recognise items again.
        $item->url = $this->getUrl($item->id, $this->extension, $this->layout);
        $item->access = 1;

        // Get the menu title if it exists.
        $title = $this->getItemMenuTitle($item->url);

        // Adjust the title if necessary.
        if (!empty($title) && $this->params->get('use_menu_title', true)) {
            $item->title = $title;
        }

        $helper = new DropfilesFilesHelper();
        $modelCategory = new DropfilesModelFrontcategory();
        $modelFile = new DropfilesModelFrontfile();
        $category = $modelCategory->getCategory($item->catid);
        $file = $modelFile->getFile($item->id);

        // Build the necessary route and path information.
        $item->route = $helper->genUrl($item->id, $item->catid, $category->title, '', $file->title . '.' . $file->ext);

        // Add the meta author.
        $item->metaauthor = $item->metadata->get('author');

        // Handle the link to the metadata.
        $item->addInstruction(Indexer::META_CONTEXT, 'title');
        $item->addInstruction(Indexer::META_CONTEXT, 'summary');
        $item->addInstruction(Indexer::META_CONTEXT, 'author');
        $item->addInstruction(Indexer::META_CONTEXT, 'category');

        $item->state = $this->translateState($item->state, $item->cat_state);

        // Add the type taxonomy data.
        $item->addTaxonomy('Type', 'Dropfiles');

        // Add categories
        $categories = Categories::getInstance('com_dropfiles', ['published' => false, 'access' => false ]); // phpcs:ignore PHPCompatibility.Syntax.NewShortArray.Found -- For reasons.
        $category = $categories->get($item->catid);

        if (!$category) {
            return;
        }

        $item->addNestedTaxonomy('Category', $category, $this->translateState($category->published), $category->access, $category->language);

        // Get content extras.
        Helper::getContentExtras($item);

        // Index the item.
        $this->indexer->index($item);
    }

    /**
     * Method to get the SQL query used to retrieve the list of content items.
     *
     * @param mixed $query A DatabaseQuery object or null.
     *
     * @return DatabaseQuery  A database object.
     */
    protected function getListQuery($query = null)
    {
        $db = $this->db;

        // Check if we can use the supplied SQL query.
        $query = $query instanceof DatabaseQuery ? $query : $db->getQuery(true)
            ->select('f.id, f.title, f.description as summary')
            ->select('f.created_time AS start_date')
            ->select('f.catid, f.state, f.ext, f.file, f.language, f.author')
            ->select('f.modified_time AS modified')
            ->select('f.publish AS publish_start_date, f.publish_down AS publish_end_date')
            ->select('c.title AS category, c.published AS cat_state, c.access AS cat_access')
            ->from('#__dropfiles_files AS f')

            ->join('LEFT', '#__categories AS c ON c.id = f.catid')

            // Exclude the ROOT item
            ->where($db->quoteName('f.id') . ' > 1');

        return $query;
    }
}
