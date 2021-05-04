$(document).ready(function() {

const container = $('#container');

container.on('change', '.greater-than', function(e) {
    const thisRange = $(this);
    const facet = thisRange.closest('.facet');
    const facetData = facet.data('facetData');
    FacetedBrowse.setFacetQuery(
        facet.data('facetId'),
        `numeric[int][gt][pid]=${facetData.property_id}&numeric[int][gt][val]=${encodeURIComponent(thisRange.val())}`
    );
});
container.on('input', '.greater-than', function(e) {
    const thisRange = $(this);
    const facet = thisRange.closest('.facet');
    facet.find('.greater-than-reset').show();
    facet.find('.greater-than-value').text(thisRange.val());
});
container.on('click', '.greater-than-reset', function(e) {
    const thisButton = $(this);
    const facet = thisButton.closest('.facet');
    const facetData = facet.data('facetData');
    const range = facet.find('.greater-than');
    facet.find('.greater-than-reset').hide();
    facet.find('.greater-than-value').text('');
    range.val('');
    FacetedBrowse.setFacetQuery(facet.data('facetId'), '');
});

});
