//Call a function whos name is in a variable
//_____http://stackoverflow.com/questions/359788/how-to-execute-a-javascript-function-when-i-have-its-name-as-a-string
function executeFunctionByName(functionName, context /*, args */) {
    var args = Array.prototype.slice.call(arguments).splice(2);
    var namespaces = functionName.split(".");
    var func = namespaces.pop();
    for (var i = 0; i < namespaces.length; i++) {
        context = context[namespaces[i]];
    }
    return context[func].apply(this, args);
}

//Get selected text
//_____http://motyar.blogspot.fi/2010/02/get-user-selected-text-with-jquery-and.html
function getSelected() {
    if (window.getSelection) {
        return window.getSelection();
    }
    else if (document.getSelection) {
        return document.getSelection();
    }
    else {
        var selection = document.selection && document.selection.createRange();
        if (selection.text) {
            return selection.text;
        }
        return false;
    }
}

//Save the selected text
function saveSelection() {
    if (window.getSelection) {
        sel = window.getSelection();
        if (sel.getRangeAt && sel.rangeCount) {
            var ranges = [];
            for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                ranges.push(sel.getRangeAt(i));
            }
            return ranges;
        }
    } else if (document.selection && document.selection.createRange) {
        return document.selection.createRange();
    }
    console.log("can't get selection.");
    return null;
}


//Restore a text selection
function restoreSelection(savedSel) {
    if (savedSel) {
        if (window.getSelection) {
            sel = window.getSelection();
            sel.removeAllRanges();
            for (var i = 0, len = savedSel.length; i < len; ++i) {
                sel.addRange(savedSel[i]);
            }
        } else if (document.selection && savedSel.select) {
            savedSel.select();
        }
    }
}


/**
 * author Remy Sharp
 * url http://remysharp.com/2009/01/26/element-in-view-event-plugin/
 */
(function ($) {
    function getViewportHeight() {
        var height = window.innerHeight; // Safari, Opera
        var mode = document.compatMode;

        if ((mode || !$.support.boxModel)) { // IE, Gecko
            height = (mode == 'CSS1Compat') ?
                document.documentElement.clientHeight : // Standards
                document.body.clientHeight; // Quirks
        }

        return height;
    }

    $(window).scroll(function () {
        var vpH = getViewportHeight(),
            scrolltop = (document.documentElement.scrollTop ?
                document.documentElement.scrollTop :
                document.body.scrollTop),
            elems = [];

        // naughty, but this is how it knows which elements to check for
        $.each($.cache, function () {
            if (this.events && this.events.inview) {
                elems.push(this.handle.elem);
            }
        });

        if (elems.length) {
            $(elems).each(function () {
                var $el = $(this),
                    top = $el.offset().top,
                    height = $el.height(),
                    inview = $el.data('inview') || false;

                if (scrolltop > (top + height) || scrolltop + vpH < top) {
                    if (inview) {
                        $el.data('inview', false);
                        $el.trigger('inview', [false]);
                    }
                } else if (scrolltop < (top + height)) {
                    if (!inview) {
                        $el.data('inview', true);
                        $el.trigger('inview', [true]);
                    }
                }
            });
        }
    });

    // kick the event to pick up any elements already in view.
    // note however, this only works if the plugin is included after the elements are bound to 'inview'
    $(function () {
        $(window).scroll();
    });
})(jQuery);


//A general purpose debouncer extension
//_____via http://www.hnldesign.nl/blog/debouncing-events-with-jquery/
var deBouncer = function ($, cf, of, interval) {
    // deBouncer by hnldesign.nl
    // based on code by Paul Irish and the original debouncing function from John Hann
    // http://unscriptable.com/index.php/2009/03/20/debouncing-javascript-methods/
    var debounce = function (func, threshold, execAsap) {
        var timeout;

        return function debounced() {
            var obj = this, args = arguments;

            function delayed() {
                if (!execAsap)
                    func.apply(obj, args);
                timeout = null;
            }

            if (timeout)
                clearTimeout(timeout);
            else if (execAsap)
                func.apply(obj, args);

            timeout = setTimeout(delayed, threshold || interval);
        };
    };
    jQuery.fn[cf] = function (fn) {
        return fn ? this.bind(of, debounce(fn)) : this.trigger(cf);
    };
};


