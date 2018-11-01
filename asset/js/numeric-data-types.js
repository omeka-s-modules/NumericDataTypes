var NumericDataTypes = {
    /**
     * Set a date to a value.
     *
     * @param v Value input
     * @param y Year input
     * @param m Month select
     * @param d Day input
     */
    setDateValue : function(v, y, m, d) {
        var year = y.val();;
        var month = m.val();
        var day = d.val();
        if (year && month && day) {
            v.val(`${year}-${month}-${day}`);
        } else if (year && month) {
            v.val(`${year}-${month}`);
        } else if (year) {
            v.val(year);
        } else {
            v.val(null); // must have year
        }
    }
};

$(document).on('o:prepare-value', function(e, type, value) {
    if ('numeric:timestamp' === type) {
        var v = value.find('input[data-value-key="@value"]');
        var y = value.find('input[name="numeric-timestamp-year"]');
        var m = value.find('select[name="numeric-timestamp-month"]');
        var d = value.find('input[name="numeric-timestamp-day"]');
        var matches = /(-?\d+)(-(\d{1,2}))?(-(\d{1,2}))?/.exec(v.val());
        // Set existing year, month, and day during initial load.
        if (matches) {
            y.val(parseInt(matches[1]));
            m.val(matches[3] ? parseInt(matches[3]) : null);
            d.val(matches[5] ? parseInt(matches[5]) : null);
        }
        y.on('input', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d);
        });
        m.on('change', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d);
        });
        d.on('input', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d);
        });
    }
});
