<?php
/**
 * Plugin element to render day/month/year dropdowns
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.birthday
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// Check to ensure this file is included in Joomla!
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

/**
 * Plugin element to render day/month/year drop-downs
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.birthday
 * @since       3.0
 */

class PlgFabrik_ElementYears extends PlgFabrik_Element {
    /**
     * Does the element contain sub elements e.g checkboxes radio-buttons
     *
     * @var bool
     */
    public $hasSubElements = true;

    /**
     * Get db table field type
     *
     * @return  string
     */
    public function getFieldDescription() {
        return 'VARCHAR(4)';
    }

    /**
     * Determines the value for the element in the form view
     *
     * @param   array  $data           Form data
     * @param   int    $repeatCounter  When repeating joined groups we need to know what part of the array to access
     * @param   array  $opts           Options, 'raw' = 1/0 use raw value
     *
     * @return  string	value
     */
    public function getValue($data, $repeatCounter = 0, $opts = array()) {
        $value = parent::getValue($data, $repeatCounter, $opts);

        if (is_array($value)) {
            $year = FArrayHelper::getValue($value, 0);
            $value = $year;
        }

        return $value;
    }

    /**
     * Draws the html form element
     *
     * @param   array  $data           To pre-populate element with
     * @param   int    $repeatCounter  Repeat group counter
     *
     * @return  string	elements html
     */

    public function render($data, $repeatCounter = 0) {
        $aNullDates = array('0000', '', $this->_db->getNullDate());
        $name = $this->getHTMLName($repeatCounter);
        $id = $this->getHTMLId($repeatCounter);
        $params = $this->getParams();
        $element = $this->getElement();

        if (is_array($this->getFormModel()->data)) {
            $data = $this->getFormModel()->data;
        }

        $value = $this->getValue($data, $repeatCounter);

        if (!$this->isEditable()) {

            if (!in_array($value, $aNullDates)) {

                $layout = $this->getLayout('detail');
                $layoutData = new stdClass;
                $layoutData->text =  $this->replaceWithIcons($value);
                $layoutData->hidden = $element->hidden;

                return $layout->render($layoutData);
            } else {
                return '';
            }
        } else {
            // Weirdness for failed validation
            $value = strstr($value, ',') ? array_reverse(explode(',', $value)) : explode('-', $value);
            $yearValue = FArrayHelper::getValue($value, 0);
            $errorCSS = (isset($this->_elementError) && $this->_elementError != '') ? ' elementErrorHighlight' : '';
            $advancedClass = $this->getAdvancedSelectClass();

            $attributes = 'class="input-small fabrikinput inputbox ' . $advancedClass . ' ' . $errorCSS . '"';

            $layout = $this->getLayout('form');
            $layoutData = new stdClass;
            $layoutData->id = $id;
            $layoutData->attribs = $attributes;

            $layoutData->year_name = preg_replace('#(\[\])$#', '', $name);
            $layoutData->year_id = $id;
            $layoutData->year_options = $this->_yearOptions();
            $layoutData->year_value = $yearValue;


            return $layout->render($layoutData);
        }
    }

    /**
     * Get select list year options
     * @return array
     */
    private function _yearOptions() {
        $params = $this->getParams();
        $years = array(
            JHTML::_(
                'select.option',
                '',
                Text::_($params->get('birthday_yearlabel', 'PLG_ELEMENT_BIRTHDAY_YEAR')),
                'value',
                'text',
                false
            )
        );
        // Jaanus: now we can choose one exact year A.C to begin the dropdown AND would the latest year be current year or some years earlier/later.
        $date = date('Y') + (int) $params->get('birthday_forward', 0);
        $yearOpt = $params->get('birthday_yearopt');
        $yearStart = (int) $params->get('birthday_yearstart');
        $yearDiff = $yearOpt == 'number' ? $yearStart : $date - $yearStart;

        for ($i = $date; $i >= $date - $yearDiff; $i--) {
            $years[] = JHTML::_('select.option', (string) $i);
        }

        return $years;
    }

    /**
     * Manipulates posted form data for insertion into database
     *
     * @param   mixed  $val   this elements posted form data
     * @param   array  $data  posted form data
     *
     * @return  mixed
     */

    public function storeDatabaseFormat($val, $data)
    {
        return $this->_indStoreDBFormat($val);
    }

    private function _indStoreDBFormat($val) {
        $params = $this->getParams();

        if (is_array($val)) {
            if ($params->get('empty_is_null', '1') == 0 || !in_array('', $val)) {
                return $val[0];
            }
        } else {
            if ($params->get('empty_is_null', '1') == '0' || !in_array('', explode('-',$val))) {
                return $val;
            }
        }
    }

    /**
     * Does the element consider the data to be empty
     * Used in isempty validation rule
     *
     * @param   array  $data           data to test against
     * @param   int    $repeatCounter  repeat group #
     *
     * @return  bool
     */

