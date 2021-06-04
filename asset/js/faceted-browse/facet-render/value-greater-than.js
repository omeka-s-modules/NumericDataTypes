FacetedBrowse.registerFacetApplyStateHandler('value_greater_than', function(facet, facetState) {
    const thisFacet = $(facet);
    const thisRange = thisFacet.find(`input.value-greater-than`);
    thisRange.val(facetState);
    thisFacet.find('.value-greater-than-reset').show();
    thisFacet.find('.value-greater-than-value').text(thisRange.val());
});

$(document).ready(function() {

const container = $('#container');

container.on('input', '.value-greater-than', function(e) {
    const thisRange = $(this);
    const facet = thisRange.closest('.facet');
    const facetData = facet.data('facetData');
    facet.find('.value-greater-than-reset').show();
    facet.find('.value-greater-than-value').text(thisRange.val());
    FacetedBrowse.setFacetState(
        facet.data('facetId'),
        thisRange.val(),
        `numeric[int][gt][pid]=${facetData.property_id}&numeric[int][gt][val]=${encodeURIComponent(thisRange.val())}`
    );
    FacetedBrowse.triggerStateChange();
});
container.on('click', '.value-greater-than-reset', function(e) {
    const thisButton = $(this);
    const facet = thisButton.closest('.facet');
    const facetData = facet.data('facetData');
    const range = facet.find('.value-greater-than');
    facet.find('.value-greater-than-reset').hide();
    facet.find('.value-greater-than-value').text('');
    range.val('');
    FacetedBrowse.setFacetState(facet.data('facetId'), '');
    FacetedBrowse.triggerStateChange();
});

});
