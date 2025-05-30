<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Editors.tinymce
 *
 * @copyright   (C) 2021 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Editors\TinyMCE\PluginTraits;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles the onDisplay event for the TinyMCE editor.
 *
 * @since  4.1.0
 */
trait DisplayTrait
{
    use GlobalFilters;
    use KnownButtons;
    use ResolveFiles;
    use ToolbarPresets;
    use XTDButtons;

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
        // General variables
        $app             = $this->application;
        $user            = $app->getIdentity();
        $language        = $app->getLanguage();
        $doc             = $app->getDocument();
        $wa              = $doc->getWebAssetManager();
        $options         = $doc->getScriptOptions('plg_editor_tinymce');
        $csrf            = Session::getFormToken();

        // Editor variables
        $col             = $attributes['col'] ?? '';
        $row             = $attributes['row'] ?? '';
        $width           = $attributes['width'] ?? '';
        $height          = $attributes['height'] ?? '';
        $id              = $attributes['id'] ?? $name;
        $id              = preg_replace('/(\s|[^A-Za-z0-9_])+/', '_', $id);
        $nameGroup       = explode('[', preg_replace('/\[\]|\]/', '', $name));
        $fieldName       = end($nameGroup);
        $buttons         = $params['buttons'] ?? true;
        $asset           = $params['asset'] ?? 0;
        $author          = $params['author'] ?? 0;
        $scriptOptions   = [];
        $externalPlugins = [];
        $theme           = 'silver';

        // Register assets
        $wa->getRegistry()->addExtensionRegistryFile('plg_editors_tinymce');

        // Data object for the layout
        $textarea           = new \stdClass();
        $textarea->name     = $name;
        $textarea->id       = $id;
        $textarea->class    = 'mce_editable joomla-editor-tinymce';
        $textarea->cols     = $col;
        $textarea->rows     = $row;
        $textarea->width    = is_numeric($width) ? $width . 'px' : $width;
        $textarea->height   = is_numeric($height) ? $height . 'px' : $height;
        $textarea->content  = $content;
        $textarea->readonly = !empty($params['readonly']);

        // Render Editor markup
        $editor = '<div class="js-editor-tinymce">';
        $editor .= LayoutHelper::render('joomla.tinymce.textarea', $textarea);
        $editor .= !$app->client->mobile ? LayoutHelper::render('joomla.tinymce.togglebutton') : '';
        $editor .= '</div>';

        // Prepare the instance specific options
        if (empty($options['tinyMCE'][$fieldName])) {
            $options['tinyMCE'][$fieldName] = [];
        }

        // Width and height
        if ($width && empty($options['tinyMCE'][$fieldName]['width'])) {
            $options['tinyMCE'][$fieldName]['width'] = $width;
        }

        if ($height && empty($options['tinyMCE'][$fieldName]['height'])) {
            $options['tinyMCE'][$fieldName]['height'] = $height;
        }

        // Set editor to readonly mode
        if (!empty($params['readonly'])) {
            $options['tinyMCE'][$fieldName]['readonly'] = 1;
        }

        // The ext-buttons
        if (empty($options['tinyMCE'][$fieldName]['joomlaExtButtons'])) {
            $tinyButtons = $this->tinyButtons($buttons, ['asset' => $asset, 'author' => $author, 'editorId' => $id]);

            $options['tinyMCE'][$fieldName]['joomlaMergeDefaults'] = true;
            $options['tinyMCE'][$fieldName]['joomlaExtButtons']    = $tinyButtons;
        }

        $doc->addScriptOptions('plg_editor_tinymce', $options, false);

        // Setup Default (common) options for all Editor instances
        // Check whether we already have them
        if (!empty($options['tinyMCE']['default'])) {
            return $editor;
        }

        $ugroups  = array_combine($user->getAuthorisedGroups(), $user->getAuthorisedGroups());

        // Prepare the parameters
        $levelParams      = new Registry();
        $extraOptions     = new \stdClass();
        $toolbarParams    = new \stdClass();
        $extraOptionsAll  = (array) $this->params->get('configuration.setoptions', []);
        $toolbarParamsAll = (array) $this->params->get('configuration.toolbars', []);

        // Sort the array in reverse, so the items with the lowest access level goes first
        krsort($extraOptionsAll);

