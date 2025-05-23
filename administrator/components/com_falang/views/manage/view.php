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
class ManageViewManage extends FalangViewDefault
{
	function display($tpl = null)
	{
        HTMLHelper::stylesheet( 'falang.css', 'administrator/components/com_falang/assets/css/' );

		$document = Factory::getDocument();
		$document->setTitle(Text::_('COM_FALANG_TITLE') . ' :: ' .Text::_('COM_FALANG_TITLE_MANAGEMENT'));
		
		// Set toolbar items for the page
		ToolBarHelper::title(Text::_( 'COM_FALANG_TITLE_MANAGEMENT' ), 'manage' );

		$this->panelStates	= $this->get('PanelStates');
		$this->contentInfo	= $this->get('ContentInfo');
		$this->publishedTabs	= $this->get('PublishedTabs');

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
	 * This method renders the information page for the copy process
	 *
	 * @param unknown_type $contentelements
	 */
	function renderCopyInformation($original2languageInfo, $message='', $langList=null) {
		$htmlOutput = '';

		if($message!='') {
			$htmlOutput .= '<span class="message">' .$message. '</span><br />';
		}
		$htmlOutput .= '<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">';
		$htmlOutput .= '<tr><th width="25%">' .Text::_('Content'). '</th><th width="10%">' .Text::_('original total'). '</th><th width="10%">' .Text::_('processed'). '</th><th width="10%">' .Text::_('copied'). '</th><th>' .Text::_('copy to language'). '</th>';
		$htmlOutput .= "</tr>\n";

		$ceName = '';
		foreach ($original2languageInfo as $statusRow ) {
			$href = 'index2.php?option=com_falang&amp;task=translate.overview&amp;catid='.$statusRow['catid'];
			$htmlOutput .= '<tr>';
			$htmlOutput .= '<td><a href="' .$href. '">' .$statusRow['name']. '</a></td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['total']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['processed']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['copied']. '</td>';
			$htmlOutput .= '<td style="text-align: center;"><input name="copy_catid" type="checkbox" value="' .$statusRow['catid'].'" /></td>';
			$htmlOutput .= "</tr>\n";
		}

		if($langList != null) {
			$htmlOutput .= '<tr><td>' .Text::_('select language'). '</td>';
			$htmlOutput .= '<td style="text-align: center;" colspan="3" nowrap="nowrap">' .$langList. '<input id="confirm_overwrite" name="confirm_overwrite" type="checkbox" value="1" />' .Text::_('overwrite existing translations'). '&nbsp;';
			$htmlOutput .= '<input id="copy_original" name="copy_original" type="button" value="' .Text::_('copy'). '" onClick="executeCopyOriginal(document.getElementById(\'select_language\'), document.getElementById(\'confirm_overwrite\'), document.getElementsByName(\'copy_catid\'))" /></td>';
			$htmlOutput .= '<td>&nbsp;</tb>';
			$htmlOutput .= "</tr>\n";
		}

		$htmlOutput .= '</table>';

		return $htmlOutput;
	}

	/**
	 * This method renders the information page for the copy process
	 *
	 * @param unknown_type $contentelements
	 */
	function renderCopyProcess($original2languageInfo, $message='') {
		$htmlOutput = '';

		$htmlOutput = '<table cellpadding="4" cellspacing="0" border="0" width="100%" class="adminlist">';
		$htmlOutput .= '<tr><th>' .Text::_('Content'). '</th><th width="10%">' .Text::_('original total'). '</th><th width="10%">' .Text::_('processed'). '</th><th width="10%">' .Text::_('copied'). '</th>';
		$htmlOutput .= '</tr>';

		$ceName = '';
		foreach ($original2languageInfo as $statusRow ) {
			$href = 'index2.php?option=com_falang&amp;task=translate.overview&amp;catid='.$statusRow['catid'];
			$htmlOutput .= '<tr>';
			$htmlOutput .= '<td><a href="' .$href. '">' .$statusRow['name']. '</a></td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['total']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['processed']. '</td>';
			$htmlOutput .= '<td style="text-align: center;">' .$statusRow['copied']. '</td>';
			$htmlOutput .= '</tr>';
		}
		if($message!='') {
			$htmlOutput .= '<tr><td colspan="7" class="message">' .$message. '</td></tr>';
		}
		$htmlOutput .= '</table>';

		return $htmlOutput;
	}
}
