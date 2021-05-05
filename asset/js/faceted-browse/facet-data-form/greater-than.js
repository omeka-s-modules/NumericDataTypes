FacetedBrowse.registerFacetAddEditHandler('greater_than', function() {
    $('#greater-than-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('greater_than', function() {
    const propertyId = $('#greater-than-property-id');
    const min = $('#greater-than-min');
    const max = $('#greater-than-max');
    const step = $('#greater-than-step');
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
$(document).on('change', '#greater-than-property-id', function(e) {
    $('#show-all').prop('checked', false);
    $('#show-all-table-container').empty();
});

});
