$(document).ready(function() {

/**
 * Add all available numeric strings to the FacetedBrowse form. Legacy fill; see
 * the handler below for when it applies.
 */
const numericAddAll = function(textareaId) {
    const textarea = $(textareaId);
    const rows = $('#show-all-table').data('rows');
    const container = $('.confirm-main');
    const labels = [];
    $.each(rows, (index, row) => {
        labels.push(row.label);
    });
    textarea.val(labels.join("\n"));
    container.animate({
        scrollTop: textarea.closest('.field').offset().top - container.offset().top + container.scrollTop()
    });
};

/**
 * Handle the add all button on a FacetedBrowse that predates the declared
 * target.
 *
 * A current FacetedBrowse fills the field itself, so this stands aside as soon as
 * a declaration is present or the field would be written twice. An older one
 * declares nothing, and then this is the only handler there is.
 */
$(document).on('click', '#add-all', function(e) {
    // Any declaration at all means a current FacetedBrowse owns the click.
    if ($('#show-all').data('addAllMode')) {
        return;
    }
    // Add all according to facet type.
    switch ($('#facet-type-input').val()) {
        case 'date_after':
            numericAddAll('#date-after-values');
            break;
        case 'date_before':
            numericAddAll('#date-before-values');
            break;
        // No list to add to: these are configured with min, max and step.
        case 'value_greater_than':
        case 'value_less_than':
            alert(Omeka.jsTranslate('Cannot add all. This facet is configured with a minimum, maximum and step rather than a list of values.'));
            break;
        case 'duration_greater_than':
            numericAddAll('#duration-greater-than-values');
            break;
        case 'duration_less_than':
            numericAddAll('#duration-less-than-values');
            break;
        case 'date_in_interval':
            alert(Omeka.jsTranslate('Cannot add all. This facet needs a single date or time per line, but the available values are intervals.'));
            break;
    }
});

});