        // Get configuration depend from User group
        foreach ($extraOptionsAll as $set => $val) {
            $val         = (object) $val;
            $val->access = empty($val->access) ? [] : $val->access;

            // Check whether User in one of allowed group
            foreach ($val->access as $group) {
                if (isset($ugroups[$group])) {
                    $extraOptions  = $val;
                    $toolbarParams = (object) $toolbarParamsAll[$set];
                }
            }
        }

        // load external plugins
        if (isset($extraOptions->external_plugins) && $extraOptions->external_plugins) {
            foreach (json_decode(json_encode($extraOptions->external_plugins), true) as $external) {
                // get the path for readability
                $path = $external['path'];

                // if we have a name and path, add it to the list
                if ($external['name'] != '' && $path != '') {
                    $externalPlugins[$external['name']] = str_starts_with($path, '/') ? Uri::root() . substr($path, 1) : $path;
                }
            }
        }

        // Merge the params
        $levelParams->loadObject($toolbarParams);
        $levelParams->loadObject($extraOptions);

        // Set the selected skin
        $skin     = $levelParams->get($app->isClient('administrator') ? 'skin_admin' : 'skin', 'oxide');
        $skinDark = $levelParams->get($app->isClient('administrator') ? 'skin_admin_dark' : 'skin_dark', 'oxide-dark');

        // Check that selected skin exists.
        $skin     = is_dir(JPATH_ROOT . '/media/vendor/tinymce/skins/ui/' . $skin) ? $skin : 'oxide';
        $skinDark = is_dir(JPATH_ROOT . '/media/vendor/tinymce/skins/ui/' . $skinDark) ? $skinDark : 'oxide-dark';

        if (!$levelParams->get('lang_mode', 1)) {
            // Admin selected language
            $langPrefix = $levelParams->get('lang_code', 'en');
        } else {
            // Reflect the current language
            if (file_exists(JPATH_ROOT . '/media/vendor/tinymce/langs/' . $language->getTag() . '.js')) {
                $langPrefix = $language->getTag();
            } elseif (file_exists(JPATH_ROOT . '/media/vendor/tinymce/langs/' . substr($language->getTag(), 0, strpos($language->getTag(), '-')) . '.js')) {
                $langPrefix = substr($language->getTag(), 0, strpos($language->getTag(), '-'));
            } else {
                $langPrefix = 'en';
            }
        }

        $use_content_css    = $levelParams->get('content_css', 1);
        $content_css_custom = $levelParams->get('content_css_custom', '');
        $content_css        = null;

        // Loading of css file for 'styles' dropdown
        if ($content_css_custom) {
            /**
             * If URL, just pass it to $content_css
             * else, assume it is a file name in the current template folder
             */
            $content_css = str_contains($content_css_custom, 'http')
                ? $content_css_custom
                : $this->includeRelativeFiles('css', $content_css_custom);
        } else {
            // Process when use_content_css is Yes and no custom file given
            $content_css = $use_content_css ? $this->includeRelativeFiles('css', 'editor' . (JDEBUG ? '' : '.min') . '.css') : $content_css;
        }

        $ignore_filter = false;

        // Text filtering
        if ($levelParams->get('use_config_textfilters', 0)) {
            // Use filters from com_config
            $filter            = static::getGlobalFilters($user);
            $ignore_filter     = $filter === false;
            $blockedTags       = !empty($filter->blockedTags) ? $filter->blockedTags : [];
            $blockedAttributes = !empty($filter->blockedAttributes) ? $filter->blockedAttributes : [];
            $tagArray          = !empty($filter->tagsArray) ? $filter->tagsArray : [];
            $attrArray         = !empty($filter->attrArray) ? $filter->attrArray : [];
            $invalid_elements  = implode(',', array_merge($blockedTags, $blockedAttributes, $tagArray, $attrArray));

            // Valid elements are all entries listed as allowed in com_config, which are now missing in the filter blocked properties
            $default_filter = InputFilter::getInstance();
            $valid_elements = implode(',', array_diff($default_filter->blockedTags, $blockedTags));

            $extended_elements = '';
        } else {
            // Use filters from TinyMCE params
            $invalid_elements  = trim($levelParams->get('invalid_elements', 'script,applet,iframe'));
            $extended_elements = trim($levelParams->get('extended_elements', ''));
            $valid_elements    = trim($levelParams->get('valid_elements', ''));
        }

