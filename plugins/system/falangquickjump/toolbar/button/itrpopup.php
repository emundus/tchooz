<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Toolbar\ToolbarButton;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

class JToolbarButtonItrPopup extends ToolbarButton {
	/**
	 * Button type
	 *
	 * @var    string
	 */
	protected $_name = 'ItrPopup';

  /**
   * @var    array  Array containing information for loaded files
   * @since  3.0
   */
  protected static $loaded = array();

    /**
     * Prepare options for this button.
     *
     * @param   array  $options  The options about this button.
     *
     * @return  void
     *
     * @since   4.0.0
     */
    protected function prepareOptions(array &$options)
    {
        $options['icon'] = $options['icon'] ?? 'fas fa-square';

        parent::prepareOptions($options);

        $options['doTask'] = $this->_getCommand($this->getUrl());

        $options['selector'] = $options['selector'] ?? 'modal-' . $this->getName();
    }

    /**
     * Fetch the HTML for the button
     *
     * @param   string   $type          Unused string, formerly button type.
     * @param   string   $name          Modal name, used to generate element ID
     * @param   string   $text          The link text
     * @param   string   $url           URL for popup
     * @param   integer  $iframeWidth   Width of popup
     * @param   integer  $iframeHeight  Height of popup
     * @param   integer  $bodyHeight    Optional height of the modal body in viewport units (vh)
     * @param   integer  $modalWidth    Optional width of the modal in viewport units (vh)
     * @param   string   $onClose       JavaScript for the onClose event.
     * @param   string   $title         The title text
     * @param   string   $footer        The footer html
     *
     * @return  string  HTML string for the button
     *
     * @since   3.0
     */
    public function fetchButton($type = 'Modal', $name = '', $text = '', $url = '', $iframeWidth = 640,
                                $iframeHeight = 480, $bodyHeight = null, $modalWidth = null, $onClose = '', $title = '', $footer = null
    )
    {
        $this->name($name)
            ->text($text)
            ->task($this->_getCommand($url))
            ->url($url)
            ->icon('fas fa-' . $name)
            ->iframeWidth($iframeWidth)
            ->iframeHeight($iframeHeight)
            ->bodyHeight($bodyHeight)
            ->modalWidth($modalWidth)
            ->onclose($onClose)
            ->title($title)
            ->footer($footer);

        return $this->renderButton($this->options);
    }

    /**
     * Render button HTML.
     *
     * @param   array  $options  The button options.
     *
     * @return  string  The button HTML.
     *
     * @since  4.0.0
     * @udpate 5.9 remove the # from the button id (can't be selected)
     *             add inline script with webassets
     */
    protected function renderButton(array &$options): string
    {
        $html = [];


        $html[] = "<joomla-toolbar-button id=\"toolbar-".$options['name']."\">";
        $html[] = "<button type=\"button\" class=\"btn btn-small " . $options['class'] . "\" data-bs-toggle=\"modal\" data-bs-target=\"#modal-" . $options['name'] . "\" id=\"modal-" . $options['name'] . "-btn\" />";
        $html[] = "<span class=\"".$options['publish']." falang-status\">";
        $html[] = "</span>";
        $html[] = "<img src=\"../media/mod_falang/images/".$options['flag'].".gif\" alt=\"\" />";
        $html[] = "</button>";
        $html[] = "</joomla-toolbar-button>";

        // Build the options array for the modal
        $params = array();
        $params['title']  = $options['title'];
        $params['url']    = $options['url'];//$doTask;
        $params['modalWidth'] = $options['modalWidth'];
        $params['bodyHeight'] = $options['bodyHeight'];

        // Place modal div and scripts in a new div
        $html[] = "<div class=\"btn-group\" style=\"width: 0; margin: 0; padding: 0;\">";

        $html[] = HTMLHelper::_('bootstrap.renderModal', 'modal-' . $options['name'], $params);

        $html[] = "</div>";

        // We have to move the modal, otherwise we get problems with the backdrop
        // TODO: There should be a better workaround than this
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->addInlineScript(
            <<<JS
window.addEventListener('DOMContentLoaded', function() {
	document.body.appendChild(document.getElementById('modal-{$options['name']}'));
});
JS
        );

        // If an $onClose event is passed, add it to the modal JS object
        if ((string) $this->getOnclose() !== '')
        {
            $wa->addInlineScript(
                <<<JS
window.addEventListener('DOMContentLoaded', function() {
	jQuery('#{$options['selector']}').on('hide.bs.modal', function () {
	    {$options['onclose']}
	});
});
JS
            );
        }

        return implode("\n", $html);
    }

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string  $url  URL for popup
	 *
	 * @return  string  JavaScript command string
	 *
	 * @since   3.0
	 */
	private function _getCommand($url) {
		if (substr($url, 0, 4) !== 'http')
		{
			$url = URI::base() . $url;
		}

		return $url;
	}

    /**
     * Method to configure available option accessors.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    protected static function getAccessors(): array
    {
        return array_merge(
            parent::getAccessors(),
            [
                'url',
                'iframeWidth',
                'iframeHeight',
                'bodyHeight',
                'modalWidth',
                'onclose',
                'title',
                'footer',
                'selector',
                'listCheck',
            ]
        );
    }

}