//Strip leading and trailing whitespace from a string
function trim(strText) {
    // this will get rid of leading spaces
    while (strText.substring(0, 1) == ' ')
        strText = strText.substring(1, strText.length);

    // this will get rid of trailing spaces
    while (strText.substring(strText.length - 1, strText.length) == ' ')
        strText = strText.substring(0, strText.length - 1);

    return strText;
}


//Extend jquery so we can easily center an element
//_____via stackflow: http://stackoverflow.com/questions/210717/using-jquery-to-center-a-div-on-the-screen
jQuery.fn.center = function (horz, vert) {
    this.css("position", "absolute");
    if (vert == true) {
        this.css("top", Math.max(0, (($(window).height() - this.outerHeight()) / 2) + $(window).scrollTop()) + "px");
    }
    if (horz == true) {
        this.css("left", Math.max(0, (($(window).width() - this.outerWidth()) / 2) + $(window).scrollLeft()) + "px");
    }
    return this;
}


//A jquery selector extension to determine if an element is visible in the viewport
//_____via http://remysharp.com/2009/01/26/element-in-view-event-plugin/
$.extend($.expr[':'], {
    inView: function (a) {
        var st = (document.documentElement.scrollTop || document.body.scrollTop),
            ot = $(a).offset().top,
            wh = (window.innerHeight && window.innerHeight < $(window).height()) ? window.innerHeight : $(window).height();
        return ot > st && ($(a).height() + ot) < (st + wh);
    }
});


//Display something in the global dropdown message box
function showMessage(text, status, timeout) {
    clearTimeout(msgtimer);
    //closeMessage();
    //$('#divMessageBox').slideUp("normal", function(){ $('#divMessageBox').remove(); });

    if (isMessageBoxVisible()) {
        return changeMessage(text, status, timeout);
    }

    $('.msganchor').after('<div style="display:none;" id="divMessageBox"><p id="messagebox"></p></div>');

    $('#messagebox').empty();
    $('#messagebox').removeClass('msggood');
    $('#messagebox').removeClass('msgbad');
    if (platform == "mobile") {
        $('#messagebox').append(text);
    } else {
        $('#messagebox').append('<a id="btnMessageBoxClose" title="Close message box." onclick="javascript:closeMessage();"><img class="icon-collapse-up" src="/images/blank.gif" /></a>' + text);
    }

    if (status == false || status == "false") {
        $('#messagebox').addClass('msgbad');
    } else if (status == "warning") {
        $('#messagebox').addClass('msgwarn');
    } else {
        $('#messagebox').addClass('msggood');
    }

    if (platform == "mobile") {
        alert(text);
    } else {
        $('#divMessageBox').center(true, false);
        $('#divMessageBox').slideDown();

        msgtimer = setTimeout(function () {
                closeMessage();
            },
            (timeout * 1000));
    }

}

//See if messagebox is showing
function isMessageBoxVisible() {
    return ($('#messagebox').length > 0);
}

//Change message in box
function changeMessage(text, status, timeout) {
    clearTimeout(msgtimer);
    $('#messagebox').empty();
    $('#messagebox').removeClass('msggood');
    $('#messagebox').removeClass('msgbad');
    $('#messagebox').removeClass('msgwarn');

    $('#messagebox').html('<a id="btnMessageBoxClose" title="Close message box." onclick="javascript:closeMessage();"><img class="icon-collapse-up" src="/images/blank.gif" /></a>' + text);

    if (status == false || status == "false") {
        $('#messagebox').addClass('msgbad');
    } else if (status == "warning") {
        $('#messagebox').addClass('msgwarn');
    } else {
        $('#messagebox').addClass('msggood');
    }

    msgtimer = setTimeout(function () {
            closeMessage();
        },
        (timeout * 1000));
}

