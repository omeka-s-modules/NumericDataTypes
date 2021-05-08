$(document).ready(function() {

const container = $('#container');

container.on('change', '.date-after-value', function(e) {
    const thisSelect = $(this);
    const facet = thisSelect.closest('.facet');
    const facetData = facet.data('facetData');
    FacetedBrowse.setFacetState(
        facet.data('facetId'),
        `numeric[ts][gt][pid]=${facetData.property_id}&numeric[ts][gt][val]=${encodeURIComponent(thisSelect.val())}`
    );
    FacetedBrowse.triggerFacetStateChange();
});

});
