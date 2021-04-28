$(document).ready(function() {

FacetedBrowse.registerFacetAddEditHandler('date_after', function() {
    $('#date-after-property-id').chosen({
        allow_single_deselect: true,
    });
});
FacetedBrowse.registerFacetSetHandler('date_after', function() {
    const propertyId = $('#date-after-property-id');
    if (!propertyId.val()) {
        alert(Omeka.jsTranslate('A facet must have a property.'));
    } else {
        return {
            property_id: propertyId.val(),
            values: $('#date-after-values').val()
        };
    }
});

// Handle show all values.
$(document).on('click', '#date-after-show-timestamp-values', function(e) {
    const timestampValues = $('#date-after-timestamp-values');
    if (this.checked) {
        $.get(timestampValues.data('timestampValuesUrl'), {
            property_id: $('#date-after-property-id').val(),
            query: $('#category-query').val()
        }, function(data) {
            if (data.length) {
                data.forEach(value => {
                    timestampValues.append(`<tr><td style="width: 90%; padding: 0; border-bottom: 1px solid #dfdfdf;">${value.value}</td><td style="width: 10%; padding: 0; border-bottom: 1px solid #dfdfdf;">${value.value_count}</td></tr>`);
                });
            } else {
                timestampValues.append(`<tr><td>${Omeka.jsTranslate('There are no available values.')}</td></tr>`);
            }
        });
    } else {
        timestampValues.empty();
    }
});
// Handle property ID select.
$(document).on('change', '#date-after-property-id', function(e) {
    $('#date-after-show-timestamp-values').prop('checked', false);
    $('#date-after-timestamp-values').empty();
});

});
