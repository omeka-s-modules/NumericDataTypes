FacetedBrowse.registerFacetApplyStateHandler('less_than', function(facet, facetState) {
    const thisFacet = $(facet);
    const thisRange = thisFacet.find(`input.less-than`);
    thisRange.val(facetState);
    thisFacet.find('.less-than-reset').show();
    thisFacet.find('.less-than-value').text(thisRange.val());
});

$(document).ready(function() {

const container = $('#container');

container.on('input', '.less-than', function(e) {
    const thisRange = $(this);
    const facet = thisRange.closest('.facet');
    const facetData = facet.data('facetData');
    facet.find('.less-than-reset').show();
    facet.find('.less-than-value').text(thisRange.val());
    FacetedBrowse.setFacetState(
        facet.data('facetId'),
        thisRange.val(),
        `numeric[int][lt][pid]=${facetData.property_id}&numeric[int][lt][val]=${encodeURIComponent(thisRange.val())}`
    );
    FacetedBrowse.triggerFacetStateChange();
});
container.on('click', '.less-than-reset', function(e) {
    const thisButton = $(this);
    const facet = thisButton.closest('.facet');
    const facetData = facet.data('facetData');
    const range = facet.find('.less-than');
    facet.find('.less-than-reset').hide();
    facet.find('.less-than-value').text('');
    range.val('');
    FacetedBrowse.setFacetState(facet.data('facetId'), '');
    FacetedBrowse.triggerFacetStateChange();
});

});
