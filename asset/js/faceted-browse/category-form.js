$(document).ready(function() {

/**
 * Add all available numeric strings to the FacetedBrowse form.
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

// Handle add all button.
$(document).on('click', '#add-all', function(e) {
    // Add all according to facet type.
    switch ($('#facet-type-input').val()) {
        case 'date_after':
            numericAddAll('#date-after-values');
            break;
        case 'date_before':
            numericAddAll('#date-before-values');
            break;
        case 'value_greater_than':
            alert('Cannot add all');
            break;
        case 'value_less_than':
            alert('Cannot add all');
            break;
        case 'duration_greater_than':
            numericAddAll('#duration-greater-than-values');
            break;
        case 'duration_less_than':
            numericAddAll('#duration-less-than-values');
            break;
        case 'date_in_interval':
            numericAddAll('#date-in-interval-values');
            break;
    }
});

});
