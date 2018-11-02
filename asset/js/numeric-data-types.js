var NumericDataTypes = {
    /**
     * Set a date to a value.
     *
     * @param v Value input
     * @param y Year input
     * @param m Month select
     * @param d Day input
     */
    setDateValue : function(v, y, m, d, h, mi, s) {
        var year = y.val();;
        var month = m.val();
        var day = d.val();
        var hour = h.val();
        var minute = mi.val();
        var second = s.val();
        if (year && month && day && hour && minute && second) {
            v.val(`${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}:${second.padStart(2, '0')}`);
        } else if (year && month && day && hour && minute) {
            v.val(`${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`);
        } else if (year && month && day && hour) {
            v.val(`${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}`);
        } else if (year && month && day) {
            v.val(`${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`);
        } else if (year && month) {
            v.val(`${year}-${month.padStart(2, '0')}`);
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
        var h = value.find('input[name="numeric-timestamp-hour"]');
        var mi = value.find('input[name="numeric-timestamp-minute"]');
        var s = value.find('input[name="numeric-timestamp-second"]');
        // Match against ISO 8601, allowing for reduced accuracy.
        var matches = /(-?\d+)(-(\d{2}))?(-(\d{2}))?(T(\d{2}))?(:(\d{2}))?(:(\d{2}))?/.exec(v.val());
        if (matches) {
            // Set existing year, month, day, hour, minute, second during
            // initial load.
            y.val(parseInt(matches[1]));
            m.val(matches[3] ? parseInt(matches[3]) : null);
            d.val(matches[5] ? parseInt(matches[5]) : null);
            h.val(matches[7] ? parseInt(matches[7]) : null);
            mi.val(matches[9] ? parseInt(matches[9]) : null);
            s.val(matches[11] ? parseInt(matches[11]) : null);
        }
        y.on('input', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d, h, mi, s);
        });
        m.on('change', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d, h, mi, s);
        });
        d.on('input', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d, h, mi, s);
        });
        h.on('input', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d, h, mi, s);
        });
        mi.on('change', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d, h, mi, s);
        });
        s.on('change', function(e) {
            NumericDataTypes.setDateValue(v, y, m, d, h, mi, s);
        });

        // By default, show time inputs only if there's an hour.
        var timeInputs = value.find('.timestamp-time-inputs');
        h.val() ? timeInputs.show() : timeInputs.hide();
    }
});

$(function() {
    $(document).find('.timestamp-toggle-time').on('click', function(e) {
        // Toggle visibility of time inputs.
        e.preventDefault();
        $(this).closest('.timestamp-datetime-inputs').find('.timestamp-time-inputs').toggle();
    });
});
