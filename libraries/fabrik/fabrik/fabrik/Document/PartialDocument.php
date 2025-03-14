<?php
/**
 * Partial Document class
 *
 * @package     Joomla
 * @subpackage  Fabrik.Documents
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

namespace Fabrik\Document;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Utility\Utility;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\Document\Document;
use Fabrik\Helpers\Php;

jimport('joomla.utilities.utility');

/**
 * HtmlDocument class, provides an easy interface to parse and display a HTML document
 *
 * @since  11.1
 */
class PartialDocument extends Document
{
    /**
     * Array of Header `<link>` tags
     *
     * @var    array
     * @since  11.1
     */
    public $_links = array();

    /**
     * Array of custom tags
     *
     * @var    array
     * @since  11.1
     */
    public $_custom = array();

    /**
     * Name of the template
     *
     * @var    string
     * @since  11.1
     */
    public $template = null;

    /**
     * Base url
     *
     * @var    string
     * @since  11.1
     */
    public $baseurl = null;

    /**
     * Array of template parameters
     *
     * @var    array
     * @since  11.1
     */
    public $params = null;

    /**
     * File name
     *
     * @var    array
     * @since  11.1
     */
    public $_file = null;

    /**
     * String holding parsed template
     *
     * @var    string
     * @since  11.1
     */
    protected $_template = '';

    /**
     * Array of parsed template JDoc tags
     *
     * @var    array
     * @since  11.1
     */
    protected $_template_tags = array();

    /**
     * Integer with caching setting
     *
     * @var    integer
     * @since  11.1
     */
    protected $_caching = null;

    /**
     * Set to true when the document should be output as HTML5
     *
     * @var    boolean
     * @since  12.1
     *
     * @note  4.0  Will be replaced by $html5 and the default value will be true.
     */
    private $_html5 = null;

    /**
     * Class constructor
     *
     * @param   array  $options  Associative array of options
     *
     * @since   11.1
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        // Set document type
        $this->_type = 'partial';

        // Set default mime type and document metadata (meta data syncs with mime type by default)
        $this->setMimeEncoding('text/html');
    }

    /**
     * Get the HTML document head data
     *
     * @return  array  The document head data in array form
     *
     * @since   11.1
     */
    public function getHeadData()
    {
        $data = array();
        $data['title']       = $this->title;
        $data['description'] = $this->description;
        $data['link']        = $this->link;
        $data['metaTags']    = $this->_metaTags;
        $data['links']       = $this->_links;
        $data['styleSheets'] = $this->_styleSheets;
        $data['style']       = $this->_style;
        $data['scripts']     = $this->_scripts;
        $data['script']      = $this->_script;
        $data['custom']      = $this->_custom;
        $data['scriptText']  = Text::script();

        return $data;
    }

    /**
     * Reset the HTML document head data
     *
     * @param   mixed  $types  type or types of the heads elements to reset
     *
     * @return  PartialDocument  instance of $this to allow chaining
     *
     * @since   3.7.0
     */
    public function resetHeadData($types = null)
    {
        if (is_null($types))
        {
            $this->title        = '';
            $this->description  = '';
            $this->link         = '';
            $this->_metaTags    = array();
            $this->_links       = array();
            $this->_styleSheets = array();
            $this->_style       = array();
            $this->_scripts     = array();
            $this->_script      = array();
            $this->_custom      = array();
        }

        if (is_array($types))
        {
            foreach ($types as $type)
            {
                $this->resetHeadDatum($type);
            }
        }

        if (is_string($types))
        {
            $this->resetHeadDatum($types);
        }

        return $this;
    }

    /**
     * Reset a part the HTML document head data
     *
     * @param   string  $type  type of the heads elements to reset
     *
     * @return  void
     *
     * @since   3.7.0
     */
    private function resetHeadDatum($type)
    {
        switch ($type)
        {
            case 'title':
            case 'description':
            case 'link':
                $this->{$type} = '';
                break;

            case 'metaTags':
            case 'links':
            case 'styleSheets':
            case 'style':
            case 'scripts':
            case 'script':
            case 'custom':
                $realType = '_' . $type;
                $this->{$realType} = array();
                break;
        }
    }

