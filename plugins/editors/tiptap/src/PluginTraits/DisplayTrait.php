<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      Editors.tinymce
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Editors\TipTap\PluginTraits;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles the onDisplay event for the TipTap editor.
 *
 * @since  4.1.0
 */
trait DisplayTrait
{

	/**
	 * Gets the editor HTML markup
	 *
	 * @param   string  $name        Input name.
	 * @param   string  $content     The content of the field.
	 * @param   array   $attributes  Associative array of editor attributes.
	 * @param   array   $params      Associative array of editor parameters.
	 *
	 * @return  string  The HTML markup of the editor
	 *
	 * @since   5.0.0
	 */
	public function display(string $name, string $content = '', array $attributes = [], array $params = []): string
	{
		$app = $this->application;
		$doc = $app->getDocument();
		$wa  = $doc->getWebAssetManager();

		$wa->registerAndUseScript('plg_editors_tiptap', 'media/plg_editors_tiptap/app.js', ['defer' => true], ['type' => 'module']);

		// Editor variables
		$id     = $attributes['id'] ?? $name;
		$id     = preg_replace('/(\s|[^A-Za-z0-9_])+/', '_', $id);

		// Data object for the layout
		$textarea           = new \stdClass();
		$textarea->name     = $name;
		$textarea->id       = $id;
		$textarea->class    = 'mce_editable emundus-editor-tiptap';
		$textarea->content  = $content;
		$textarea->readonly = !empty($params['readonly']);
		$textarea->enable_suggestions = !empty($params['enable_suggestions']) ? $params['enable_suggestions'] : false;
		$textarea->suggestions = $params['suggestions'] ?? [];
		$textarea->plugins = $params['plugins'] ?? [];

		// Render Editor markup
		$editor = '<div class="js-editor-tiptap">';
		$editor .= LayoutHelper::render('emundus.tiptap.textarea', $textarea);
		$editor .= '</div>';

		return $editor;
	}
}