        // The param is true for vertical resizing only, false or both
        $resizing          = (bool) $levelParams->get('resizing', true);
        $resize_horizontal = (bool) $levelParams->get('resize_horizontal', true);

        if ($resizing && $resize_horizontal) {
            $resizing = 'both';
        }

        // Set of always available plugins
        $plugins  = [
            'autolink',
            'lists',
            'importcss',
            'quickbars',
            'jxtdbuttons',
        ];
        $wa->useScript('plg_editors_tinymce.jxtdbuttons');

        // Allowed elements
        $elements = [
            'hr[id|title|alt|class|width|size|noshade]',
        ];
        $elements = $extended_elements ? array_merge($elements, explode(',', $extended_elements)) : $elements;

        // Prepare the toolbar/menubar
        $knownButtons = static::getKnownButtons();

        // Check if there no value at all
        if (!$levelParams->get('menu') && !$levelParams->get('toolbar1') && !$levelParams->get('toolbar2')) {
            // Get from preset
            $presets = static::getToolbarPreset();

            /**
             * Predefine group as:
             * Set 0: for Administrator, Editor, Super Users (4,7,8)
             * Set 1: for Registered, Manager (2,6), all else are public
             */
            switch (true) {
                case isset($ugroups[4]) || isset($ugroups[7]) || isset($ugroups[8]):
                    $preset = $presets['advanced'];
                    break;

                case isset($ugroups[2]) || isset($ugroups[6]):
                    $preset = $presets['medium'];
                    break;

                default:
                    $preset = $presets['simple'];
            }

            $levelParams->loadArray($preset);
        }

        $menubar  = (array) $levelParams->get('menu', []);
        $toolbar1 = (array) $levelParams->get('toolbar1', []);
        $toolbar2 = (array) $levelParams->get('toolbar2', []);

        // Make an easy way to check which button is enabled
        $allButtons = array_merge($toolbar1, $toolbar2);
        $allButtons = array_combine($allButtons, $allButtons);

        // Check for button-specific plugins
        foreach ($allButtons as $btnName) {
            if (!empty($knownButtons[$btnName]['plugin'])) {
                $plugins[] = $knownButtons[$btnName]['plugin'];
            }
        }

        // Check for extra plugins, from the setoptions form
        foreach (['wordcount' => 1, 'advlist' => 1, 'autosave' => 1] as $pName => $def) {
            if ($levelParams->get($pName, $def)) {
                $plugins[] = $pName;
            }
        }

        // Use CodeMirror in the code view instead of plain text to provide syntax highlighting
        if ($levelParams->get('sourcecode', 1)) {
            // Enable joomla-highlighter plugin
            $wa->getRegistry()->addExtensionRegistryFile('plg_editors_codemirror');
            $wa->useScript('plg_editors_tinymce.highlighter');
            $plugins[] = 'joomlaHighlighter';
        }

        $dragdrop = $levelParams->get('drag_drop', 1);

        if ($dragdrop && $user->authorise('core.create', 'com_media')) {
            $wa->useScript('plg_editors_tinymce.jdragndrop');
            $plugins[]  = 'jdragndrop';
            $uploadUrl  = Uri::base(true) . '/index.php?option=com_media&format=json&url=1&task=api.files';
            $uploadPath = $levelParams->get('path', '');

            // Make sure the path is full, and contain the media adapter in it.
            $mediaHelper = new class () {
                use ProviderManagerHelperTrait;

                public function prepareTinyMCEUploadPath(string $path): string
                {
                    $result = $this->resolveAdapterAndPath($path);

                    return implode(':', $result);
                }
            };

            Text::script('PLG_TINY_ERR_UNSUPPORTEDBROWSER');
            Text::script('ERROR');
            Text::script('PLG_TINY_DND_ADDITIONALDATA');
            Text::script('PLG_TINY_DND_ALTTEXT');
            Text::script('PLG_TINY_DND_LAZYLOADED');
            Text::script('PLG_TINY_DND_EMPTY_ALT');
            Text::script('PLG_TINY_DND_FILE_EXISTS_ERROR');

            $scriptOptions['parentUploadFolder'] = $mediaHelper->prepareTinyMCEUploadPath($uploadPath);
            $scriptOptions['uploadUri']          = $uploadUrl;
        }

