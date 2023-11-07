<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die;

JLoader::import( 'views.default.view',FALANG_ADMINPATH);

/**
 * HTML View class for the WebLinks component
 *
 * @static
 * @package		Joomla
 * @subpackage	Weblinks
 * @since 1.0
 */
class StatisticsViewStatistics extends FalangViewDefault
{
	function display($tpl = null)
	{
        HTMLHelper::stylesheet( 'falang.css', 'administrator/components/com_falang/assets/css/' );

		$document = Factory::getDocument();
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_STATISTICS'));

		// Set toolbar items for the page
		ToolBarHelper::title(Text::_( 'COM_FALANG_TITLE_STATISTICS' ), 'statistics' );
//		JToolBarHelper::custom( 'cpanel.show', 'joomfish', 'joomfish', 'CONTROL PANEL' , false );
		ToolBarHelper::help( 'screen.statistics', true);

		JSubMenuHelper::addEntry(Text::_('COM_FALANG_CONTROL_PANEL'), 'index.php?option=com_falang');
		JSubMenuHelper::addEntry(Text::_('COM_FALANG_TRANSLATION'), 'index.php?option=com_falang&amp;task=translate.overview');
		JSubMenuHelper::addEntry(Text::_('COM_FALANG_ORPHANS'), 'index.php?option=com_falang&amp;task=translate.orphans');
//		JSubMenuHelper::addEntry(Text::_('Manage Translations'), 'index.php?option=com_falang&amp;task=manage.overview', false);
//		JSubMenuHelper::addEntry(Text::_('Statistics'), 'index.php?option=com_falang&amp;task=statistics.overview', true);
//		JSubMenuHelper::addEntry(Text::_('Language Configuration'), 'index.php?option=com_falang&amp;task=languages.show', false);
		JSubMenuHelper::addEntry(Text::_('COM_FALANG_CONTENT_ELEMENTS'), 'index.php?option=com_falang&amp;task=elements.show', false);
		JSubMenuHelper::addEntry(Text::_('COM_FALANG_HELP_AND_HOWTO'), 'index.php?option=com_falang&amp;task=help.show', false);

		$this->panelStates	= $this->get('PanelStates');
		$this->contentInfo	= $this->get('ContentInfo');
		$this->publishedTabs	= $this->get('PublishedTabs');

		$this->panelStates = $this->panelStates;
		$this->contentInfo = $this->contentInfo;
		$this->publishedTabs = $this->publishedTabs;

		parent::display($tpl);
	}

	/**
	 * This method renders a nice status overview table from the content element files
	 *
	 * @param unknown_type $contentelements
	 */
	function renderOriginalStatusTable($originalStatus, $message='', $langCodes=null) {
		$htmlOutput = '';

		$htmlOutput = '<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">';
		$htmlOutput .= '<tr><th>' .Text::_('Content'). '</th><th>' .Text::_('table exist'). '</th><th>' .Text::_('original total'). '</th><th>' .Text::_('Orphans'). '</th>';
		if(is_array($langCodes)) {
			foreach ($langCodes as $code) {
				$htmlOutput .= '<th>' .$code. '</th>';
			}
		}
		$htmlOutput .= '</tr>';

		$ceName = '';
		foreach ($originalStatus as $statusRow ) {
			$href = 'index2.php?option=com_falang&amp;task=overview&amp;act=translate&amp;catid='.$statusRow['catid'];
			$htmlOutput .= '<tr>';
			$htmlOutput .= '<td><a href="' .$href. '" target="_blank">' .$statusRow['name']. '</a></td>';
			$htmlOutput .= '<td style="text-align: center;">' .($statusRow['missing_table'] ? Text::_('missing') : Text::_('valid')). '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['total']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['orphans']. '</td>';
			if(is_array($langCodes)) {
				foreach ($langCodes as $code) {
					if( array_key_exists('langentry_' .$code, $statusRow)) {
						$persentage = intval( ($statusRow['langentry_' .$code]*100) / $statusRow['total'] );
						$htmlOutput .= '<td>' .$persentage. '%</td>';
					} else {
						$htmlOutput .= '<td>&nbsp;</td>';
					}
				}
			}
			$htmlOutput .= '</tr>';
		}

		if($message!='') {
			$span = 4 + count($langCodes);
			$htmlOutput .= '<tr><td colspan="'.$span.'" class="message">' .$message. '</td></tr>';
		}
		$htmlOutput .= '</table>';

		return $htmlOutput;
	}

	/**
	 * Status table for translation checks
	 *
	 * @param array $translationStatus
	 * @return unknown
	 */
	function renderTranslationStatusTable($translationStatus, $message='') {
		$htmlOutput = '';

		$htmlOutput .= '<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">';
		$htmlOutput .= '<tr><th>' .Text::_('Content'). '</th><th>' .Text::_('language'). '</th><th>' .Text::_('translation total'). '</th><th>' .Text::_('TITLE_PUBLISHED'). '</th><th>' .Text::_('valid'). '</th><th>' .Text::_('unvalid'). '</th></tr>';

		foreach ($translationStatus as $statusRow ) {
			$href = 'index.php?option=com_falang&amp;task=translate.overview&amp;catid='.$statusRow['catid'].'&amp;language_id='.$statusRow['language_id'];
			$htmlOutput .= '<tr>';
			$htmlOutput .= '<td><a href="'.$href.'">' .$statusRow['content']. '</a></td>';
			$htmlOutput .= '<td>' .$statusRow['language']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['total']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['published']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['state_valid']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['state_unvalid']. '</td>';
			$htmlOutput .= '</tr>';
		}

		if($message!='') {
			$htmlOutput .= '<tr><td colspan="7" class="message">' .$message. '</td></tr>';
		}
		$htmlOutput .= '</table>';

		return $htmlOutput;
	}
}
