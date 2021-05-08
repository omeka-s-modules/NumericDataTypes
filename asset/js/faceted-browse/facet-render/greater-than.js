$(document).ready(function() {

const container = $('#container');

container.on('input', '.greater-than', function(e) {
    const thisRange = $(this);
    const facet = thisRange.closest('.facet');
    const facetData = facet.data('facetData');
    facet.find('.greater-than-reset').show();
    facet.find('.greater-than-value').text(thisRange.val());
    FacetedBrowse.setFacetState(
        facet.data('facetId'),
        `numeric[int][gt][pid]=${facetData.property_id}&numeric[int][gt][val]=${encodeURIComponent(thisRange.val())}`
    );
    FacetedBrowse.triggerFacetStateChange();
});
container.on('click', '.greater-than-reset', function(e) {
    const thisButton = $(this);
    const facet = thisButton.closest('.facet');
    const facetData = facet.data('facetData');
    const range = facet.find('.greater-than');
    facet.find('.greater-than-reset').hide();
    facet.find('.greater-than-value').text('');
    range.val('');
    FacetedBrowse.setFacetState(facet.data('facetId'), '');
    FacetedBrowse.triggerFacetStateChange();
});

});