//Close the global message box
function closeMessage() {
    $('#divMessageBox').slideUp("normal", function () {
        $('#divMessageBox').remove();
    });
    return (false);
}


//Empty and blank string checks
//_____via http://stackoverflow.com/questions/154059/what-is-the-best-way-to-check-for-an-empty-string-in-javascript
function isEmpty(str) {
    return (!str || 0 === str.length);
}
function isBlank(str) {
    return (!str || /^\s*$/.test(str));
}


//Is this object an image?
function isImage(url, type) {
    var type = (typeof type === "undefined") ? false : type;
    if(typeof url === "undefined") {
        console.log("DEBUG: isImage() url is undefined.");
        //return false;
    }

    if (type) {
        if (type.indexOf(systemUrl) == -1 && type.indexOf('image') != -1 && url.indexOf('http') == 0) {
            return true;
        }
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.jpg') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.jpeg') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.png') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.gif') != -1 && url.indexOf('http') == 0) {
        return true;
    }

    return false;
};


//Is it video?
function isVideo(url, type) {
    var type = (typeof type === "undefined") ? false : type;
    if(typeof url === "undefined") return false;

    if (type) {
        if (type.indexOf(systemUrl) == -1 && type.indexOf('video') != -1 && url.indexOf('http') == 0) {
            return true;
        }
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.m4v') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.mp4') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.avi') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.mov') != -1 && url.indexOf('http') == 0) {
        return true;
    }

    return false;
};


//Is it audio?
function isAudio(url, type) {
    var type = (typeof type === "undefined") ? false : type;
    if(typeof url === "undefined") return false;

    if (type) {
        if (type.indexOf(systemUrl) == -1 && type.indexOf('audio') != -1 && url.indexOf('http') == 0) {
            return true;
        }
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.mp3') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.m4a') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.wav') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.ogg') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('.wmv') != -1 && url.indexOf('http') == 0) {
        return true;
    }

    return false;
};


//Is it html content?
function isHtml(url, type) {
    var type = (typeof type === "undefined") ? false : type;
    if(typeof url === "undefined") return false;

    if (type) {
        if (type.indexOf(systemUrl) == -1 && type.indexOf('html') != -1 && url.indexOf('http') == 0) {
            return true;
        }
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('youtube') != -1 && url.indexOf('http') == 0) {
        return true;
    }
    if (url.indexOf(systemUrl) == -1 && url.indexOf('vimeo') != -1 && url.indexOf('http') == 0) {
        return true;
    }

    return false;
};


// Modified from http://ejohn.org/blog/javascript-pretty-date/#comment-297458
function prettyDate(date) {
    var date, seconds, formats, i = 0, f;
    date = new Date(date);
    seconds = (new Date - date) / 1000;
    formats = [
        [-1, 'Recently'], // Deals with times in the future
        [60, 'seconds', 1],
        [120, '1 minute ago'],
        [3600, 'minutes', 60],
        [7200, '1 hour ago'],
        [86400, 'hours', 3600],
        [172800, 'Yesterday'],
        [604800, 'days', 86400],
        [1209600, '1 week ago'],
        [2678400, 'weeks', 604800]
    ];

    while (f = formats[i++]) {
        if (seconds < f[0]) {
            return f[2] ? Math.floor(seconds / f[2]) + ' ' + f[1] + ' ago' : f[1];
        }
        // Crude fix for feed items with incorrect pubDate (i.e. 01 Dec 1999)
        // look for anything over 10 years old
        if (seconds > 315569260) {
            return 'Recently';
        }
    }

    return dateFormat(date, 'longDate');
};


/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 *
 * http://blog.stevenlevithan.com/archives/date-time-format
 */