        // Convert pt to px in dropdown
        $scriptOptions['font_size_formats'] = '8px 10px 12px 14px 18px 24px 36px';

        // select the languages for the "language of parts" menu
        if (isset($extraOptions->content_languages) && $extraOptions->content_languages) {
            foreach (json_decode(json_encode($extraOptions->content_languages), true) as $content_language) {
                // if we have a language name and a language code then add to the menu
                if ($content_language['content_language_name'] != '' && $content_language['content_language_code'] != '') {
                    $ctemp[] = ['title' => $content_language['content_language_name'], 'code' => $content_language['content_language_code']];
                }
            }
            if (isset($ctemp)) {
                $scriptOptions['content_langs'] = array_merge($ctemp);
            }
        }

        // Should load the template plugin?
        if (!empty($allButtons['jtemplate'])) {
            $wa->useScript('plg_editors_tinymce.jtemplate');
            $plugins[] = 'jtemplate';

            $scriptOptions['jtemplates'] = Uri::base(true) . '/index.php?option=com_ajax&plugin=tinymce&group=editors&format=json&format=json&template='
                . $levelParams->get('content_template_path') . '&' . $csrf . '=1';
        }

        // User custom plugins and buttons
        $custom_plugin = trim($levelParams->get('custom_plugin', ''));
        $custom_button = trim($levelParams->get('custom_button', ''));

        if ($custom_plugin) {
            $plugins   = array_merge($plugins, explode(str_contains($custom_plugin, ',') ? ',' : ' ', $custom_plugin));
        }

        if ($custom_button) {
            $toolbar1  = array_merge($toolbar1, explode(str_contains($custom_button, ',') ? ',' : ' ', $custom_button));
        }

        // Merge the two toolbars for backwards compatibility
        $toolbar = array_merge($toolbar1, $toolbar2);

        // Set default classes to empty
        $linkClasses = [];

        // Load the link classes list
        if (isset($extraOptions->link_classes_list) && $extraOptions->link_classes_list) {
            $linksClassesList = $extraOptions->link_classes_list;

            if ($linksClassesList) {
                $linkClasses = [['title' => TEXT::_('PLG_TINY_FIELD_LINK_CLASS_NONE'), 'value' => '']];

                // Create an array for the link classes
                foreach ($linksClassesList as $linksClassList) {
                    array_push($linkClasses, ['title' => $linksClassList->class_name, 'value' => $linksClassList->class_list]);
                }
            }
        }

