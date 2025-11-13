<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;


use Falang\Component\Administrator\Table\FalangContentTable;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;


require_once JPATH_ROOT.'/administrator/components/com_falang/models/JFModel.php';

/**
 * This is the corresponding module for translation management
 * @package		Falang
 * @subpackage	Translate
 */
class FalangModelTranslate extends JFModel
{
	var $_modelName = 'translate';

	/**
	 * return the model name
	 */
	function getName() {
		return $this->_modelName;
	}

	/**
	 * Method to prepare the language list for the translation backend
	 * The method defines that all languages are being presented except the default language
	 * if defined in the config.
	 * @return array of languages
	 */
	function getLanguages() {
		$jfManager = FalangManager::getInstance();
		return $jfManager->getLanguages(false);
	}

	/*
	 * since 4.1.1
	 * */
    public function __construct($config = array(), MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'c.id',
                'id',
                'c.title',
                'title',
                'jfc.modified',
                'lastchanged',
                'jfc.value',
                'titleTranslation',
                'c.field_id',
                'c.badge_id',//hikshoap
                'c.banner_id',//hikshoap
                'c.category_id',
                'c.characteristic_id',//hikshoap
                'c.file_id',//hikshoap
                'c.filter_id',//hikshoap
                'c.payment_id',//hikshoap
                'c.product_id',//hikshoap
                'c.shipping_id',//hikshoap
                'c.widget_id',//hikshoap
                'c.zone_id',//hikshoap
            );
        }

        parent::__construct($config, $factory);
    }

    protected function populateState($ordering = null, $direction = null)
    {

        $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        // Populate data used by controller
        $app	= Factory::getApplication();
        $catid = $app->getUserStateFromRequest('selected_catid', 'catid', '');

        //get Translation filter from content element
        if (!empty($catid) ) {
            $falangManager = FalangManager::getInstance();
            $contentElement = $falangManager->getContentElement( $catid );
            if (!$contentElement){
                $catid = "content";
                $contentElement = $falangManager->getContentElement( $catid );
            }

            JLoader::import('models.TranslationFilter',FALANG_ADMINPATH);
            $tranFilters = getTranslationFilters($catid,$contentElement);
            foreach ($tranFilters as $tranFilter){
                $filter = $this->getUserStateFromRequest('filter.'.$tranFilter->filterType, $tranFilter->filterType.'_filter_value',$tranFilter->filterNullValue);
                $this->setState('filter.'.$tranFilter->filterType, $filter);
            }
        }

        // List state information.
        parent::populateState($ordering, $direction);

    }
	/**
	 * Deletes the selected translations (only the translations of course)
	 * @return string	message
     *
     * @update 5.16 use FalangContentTable
	 */
	function _removeTranslation( $catid, $cid ) {
		$message = '';
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		foreach( $cid as $cid_row ) {
			list($translationid, $contentid, $language_id) = explode('|', $cid_row);

			$jfManager = FalangManager::getInstance();
			$contentElement = $jfManager->getContentElement( $catid );
			$contentTable = $contentElement->getTableName();
			$contentid= intval($contentid);
			$translationid = intval($translationid);

			// safety check -- complete overkill but better to be safe than sorry

			// get the translation details
            $translation = new FalangContentTable($db);
			$translation->load($translationid);

			if (!isset($translation) || $translation->id == 0)		{
				$this->setState('message', Text::sprintf('NO_SUCH_TRANSLATION', $translationid));
				continue;
			}

			// make sure translation matches the one we wanted
			if ($contentid != $translation->reference_id){
				$this->setState('message', Text::_('Something dodgy going on here'));
				continue;
			}

			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__falang_content'))
				->where('reference_table = '.$db->q($catid))
				->where('language_id = '.$db->q($language_id))
				->where('reference_id = '.$db->q($contentid));
			$db->setQuery($query);
			try {
				$db->execute();
				$this->setState('message', Text::_('COM_FALANG_TRANSLATION_DELETED'));
			} catch (Exception $e){
				Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
				continue;
			}

		}
		return $message;
	}


}

