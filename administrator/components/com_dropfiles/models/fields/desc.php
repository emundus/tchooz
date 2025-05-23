<?php

defined('JPATH_PLATFORM') || die;

/**
 * Class JFormFieldDesc
 */
class JFormFieldDesc extends JFormField
{
    /**
     * The form field type.
     *
     * @var string
     */
    protected $type = 'desc';

    /**
     * The number of rows in textarea.
     *
     * @var mixed
     */
    protected $rows;

    /**
     * The number of columns in textarea.
     *
     * @var mixed
     */
    protected $columns;

    /**
     * The maximum number of characters in textarea.
     *
     * @var mixed
     */
    protected $maxlength;

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param string $name The property name for which to the the value.
     *
     * @return mixed  The property value or null.
     *
     * @since 3.2
     */
    public function __get($name)
    {
        switch ($name) {
            case 'rows':
            case 'columns':
            case 'maxlength':
                return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @param string $name  The property name for which to the the value.
     * @param mixed  $value The value of the property.
     *
     * @return void
     *
     * @since 3.2
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'rows':
            case 'columns':
            case 'maxlength':
                $this->$name = (int)$value;
                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Method to attach a JForm object to the field.
     *
     * @param SimpleXMLElement $element The SimpleXMLElement object representing the <field />
     * tag for the form field object.
     * @param mixed            $value   The form field value to validate.
     * @param string           $group   The field name group control value.
     *             This acts as as an array container for the field.
     *             For example if the field has name="foo" and the group value is set to "bar" then the
     *                                                  full field name would end up being "bar[foo]".
     *
     * @return boolean  True on success.
     *
     * @see   JFormField::setup()
     * @since 3.2
     */
    public function setup(SimpleXMLElement $element, $value, $group = null)
    {
        $return = parent::setup($element, $value, $group);

        if ($return) {
            $this->rows = isset($this->element['rows']) ? (int)$this->element['rows'] : false;
            $this->columns = isset($this->element['cols']) ? (int)$this->element['cols'] : false;
            $this->maxlength = isset($this->element['maxlength']) ? (int)$this->element['maxlength'] : false;
        }

        return $return;
    }

    /**
     * Method to get the textarea field input markup.
     * Use the rows and columns attributes to specify the dimensions of the area.
     *
     * @return string  The field input markup.
     *
     * @since 11.1
     */
    protected function getInput()
    {
        // Translate placeholder text
        $hint = $this->translateHint ? JText::_($this->hint) : $this->hint;

        // Initialize some field attributes.
        $class = !empty($this->class) ? ' class="' . $this->class . '"' : '';
        $disabled = $this->disabled ? ' disabled' : '';
        $readonly = $this->readonly ? ' readonly' : '';
        $columns = $this->columns ? ' cols="' . $this->columns . '"' : '';
        $rows = $this->rows ? ' rows="' . $this->rows . '"' : '';
        $required = $this->required ? ' required aria-required="true"' : '';
        $hint = $hint ? ' placeholder="' . $hint . '"' : '';
        $autocomplete = !$this->autocomplete ? ' autocomplete="off"' : ' autocomplete="' . $this->autocomplete . '"';
        $autocomplete = $autocomplete === ' autocomplete="on"' ? '' : $autocomplete;
        $autofocus = $this->autofocus ? ' autofocus' : '';
        $spellcheck = $this->spellcheck ? '' : ' spellcheck="false"';
        $maxlength = $this->maxlength ? ' maxlength="' . $this->maxlength . '"' : '';

        // Initialize JavaScript field attributes.
        $onchange = $this->onchange ? ' onchange="' . $this->onchange . '"' : '';
        $onclick = $this->onclick ? ' onclick="' . $this->onclick . '"' : '';

        // Including fallback code for HTML5 non supported browsers.
        JHtml::_('jquery.framework');
        JHtml::_('script', 'system/html5fallback.js', array('version' => 'auto', 'relative' => true));
        //JHtml::_('script', 'editors/tinymce/tinymce.min.js', false, true);
        $params = JComponentHelper::getParams('com_dropfiles');

        $output = '<textarea name="' . $this->name . '" id="' . $this->id . '"' . $columns . $rows . $class
            . $hint . $disabled . $readonly . $onchange . $onclick . $required . $autocomplete . $autofocus
            . $spellcheck . $maxlength . ' >'
            . htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '</textarea>';

        if ($params->get('usereditor', 0)) {
            $output .= "\t<script type=\"text/javascript\">
                    tinymce.init({
                        selector: '#" . $this->id . "',
                        menubar: false,
                        mode : \"specific_textareas\",
                        autosave_restore_when_empty: false,
                        schema: \"html5\",
                        //toolbar1: \"bold italics underline strikethrough | undo redo | bullist numlist\",
                        // Cleanup/Output
//						inline_styles : true,
//						gecko_spellcheck : true,
                        setup: function (editor) {
                                editor.on('change', function () {
                                    tinymce.triggerSave();
                                });
                            }
                    });
                </script>";
        }
        return $output;
    }
}