    /**
     * Set the HTML document head data
     *
     * @param   array  $data  The document head data in array form
     *
     * @return  HtmlDocument|null instance of $this to allow chaining or null for empty input data
     *
     * @since   11.1
     */
    public function setHeadData($data)
    {
        if (empty($data) || !is_array($data))
        {
            return;
        }

        $this->title        = (isset($data['title']) && !empty($data['title'])) ? $data['title'] : $this->title;
        $this->description  = (isset($data['description']) && !empty($data['description'])) ? $data['description'] : $this->description;
        $this->link         = (isset($data['link']) && !empty($data['link'])) ? $data['link'] : $this->link;
        $this->_metaTags    = (isset($data['metaTags']) && !empty($data['metaTags'])) ? $data['metaTags'] : $this->_metaTags;
        $this->_links       = (isset($data['links']) && !empty($data['links'])) ? $data['links'] : $this->_links;
        $this->_styleSheets = (isset($data['styleSheets']) && !empty($data['styleSheets'])) ? $data['styleSheets'] : $this->_styleSheets;
        $this->_style       = (isset($data['style']) && !empty($data['style'])) ? $data['style'] : $this->_style;
        $this->_scripts     = (isset($data['scripts']) && !empty($data['scripts'])) ? $data['scripts'] : $this->_scripts;
        $this->_script      = (isset($data['script']) && !empty($data['script'])) ? $data['script'] : $this->_script;
        $this->_custom      = (isset($data['custom']) && !empty($data['custom'])) ? $data['custom'] : $this->_custom;

        if (isset($data['scriptText']) && !empty($data['scriptText']))
        {
            foreach ($data['scriptText'] as $key => $string)
            {
                Text::script($key, $string);
            }
        }

        return $this;
    }

    /**
     * Merge the HTML document head data
     *
     * @param   array  $data  The document head data in array form
     *
     * @return  PartialDocument|null instance of $this to allow chaining or null for empty input data
     *
     * @since   11.1
     */
    public function mergeHeadData($data)
    {
        if (empty($data) || !is_array($data))
        {
            return;
        }

        $this->title = (isset($data['title']) && !empty($data['title']) && !stristr($this->title, $data['title']))
            ? $this->title . $data['title']
            : $this->title;
        $this->description = (isset($data['description']) && !empty($data['description']) && !stristr($this->description, $data['description']))
            ? $this->description . $data['description']
            : $this->description;
        $this->link = (isset($data['link'])) ? $data['link'] : $this->link;

        if (isset($data['metaTags']))
        {
            foreach ($data['metaTags'] as $type1 => $data1)
            {
                $booldog = $type1 == 'http-equiv' ? true : false;

                foreach ($data1 as $name2 => $data2)
                {
                    $this->setMetaData($name2, $data2, $booldog);
                }
            }
        }

        $this->_links = (isset($data['links']) && !empty($data['links']) && is_array($data['links']))
            ? array_unique(array_merge($this->_links, $data['links']), SORT_REGULAR)
            : $this->_links;
        $this->_styleSheets = (isset($data['styleSheets']) && !empty($data['styleSheets']) && is_array($data['styleSheets']))
            ? array_merge($this->_styleSheets, $data['styleSheets'])
            : $this->_styleSheets;

        if (isset($data['style']))
        {
            foreach ($data['style'] as $type => $stdata)
            {
                if (!isset($this->_style[strtolower($type)]) || !stristr($this->_style[strtolower($type)], $stdata))
                {
                    $this->addStyleDeclaration($stdata, $type);
                }
            }
        }

        $this->_scripts = (isset($data['scripts']) && !empty($data['scripts']) && is_array($data['scripts']))
            ? array_merge($this->_scripts, $data['scripts'])
            : $this->_scripts;

        if (isset($data['script']))
        {
            foreach ($data['script'] as $type => $sdata)
            {
                if (!isset($this->_script[strtolower($type)]) || !stristr($this->_script[strtolower($type)], $sdata))
                {
                    $this->addScriptDeclaration($sdata, $type);
                }
            }
        }

        $this->_custom = (isset($data['custom']) && !empty($data['custom']) && is_array($data['custom']))
            ? array_unique(array_merge($this->_custom, $data['custom']))
            : $this->_custom;

        return $this;
    }

