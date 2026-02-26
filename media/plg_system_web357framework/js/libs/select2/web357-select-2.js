jQuery(function ($) {

    var select2Options = Joomla.getOptions('web35.select2');

    /* add select all option */
    const addSelectAll = function (matches) {
        if (matches.length === 0) return matches; // Return early if no matches

        // Add "Select all matches" option at the beginning
        return [{
            id: 'selectAll',
            text: select2Options.selectAllText,
            matchIds: matches.map((match) => match.id)
        }, ...matches,];
    };

    /* select all functionality */
    const handleSelection = (event, selectElement) => {
        if (event.params.data.id === 'selectAll') {
            $(selectElement).val(event.params.data.matchIds).trigger('change').select2('close');
        }
    };

    /**
     * Initialize Select2 for elements with the .web357-select2 class or [data-web357-select2-options] attribute
     * - Joomla 3 has chosen with the same functionality, but you can override it with .web357-select2
     * - In Joomla fields doesn't support data-* attributes, that's why you can use `Select2multiselectField`
     * - If you like to keep Joomla 3 functionality and add this to Joomla 4,5 use [data-web357-select2-options] attribute
     */
    $('[data-web357-select2-options],.web357-select2 ').each(function () {

        try {
            /* this destroys chozen instance in Joomla3 */
            $(this).chosen('destroy');
        } catch (e) {

        }

        const $select = $(this);
        const options = $select.data('web357-select2-options') || [];

        options.dropdownCssClass = select2Options.dropdownClass;

        if (!options.width) {
            options.width = '100%';
        }

        if (options.displayShowAll) {
            options.sorter = addSelectAll;
        }

        if (options.allowClear && typeof options.placeholder === 'undefined') {
            options.placeholder = select2Options.selectOptionText;
        }

        // Initialize Select2 with custom options
        $select.select2(options);

        // Add Joomla version class to the Select2 container
        $select.data('select2').$container.addClass(select2Options.containerClass);

        // Attach event listener for "Select All" functionality
        $select.on('select2:select', (event) => handleSelection(event, $select));
    });
});
