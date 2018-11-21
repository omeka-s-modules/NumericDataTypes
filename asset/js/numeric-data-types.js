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
        var yearMatches = /^(-?)(\d+)$/.exec(y.val());
        var yearSign = yearMatches ? yearMatches[1] : null;
        var year = yearMatches ? yearMatches[2] : null;
        var month = m.val();
        var day = d.val();
        var hour = h.val();
        var minute = mi.val();
        var second = s.val();
        if (year && month && day && hour && minute && second) {
            v.val(`${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}:${second.padStart(2, '0')}`);
        } else if (year && month && day && hour && minute) {
            v.val(`${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}:${minute.padStart(2, '0')}`);
        } else if (year && month && day && hour) {
            v.val(`${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}T${hour.padStart(2, '0')}`);
        } else if (year && month && day) {
            v.val(`${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`);
        } else if (year && month) {
            v.val(`${yearSign}${year.padStart(4, '0')}-${month.padStart(2, '0')}`);
        } else if (year) {
            v.val(`${yearSign}${year.padStart(4, '0')}`);
        } else {
            v.val(null); // must have year
        }
    },
    /**
     * Set a duration to a value.
     *
     * @param v Value input
     * @param y Years input
     * @param m Months input
     * @param w Weeks input
     * @param d Days input
     * @param mi Minutes input
     * @param s Seconds input
     */
    setDurationValue : function(v, y, m, d, h, i, s) {
        var years = y.val();
        var months = m.val();
        var days = d.val();
        var hours = h.val();
        var minutes = i.val();
        var seconds = s.val();
        var value = 'P';
        if (years) {
            value = `${value}${years}Y`;
        }
        if (months) {
            value = `${value}${months}M`;
        }
        if (days) {
            value = `${value}${days}D`;
        }
        if (hours || minutes || seconds) {
            value = `${value}T`;
        }
        if (hours) {
            value = `${value}${hours}H`;
        }
        if (minutes) {
            value = `${value}${minutes}M`;
        }
        if (seconds) {
            value = `${value}${seconds}S`;
        }
        v.val(value);
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
        var matches = /^(-?\d{4,})(-(\d{2}))?(-(\d{2}))?(T(\d{2}))?(:(\d{2}))?(:(\d{2}))?$/.exec(v.val());
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
    if ('numeric:duration' === type) {
        var v = value.find('input[data-value-key="@value"]');
        var y = value.find('input[name="numeric-duration-years"]');
        var m = value.find('input[name="numeric-duration-months"]');
        var d = value.find('input[name="numeric-duration-days"]');
        var h = value.find('input[name="numeric-duration-hours"]');
        var i = value.find('input[name="numeric-duration-minutes"]');
        var s = value.find('input[name="numeric-duration-seconds"]');
        // Match against ISO 8601, allowing for reduced precision.
        var matches = /^P(\d+Y)?(\d+M)?(\d+D)?(T(\d+H)?(\d+M)?(\d+S)?)?$/.exec(v.val());
        if (matches) {
            // Set existing values during initial load.
            y.val(matches[1] ? parseInt(matches[1].slice(0, -1)) : null);
            m.val(matches[2] ? parseInt(matches[2].slice(0, -1)) : null);
            d.val(matches[3] ? parseInt(matches[3].slice(0, -1)) : null);
            h.val(matches[5] ? parseInt(matches[5].slice(0, -1)) : null);
            i.val(matches[6] ? parseInt(matches[6].slice(0, -1)) : null);
            s.val(matches[7] ? parseInt(matches[7].slice(0, -1)) : null);
        }
        y.add(m).add(d).add(h).add(i).add(s).on('input', function(e) {
            NumericDataTypes.setDurationValue(v, y, m, d, h, i, s);
        });
    }
});

$(function() {
    $(document).find('.timestamp-toggle-time').on('click', function(e) {
        // Toggle visibility of time inputs.
        e.preventDefault();
        $(this).closest('.timestamp-datetime-inputs').find('.timestamp-time-inputs').toggle();
    });
});