    /**
     * Adds `<link>` tags to the head of the document
     *
     * $relType defaults to 'rel' as it is the most common relation type used.
     * ('rev' refers to reverse relation, 'rel' indicates normal, forward relation.)
     * Typical tag: `<link href="index.php" rel="Start">`
     *
     * @param   string  $href      The link that is being related.
     * @param   string  $relation  Relation of link.
     * @param   string  $relType   Relation type attribute.  Either rel or rev (default: 'rel').
     * @param   array   $attribs   Associative array of remaining attributes.
     *
     * @return  PartialDocument instance of $this to allow chaining
     *
     * @since   11.1
     */
    public function addHeadLink($href, $relation, $relType = 'rel', $attribs = array())
    {
        $this->_links[$href]['relation'] = $relation;
        $this->_links[$href]['relType'] = $relType;
        $this->_links[$href]['attribs'] = $attribs;

        return $this;
    }

    /**
     * Adds a shortcut icon (favicon)
     *
     * This adds a link to the icon shown in the favorites list or on
     * the left of the url in the address bar. Some browsers display
     * it on the tab, as well.
     *
     * @param   string  $href      The link that is being related.
     * @param   string  $type      File type
     * @param   string  $relation  Relation of link
     *
     * @return  PartialDocument instance of $this to allow chaining
     *
     * @since   11.1
     */
    public function addFavicon($href, $type = 'image/vnd.microsoft.icon', $relation = 'shortcut icon')
    {
        $href = str_replace('\\', '/', $href);
        $this->addHeadLink($href, $relation, 'rel', array('type' => $type));

        return $this;
    }

    /**
     * Adds a custom HTML string to the head block
     *
     * @param   string  $html  The HTML to add to the head
     *
     * @return  PartialDocument instance of $this to allow chaining
     *
     * @since   11.1
     */
    public function addCustomTag($html)
    {
        $this->_custom[] = trim($html);

        return $this;
    }

    /**
     * Returns whether the document is set up to be output as HTML5
     *
     * @return  boolean true when HTML5 is used
     *
     * @since   12.1
     */
    public function isHtml5()
    {
        return $this->_html5;
    }

    /**
     * Sets whether the document should be output as HTML5
     *
     * @param   bool  $state  True when HTML5 should be output
     *
     * @return  void
     *
     * @since   12.1
     */
    public function setHtml5($state)
    {
        if (is_bool($state))
        {
            $this->_html5 = $state;
        }
    }

    /**
     * Get the contents of a document include
     *
     * @param   string  $type     The type of renderer
     * @param   string  $name     The name of the element to render
     * @param   array   $attribs  Associative array of remaining attributes.
     *
     * @return  mixed|string The output of the renderer
     *
     * @since   11.1
     */
    public function getBuffer($type = null, $name = null, $attribs = array())
    {
        // If no type is specified, return the whole buffer
        if ($type === null)
        {
            return parent::$_buffer;
        }

        $title = (isset($attribs['title'])) ? $attribs['title'] : null;

        if (isset(parent::$_buffer[$type][$name][$title]))
        {
            return parent::$_buffer[$type][$name][$title];
        }

        $renderer = $this->loadRenderer($type);

        if ($this->_caching == true && $type == 'modules')
        {
            $cache = Factory::getCache('com_modules', '');
            $hash = md5(serialize(array($name, $attribs, null, $renderer)));
            $cbuffer = $cache->get('cbuffer_' . $type);

            if (isset($cbuffer[$hash]))
            {
                return Cache::getWorkarounds($cbuffer[$hash], array('mergehead' => 1));
            }
            else
            {
                $options = array();
                $options['nopathway'] = 1;
                $options['nomodules'] = 1;
                $options['modulemode'] = 1;

                $this->setBuffer($renderer->render($name, $attribs, null), $type, $name);
                $data = parent::$_buffer[$type][$name][$title];

                $tmpdata = Cache::setWorkarounds($data, $options);

                $cbuffer[$hash] = $tmpdata;

                $cache->store($cbuffer, 'cbuffer_' . $type);
            }
        }
        else
        {
            $this->setBuffer($renderer->render($name, $attribs, null), $type, $name, $title);
        }

        return parent::$_buffer[$type][$name][$title];
    }

