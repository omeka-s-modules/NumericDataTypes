$(document).ready(function() {

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

// Handle show all values.
$(document).on('click', '#greater-than-show-integer-values', function(e) {
    const timestampValues = $('#greater-than-integer-values');
    if (this.checked) {
        $.get(timestampValues.data('integerValuesUrl'), {
            property_id: $('#greater-than-property-id').val(),
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
$(document).on('change', '#greater-than-property-id', function(e) {
    $('#greater-than-show-integer-values').prop('checked', false);
    $('#greater-than-integer-values').empty();
});

});
