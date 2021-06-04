FacetedBrowse.registerFacetApplyStateHandler('value_less_than', function(facet, facetState) {
    const thisFacet = $(facet);
    const thisRange = thisFacet.find(`input.value-less-than`);
    thisRange.val(facetState);
    thisFacet.find('.value-less-than-reset').show();
    thisFacet.find('.value-less-than-value').text(thisRange.val());
});

$(document).ready(function() {

const container = $('#container');

container.on('input', '.value-less-than', function(e) {
    const thisRange = $(this);
    const facet = thisRange.closest('.facet');
    const facetData = facet.data('facetData');
    facet.find('.value-less-than-reset').show();
    facet.find('.value-less-than-value').text(thisRange.val());
    FacetedBrowse.setFacetState(
        facet.data('facetId'),
        thisRange.val(),
        `numeric[int][lt][pid]=${facetData.property_id}&numeric[int][lt][val]=${encodeURIComponent(thisRange.val())}`
    );
    FacetedBrowse.triggerStateChange();
});
container.on('click', '.value-less-than-reset', function(e) {
    const thisButton = $(this);
    const facet = thisButton.closest('.facet');
    const facetData = facet.data('facetData');
    const range = facet.find('.value-less-than');
    facet.find('.value-less-than-reset').hide();
    facet.find('.value-less-than-value').text('');
    range.val('');
    FacetedBrowse.setFacetState(facet.data('facetId'), '');
    FacetedBrowse.triggerStateChange();
});

});