    /**
     * Set the contents a document includes
     *
     * @param   string  $content  The content to be set in the buffer.
     * @param   array   $options  Array of optional elements.
     *
     * @return  PartialDocument instance of $this to allow chaining
     *
     * @since   11.1
     */
    public function setBuffer($content, $options = array())
    {
        // The following code is just for backward compatibility.
        if (\func_num_args() > 1 && !\is_array($options)) {
            $args             = \func_get_args();
            $options          = [];
            $options['type']  = $args[1];
            $options['name']  = $args[2] ?? null;
            $options['title'] = $args[3] ?? null;
        }

        $type  = $options['type'] ?? '';
        $name  = $options['name'] ?? '';
        $title = $options['title'] ?? '';

        parent::$_buffer[$type][$name][$title] = $content;

        return $this;
    }

    /**
     * Parses the template and populates the buffer
     *
     * @param   array  $params  Parameters for fetching the template
     *
     * @return  PartialDocument instance of $this to allow chaining
     *
     * @since   11.1
     */
    public function parse($params = array())
    {
        return $this->_fetchTemplate($params)->_parseTemplate();
    }

    /**
     * Outputs the template to the browser.
     *
     * @param   boolean  $caching  If true, cache the output
     * @param   array    $params   Associative array of attributes
     *
     * @return  string The rendered data
     *
     * @since   11.1
     */
    public function render($caching = false, $params = array())
    {
        $this->_caching = $caching;

        if (empty($this->_template))
        {
            $this->parse($params);
        }

        $data = $this->_renderTemplate();
        parent::render();

        return $data;
    }

    /**
     * Count the modules based on the given condition
     *
     * @param   string  $condition  The condition to use
     *
     * @return  integer  Number of modules found
     *
     * @since   11.1
     */
    public function countModules($condition)
    {
        $operators = '(\+|\-|\*|\/|==|\!=|\<\>|\<|\>|\<=|\>=|and|or|xor)';
        $words = preg_split('# ' . $operators . ' #', $condition, null, PREG_SPLIT_DELIM_CAPTURE);

        if (count($words) === 1)
        {
            $name = strtolower($words[0]);
            $result = ((isset(parent::$_buffer['modules'][$name])) && (parent::$_buffer['modules'][$name] === false))
                ? 0 : count(ModuleHelper::getModules($name));

            return $result;
        }

        Log::add('Using an expression in PartialDocument::countModules() is deprecated.', Log::WARNING, 'deprecated');

        for ($i = 0, $n = count($words); $i < $n; $i += 2)
        {
            // Odd parts (modules)
            $name = strtolower($words[$i]);
            $words[$i] = ((isset(parent::$_buffer['modules'][$name])) && (parent::$_buffer['modules'][$name] === false))
                ? 0
                : count(ModuleHelper::getModules($name));
        }

        $str = 'return ' . implode(' ', $words) . ';';

        return Php::Eval(['code' => $str]);
    }

    /**
     * Count the number of child menu items of the current active menu item
     *
     * @return  integer  Number of child menu items
     *
     * @since   11.1
     */
    public function countMenuChildren()
    {
        static $children;

        if (!isset($children))
        {
            $db = Factory::getDbo();
            $app = Factory::getApplication();
            $menu = $app->getMenu();
            $active = $menu->getActive();
            $children = 0;

            if ($active)
            {
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from('#__menu')
                    ->where('parent_id = ' . $active->id)
                    ->where('published = 1');
                $db->setQuery($query);
                $children = $db->loadResult();
            }
        }

        return $children;
    }