    public function dataConsideredEmpty($data, $repeatCounter) {

        $data = str_replace('-', ',', $data);

        if (strstr($data, ',')) {
            $data = explode(',', $data);
        }

        $data = (array) $data;

        foreach ($data as $d) {
            if (trim($d) == '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns javascript which creates an instance of the class defined in formJavascriptClass()
     *
     * @param   int  $repeatCounter  Repeat group counter
     *
     * @return  array
     */

    public function elementJavascript($repeatCounter)
    {
        $id = $this->getHTMLId($repeatCounter);
        $opts = $this->getElementJSOptions($repeatCounter);

        return array('FbYears', $id, $opts);
    }

    /**
     * Returns javascript which creates an instance of the class defined in formJavascriptClass()
     *
     * @param   int  $repeatCounter  Repeat group counter
     *
     * @return  array
     */
    public function renderListData_csv($data, &$thisRow) {
        return $this->renderListData($data, $thisRow);
    }

    /**
     * Shows the data formatted for the list view
     *
     * @param   string    $data      Elements data
     * @param   stdClass  &$thisRow  All the data in the lists current row
     * @param   array     $opts      Rendering options
     *
     * @return  string	formatted value
     */
    public function renderListData($data, stdClass &$thisRow, $opts = array()) {
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        $groupModel = $this->getGroup();
        $data = $groupModel->isJoin() ? FabrikWorker::JSONtoData($data, true) : array($data);
        $data = (array) $data;
        $data = json_encode($data);

        return parent::renderListData($data, $thisRow, $opts);
    }

    /**
     * Format a date based on list age/date format options
     *
     * @param   string  $d  Date
     *
     * @since   3.0.9
     *
     * @return string|number
     */
    private function listFormat($d) {

        $params = $this->getParams();
        $fta = $params->get('list_age_format', 'no');

        list($year) = explode('-', $d);
        $dYear = $year;

        $dateDisplay = $dYear;

        if ($fta == 'no') {
            return $dateDisplay;
        }

        return '';
    }

    /**
     * Used by radio and dropdown elements to get a dropdown list of their unique
     * unique values OR all options - based on filter_build_method
     *
     * @param   bool    $normal     Do we render as a normal filter or as an advanced search filter
     * @param   string  $tableName  Table name to use - defaults to element's current table
     * @param   string  $label      Field to use, defaults to element name
     * @param   string  $id         Field to use, defaults to element name
     * @param   bool    $incjoin    Include join
     *
     * @return  array  text/value objects
     */

    public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true) {
        $rows = parent::filterValueList($normal, $tableName, $label, $id, $incjoin);
        $return = array();

        foreach ($rows as &$row) {
            $txt = $this->listFormat($row->text);

            if ($txt !== '') {
                $row->text = strip_tags($txt);
            }
            // Ensure unique values
            if (!array_key_exists($row->text, $return)) {
                $return[$row->text] = $row;
            }
        }

        return array_values($return);
    }
    /**
     * Get the list filter for the element
     *
     * @param   int   $counter  Filter order
     * @param   bool  $normal   Do we render as a normal filter or as an advanced search filter
     * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
     *
     * @return  string	Filter html
     */
    public function getFilter($counter = 0, $normal = true, $container = '') {
        $params = $this->getParams();
        $element = $this->getElement();

        if ($element->filter_type === 'dropdown' && $params->get('list_filter_layout', 'individual') === 'day_mont_year') {
            $layout = $this->getLayout('filter-select-day-month-year');
            $elName = $this->getFullName(true, false);
            $layoutData = new stdClass;
            $layoutData->name = $this->filterName($counter, $normal);
            $layoutData->years =  $this->_yearOptions();
            $layoutData->default = (array) $this->getDefaultFilterVal($normal, $counter);
            $layoutData->elementName = $this->getFullName(true, false);
            $this->filterDisplayValues = array($layoutData->default);

            $return = array();
            $return[] = $layout->render($layoutData);
            $return[] = $normal ? $this->getFilterHiddenFields($counter, $elName, false, $normal) : $this->getAdvancedFilterHiddenFields();

            return implode("\n", $return);
        } else {
            return parent::getFilter($counter, $normal);
        }
    }

    /**
     * This builds an array containing the filters value and condition
     * when using a ranged search
     *
     * @param   array   $value      Initial values
     * @param   string  $condition  Filter condition e.g. BETWEEN
     *
     * @return  array  (value condition)
     */

    protected function getRangedFilterValue($value, $condition = '') {
        $db = FabrikWorker::getDbo();
        $element = $this->getElement();

        if ($element->filter_type === 'range' || strtoupper($condition) === 'BETWEEN') {
            if (strtotime($value[0]) > strtotime($value[1])) {
                $tmp_value = $value[0];
                $value[0] = $value[1];
                $value[1] = $tmp_value;
            }

            if (is_numeric($value[0]) && is_numeric($value[1])) {
                $value = $value[0] . ' AND ' . $value[1];
            } else {
                $today = $this->date;
                $thisMonth = $today->format('m');
                $thisDay = $today->format('d');

                // Set start date today's month/day of start year
                $startYear = JFactory::getValue($value[0]);
                $startDate = JFactory::getDate();
                $startDate->setDate($startYear, $thisMonth, $thisDay)->setTime(0, 0, 0);
                $value[0] = $startDate->toSql();

                // Set end date to today's month/day of year after end year (means search on age between 35 & 35 returns results)
                $endYear = JFactory::getDate($value[1])->format('Y');
                $endDate = JFactory::getDate();
                $endDate->setDate($endYear + 1, $thisMonth, $thisDay)->setTime(23, 59, 59);
                $value[1] = $endDate->toSql();

                $value = $db->quote($value[0]) . ' AND ' . $db->quote($value[1]);
            }

            $condition = 'BETWEEN';
        } else {
            if (is_array($value) && !empty($value)) {
                foreach ($value as &$v) {
                    $v = $db->quote($v);
                }

                $value = ' (' . implode(',', $value) . ')';
            }

            $condition = 'IN';
        }

        return array($value, $condition);
    }

    /**
     * Build the filter query for the given element.
     * Can be overwritten in plugin - e.g. see checkbox element which checks for partial matches
     *
     * @param   string  $key            element name in format `tablename`.`elementname`
     * @param   string  $condition      =/like etc.
     * @param   string  $value          search string - already quoted if specified in filter array options
     * @param   string  $originalValue  original filter value without quotes or %'s applied
     * @param   string  $type           filter type advanced/normal/prefilter/search/querystring/searchall
     * @param   string  $evalFilter     evaled
     *
     * @return  string    sql query part e,g, "key = value"
     * @throws Exception
     */
    public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal', $evalFilter = '0') {
        $params = $this->getParams();
        $element = $this->getElement();

        if ($type === 'prefilter' || $type === 'menuPrefilter') {
            switch ($condition) {
                case 'earlierthisyear':
                    throw new UnexpectedValueException('The birthday element can not deal with "Earlier This Year" prefilters');
                    break;
                case 'laterthisyear':
                    throw new UnexpectedValueException('The birthday element can not deal with "Later This Year" prefilters');
                    break;
                case 'today':
                    $search = array(date('Y'), date('n'), date('j'));
                    return $this->_dayMonthYearFilterQuery($key, $search);
                    break;
                case 'yesterday':
                    $today = new DateTime();
                    $today->sub(new DateInterval('P1D'));
                    $search = array('', $today->format('n'), $today->format('j'));
                    return $this->_dayMonthYearFilterQuery($key, $search);
                    break;
                case 'tomorrow':
                    $today = new DateTime();
                    $today->add(new DateInterval('P1D'));
                    $search = array('', $today->format('n'), $today->format('j'));
                    return $this->_dayMonthYearFilterQuery($key, $search);
                case 'thismonth':
                    $search = array('', date('n'), '');
                    return $this->_dayMonthYearFilterQuery($key, $search);
                    break;
                case 'lastmonth':
                    $today = new DateTime();
                    $today->sub(new DateInterval('P1M'));
                    $search = array('', $today->format('n'), '');
                    return $this->_dayMonthYearFilterQuery($key, $search);
                case 'nextmonth':
                    $today = new DateTime();
                    $today->add(new DateInterval('P1M'));
                    $search = array('', $today->format('n'), '');
                    return $this->_dayMonthYearFilterQuery($key, $search);
                case 'birthday':
                    $search = array('', date('n'), date('j'));
                    return $this->_dayMonthYearFilterQuery($key, $search);
                    break;
            }
        }

        if ($element->filter_type === 'dropdown' && $params->get('list_filter_layout', 'individual') === 'day_mont_year')
        {
            return $this->_dayMonthYearFilterQuery($key, $originalValue);
        }
        else
        {
            $ft = $this->getParams()->get('list_date_format', 'd.m.Y');

            if ($ft === 'd m')
            {
                $value = explode('-', $originalValue);
                array_shift($value);
                $value = implode('-', $value);
                $query = 'DATE_FORMAT(' . $key . ', \'%m-%d\') = ' . $this->_db->q($value);

                return $query;
            }

            $query = parent::getFilterQuery($key, $condition, $value, $originalValue, $type, $evalFilter);

            return $query;
        }
    }

    /**
     * Get the filter query for the day/month/year select filter
     *
     * @param   string  $key            Key name
     * @param   array   $originalValue  Posted filter data
     *
     * @return string
     */
    private function _dayMonthYearFilterQuery($key, $originalValue)
    {
        $search = array();

        foreach ($originalValue as $i => $val)
        {
            if ($i <> 0 && strlen($val) === 1)
            {
                $val = '0' . $val;
            }

            $search[] = $val === '' ? '%' : $val;
        }

        $search = implode('-', $search);

        return $key . ' LIKE ' . $this->_db->q($search);
    }

}
