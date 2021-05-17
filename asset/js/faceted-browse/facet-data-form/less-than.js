FacetedBrowse.registerFacetAddEditHandler('less_than', function() {
    $('#less-than-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('less_than', function() {
    const propertyId = $('#less-than-property-id');
    const min = $('#less-than-min');
    const max = $('#less-than-max');
    const step = $('#less-than-step');
    if (!propertyId.val()) {
        alert(Omeka.jsTranslate('A facet must have a property.'));
    } else {
        return {
            property_id: propertyId.val(),
            min: min.val(),
            max: max.val(),
            step: step.val(),
            values: $('#date-after-values').val()
        };
    }
});

$(document).ready(function() {

// Clear show all during certain interactions.
$(document).on('change', '#less-than-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});