    /**
     * Load a template file
     *
     * @param   string  $directory  The name of the template
     * @param   string  $filename   The actual filename
     *
     * @return  string  The contents of the template
     *
     * @since   11.1
     */
    protected function _loadTemplate($directory, $filename)
    {
        $contents = '';

        // Check to see if we have a valid template file
        if (file_exists($directory . '/' . $filename))
        {
            // Store the file path
            $this->_file = $directory . '/' . $filename;

            // Get the file content
            ob_start();
            require $directory . '/' . $filename;
            $contents = ob_get_contents();
            ob_end_clean();
        }

        // Try to find a favicon by checking the template and root folder
        $icon = '/favicon.ico';

        foreach (array($directory, JPATH_BASE) as $dir)
        {
            if (file_exists($dir . $icon))
            {
                $path = str_replace(JPATH_BASE, '', $dir);
                $path = str_replace('\\', '/', $path);
                $this->addFavicon(Uri::base(true) . $path . $icon);
                break;
            }
        }

        return $contents;
    }

    /**
     * Fetch the template, and initialise the params
     *
     * @param   array  $params  Parameters to determine the template
     *
     * @return  PartialDocument instance of $this to allow chaining
     *
     * @since   11.1
     */
    protected function _fetchTemplate($params = array())
    {
        // Check
        $directory = isset($params['directory']) ? $params['directory'] : 'templates';
        $filter = InputFilter::getInstance();
        $template = $filter->clean($params['template'], 'cmd');
        $file = $filter->clean($params['file'], 'cmd');

        if (!file_exists($directory . '/' . $template . '/' . $file))
        {
            $template = 'system';
        }

        if (!file_exists($directory . '/' . $template . '/' . $file))
        {
            $file = 'index.php';
        }

        // Load the language file for the template
        $lang = Factory::getApplication()->getLanguage();

        // 1.5 or core then 1.6
        $lang->load('tpl_' . $template, JPATH_BASE, null, false, true)
        || $lang->load('tpl_' . $template, $directory . '/' . $template, null, false, true);

        // Assign the variables
        $this->template = $template;
        $this->baseurl = Uri::base(true);
        $this->params = isset($params['params']) ? $params['params'] : new Registry;

        // Load
        $this->_template = $this->_loadTemplate($directory . '/' . $template, $file);

        return $this;
    }

    /**
     * Parse a document template
     *
     * @return  PartialDocument  instance of $this to allow chaining
     *
     * @since   11.1
     */
    protected function _parseTemplate()
    {
        $matches = array();

        if (preg_match_all('#<jdoc:include\ type="([^"]+)"(.*)\/>#iU', $this->_template, $matches))
        {
            $template_tags_first = array();
            $template_tags_last = array();

            // Step through the jdocs in reverse order.
            for ($i = count($matches[0]) - 1; $i >= 0; $i--)
            {
                $type = $matches[1][$i];
                $attribs = empty($matches[2][$i]) ? array() : Utility::parseAttributes($matches[2][$i]);
                $name = isset($attribs['name']) ? $attribs['name'] : null;

                // Separate buffers to be executed first and last
                if ($type == 'module' || $type == 'modules')
                {
                    $template_tags_first[$matches[0][$i]] = array('type' => $type, 'name' => $name, 'attribs' => $attribs);
                }
                else
                {
                    $template_tags_last[$matches[0][$i]] = array('type' => $type, 'name' => $name, 'attribs' => $attribs);
                }
            }
            // Reverse the last array so the jdocs are in forward order.
            $template_tags_last = array_reverse($template_tags_last);

            $this->_template_tags = $template_tags_first + $template_tags_last;
        }

        return $this;
    }

    /**
     * Render pre-parsed template
     *
     * @return string rendered template
     *
     * @since   11.1
     */
    protected function _renderTemplate()
    {
        $replace = array();
        $with = array();

        foreach ($this->_template_tags as $jdoc => $args)
        {
            $replace[] = $jdoc;
            $with[] = $this->getBuffer($args['type'], $args['name'], $args['attribs']);
        }

        return str_replace($replace, $with, $this->_template);
    }
}