var dateFormat = function () {
    var token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
        timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
        timezoneClip = /[^-+\dA-Z]/g,
        pad = function (val, len) {
            val = String(val);
            len = len || 2;
            while (val.length < len) val = "0" + val;
            return val;
        };

    // Regexes and supporting functions are cached through closure
    function finalDate(date, mask, utc) {
        // You can't provide utc if you skip other args (use the "UTC:" mask prefix)
        if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
            mask = date;
            date = undefined;
        }

        // Passing date through Date applies Date.parse, if necessary
        date = date ? new Date(date) : new Date;
        if (isNaN(date)) throw SyntaxError("invalid date");

        mask = String(dateFormat.masks[mask] || mask || dateFormat.masks["default"]);

        // Allow setting the utc argument via the mask
        if (mask.slice(0, 4) == "UTC:") {
            mask = mask.slice(4);
            utc = true;
        }

        var _ = utc ? "getUTC" : "get",
            d = date[_ + "Date"](),
            D = date[_ + "Day"](),
            m = date[_ + "Month"](),
            y = date[_ + "FullYear"](),
            H = date[_ + "Hours"](),
            M = date[_ + "Minutes"](),
            s = date[_ + "Seconds"](),
            L = date[_ + "Milliseconds"](),
            o = utc ? 0 : date.getTimezoneOffset(),
            flags = {
                d: d,
                dd: pad(d),
                ddd: dateFormat.i18n.dayNames[D],
                dddd: dateFormat.i18n.dayNames[D + 7],
                m: m + 1,
                mm: pad(m + 1),
                mmm: dateFormat.i18n.monthNames[m],
                mmmm: dateFormat.i18n.monthNames[m + 12],
                yy: String(y).slice(2),
                yyyy: y,
                h: H % 12 || 12,
                hh: pad(H % 12 || 12),
                H: H,
                HH: pad(H),
                M: M,
                MM: pad(M),
                s: s,
                ss: pad(s),
                l: pad(L, 3),
                L: pad(L > 99 ? Math.round(L / 10) : L),
                t: H < 12 ? "a" : "p",
                tt: H < 12 ? "am" : "pm",
                T: H < 12 ? "A" : "P",
                TT: H < 12 ? "AM" : "PM",
                Z: utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
                o: (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
                S: ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
            };

        return mask.replace(token, function ($0) {
            return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
        });
    };

    return finalDate;
}();


// Some common format strings
dateFormat.masks = {
    "default": "HH:MM:ss dd mmm yyyy ", // 17:46:21 09 Jun 2007
    shortDate: "m/d/yy", // 6/9/07
    mediumDate: "d mmm yyyy", // 9 Jun 2007
    longDate: "d mmmm yyyy", // 9 June 2007
    fullDate: "dddd, mmmm d, yyyy", // Saturday, June 9, 2007
    shortTime: "h:MM TT", // 5:46 PM
    mediumTime: "h:MM:ss TT", // 5:46:21 PM
    longTime: "h:MM:ss TT Z", // 5:46:21 PM EST
    isoDate: "yyyy-mm-dd", // 2007-06-09
    isoTime: "HH:MM:ss", // 17:46:21
    isoDateTime: "yyyy-mm-dd'T'HH:MM:ss", // 2007-06-09T17:46:21
    isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'", // 2007-06-09T22:46:21Z

    timeDate: "dd mmm; h:MM TT" // 09 Jun; 5:46:21 PM
};


// Internationalization strings
dateFormat.i18n = {
    dayNames: [
        "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
        "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
    ],
    monthNames: [
        "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
        "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
    ]
};


// For convenience...
Date.prototype.format = function (mask, utc) {
    return dateFormat(this, mask, utc);
};


//Get padding size for an element
function getVerticalPadding(el) {
    var pt = parseInt($(el).css("padding-top").replace("px", ""));
    var pb = parseInt($(el).css("padding-bottom").replace("px", ""));

    return (pt + pb);
}


//Get margin size for an element
function getVerticalMargins(el) {
    var mt = parseInt($(el).css("margin-top").replace("px", ""));
    var mb = parseInt($(el).css("margin-bottom").replace("px", ""));

    return (mt + mb);
}


function stripTags(html) {
    var tmp = document.createElement("DIV");
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText;
}