        // Build the final options set
        $scriptOptions   = array_merge(
            $scriptOptions,
            [
                'suffix'                      => JDEBUG ? '' : '.min',
                'baseURL'                     => Uri::root(true) . '/media/vendor/tinymce',
                'directionality'              => $language->isRtl() ? 'rtl' : 'ltr',
                'language'                    => $langPrefix,
                'autosave_restore_when_empty' => false,
                'skin_light'                  => $skin,
                'skin_dark'                   => $skinDark,
                'theme'                       => $theme,
                'schema'                      => 'html5',

                // Prevent cursor from getting stuck in blocks when nested or at end of document.
                'end_container_on_empty_block' => true,

                // Toolbars
                'menubar' => empty($menubar) ? false : implode(' ', array_unique($menubar)),
                'toolbar' => empty($toolbar) ? null : 'jxtdbuttons ' . implode(' ', $toolbar),

                'plugins' => implode(',', array_unique($plugins)),

                // Quickbars
                'quickbars_image_toolbar'     => false,
                'quickbars_insert_toolbar'    => false,
                'quickbars_selection_toolbar' => 'bold italic underline | H2 H3 | link blockquote',

                // Cleanup/Output
                'browser_spellcheck' => true,
                'entity_encoding'    => $levelParams->get('entity_encoding', 'raw'),
                'verify_html'        => !$ignore_filter,
                'paste_as_text'      => (bool) $levelParams->get('paste_as_text', false),

                'valid_elements'          => $valid_elements,
                'extended_valid_elements' => implode(',', $elements),
                'invalid_elements'        => $invalid_elements,

                // URL
                'relative_urls'      => (bool) $levelParams->get('relative_urls', true),
                'remove_script_host' => false,

                // Link classes
                'link_class_list' => $linkClasses,

                // Drag and drop Images always FALSE, reverting this allows for inlining the images
                'paste_data_images' => false,

                // Layout
                'content_css'       => $content_css,
                'document_base_url' => Uri::root(true) . '/',
                'image_caption'     => true,
                'importcss_append'  => true,
                'height'            => $this->params->get('html_height', '550px'),
                'width'             => $this->params->get('html_width', ''),
                'elementpath'       => (bool) $levelParams->get('element_path', true),
                'resize'            => $resizing,
                'external_plugins'  => empty($externalPlugins) ? null : $externalPlugins,
                'contextmenu'       => (bool) $levelParams->get('contextmenu', true) ? null : false,
                'toolbar_sticky'    => true,
                'toolbar_mode'      => $levelParams->get('toolbar_mode', 'sliding'),

                // Image plugin options
                'a11y_advanced_options' => true,
                'image_advtab'          => (bool) $levelParams->get('image_advtab', false),
                'image_title'           => true,
                'image_class_list'      => [
                    ['title' => 'None', 'value' => 'float-none'],
                    ['title' => 'Left', 'value' => 'float-start'],
                    ['title' => 'Right', 'value' => 'float-end'],
                    ['title' => 'Center', 'value' => 'mx-auto d-block'],
                ],

                // Drag and drop specific
                'dndEnabled' => $dragdrop,

                // Disable TinyMCE Branding
                'branding'  => false,
                'promotion' => false,

                // Hardened security
                // @todo enable with TinyMCE 7 using https://www.tiny.cloud/docs/tinymce/latest/content-filtering/#sandbox-iframes-exclusions otherwise all embed PDFs are broken
                'sandbox_iframes'       => (bool) $levelParams->get('sandbox_iframes', true),
                'convert_unsafe_embeds' => true,

                // Specify the attributes to be used when previewing a style. This prevents white text on a white background making the preview invisible.
                'preview_styles' => 'font-family font-size font-weight font-style text-decoration text-transform background-color border border-radius outline text-shadow',
            ]
        );

        if ($levelParams->get('newlines')) {
            // Break
            $scriptOptions['newline_behavior'] = 'invert';
        } else {
            // Paragraph
            $scriptOptions['newline_behavior']  = 'default';
            $scriptOptions['forced_root_block'] = 'p';
        }

        $scriptOptions['link_rel_list'] = [
            ['title' => 'None', 'value' => ''],
            ['title' => 'Alternate', 'value' => 'alternate'],
            ['title' => 'Author', 'value' => 'author'],
            ['title' => 'Bookmark', 'value' => 'bookmark'],
            ['title' => 'Help', 'value' => 'help'],
            ['title' => 'License', 'value' => 'license'],
            ['title' => 'Lightbox', 'value' => 'lightbox'],
            ['title' => 'Next', 'value' => 'next'],
            ['title' => 'No Follow', 'value' => 'nofollow'],
            ['title' => 'No Referrer', 'value' => 'noreferrer'],
            ['title' => 'Prefetch', 'value' => 'prefetch'],
            ['title' => 'Prev', 'value' => 'prev'],
            ['title' => 'Search', 'value' => 'search'],
            ['title' => 'Tag', 'value' => 'tag'],
        ];

        $scriptOptions['style_formats'] = [
            [
                'title' => Text::_('PLG_TINY_MENU_CONTAINER'),
                'items' => [
                    ['title' => 'article', 'block' => 'article', 'wrapper' => true, 'merge_siblings' => false],
                    ['title' => 'aside', 'block' => 'aside', 'wrapper' => true, 'merge_siblings' => false],
                    ['title' => 'section', 'block' => 'section', 'wrapper' => true, 'merge_siblings' => false],
                ],
            ],
        ];

        $scriptOptions['style_formats_merge'] = true;
        $options['tinyMCE']['default']        = $scriptOptions;

        $doc->addScriptOptions('plg_editor_tinymce', $options);

        return $editor;
    }
}
