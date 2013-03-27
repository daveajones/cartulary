var systemUrl = '<?echo $system_fqdn?>';
var platform = '<?echo $platform?>';
var msgtimer;

function sanitize_twitter(divStream) {
$(divStream + ' a').each( function() {
    var alobj = $(this);
    var artitle = alobj.text();
	if( alobj.attr("href") ) {
    if(alobj.attr("href").indexOf("twitter.com") != -1) {
    
        //If this twitter post contains a URL, then attach that URL to the link
        if(artitle.indexOf("http://") != -1) {
            var twurl = artitle.match(/\bhttp\:\/\/([A-Za-z0-9\.\/\+\&\@\~\-\%\?\=\_\#\!]*)/gi);
            //alobj.attr("href", twurl);
            //alert(twurl);
            var twlen = twurl.length;
            for(var i=0; i < twlen; i++) {
              alobj.parent().append(' &rarr; <a class="twurl" href="' + twurl[i] + '">Link</a>');
            }
        } else {
            return true;
        }
    }
	}
});
}

//Strip leading and trailing whitespace from a string
function trim(strText) {
    // this will get rid of leading spaces
    while (strText.substring(0,1) == ' ')
        strText = strText.substring(1, strText.length);

    // this will get rid of trailing spaces
    while (strText.substring(strText.length-1,strText.length) == ' ')
        strText = strText.substring(0, strText.length-1);

   return strText;
}

//Extend jquery so we can easily center an element
//_____via stackflow: http://stackoverflow.com/questions/210717/using-jquery-to-center-a-div-on-the-screen
jQuery.fn.center = function ( horz, vert ) {
    this.css("position","absolute");
    if(vert == true) {
      this.css("top", Math.max(0, (($(window).height() - this.outerHeight()) / 2) + $(window).scrollTop()) + "px");
    }
    if(horz == true) {
      this.css("left", Math.max(0, (($(window).width() - this.outerWidth()) / 2) + $(window).scrollLeft()) + "px");
    }
    return this;
}

//Display something in the global message box
function showMessage( text, status, timeout) {
   clearInterval(msgtimer);
   $('#divMessageBox').slideUp("normal", function(){ $('#divMessageBox').remove(); });

   $('#divPageTitle').after('<div style="display:none;" id="divMessageBox"><p id="messagebox"></p></div>');

   $('#messagebox').empty();
   $('#messagebox').removeClass('msggood');
   $('#messagebox').removeClass('msgbad');
   if(platform == "mobile") {
     $('#messagebox').append(text);  
   } else {
     $('#messagebox').append('<a id="btnMessageBoxClose" title="Close message box." onclick="javascript:closeMessage();"><img class="icon-collapse-up" src="/images/blank.gif" /></a>' + text);
   }

   if(status == false || status == "false") {
      $('#messagebox').addClass('msgbad');
   } else if (status == "warning") {
      $('#messagebox').addClass('msgwarn');
   } else {
      $('#messagebox').addClass('msggood');  
   }

   if(platform == "mobile") {
      alert(text);
   } else {
      $('#divMessageBox').center(true, false);
      $('#divMessageBox').slideDown();

      clearInterval(msgtimer);
      msgtimer = setInterval( function() {
         closeMessage();
      },
      (timeout * 1000));
   }

}

function closeMessage() {
         $('#divMessageBox').slideUp("normal", function(){ $('#divMessageBox').remove(); });
         return(false);
}

//Empty and blank string checks
//via: http://stackoverflow.com/questions/154059/what-is-the-best-way-to-check-for-an-empty-string-in-javascript
function isEmpty(str) {
    return (!str || 0 === str.length);
}
function isBlank(str) {
    return (!str || /^\s*$/.test(str));
}


function isImage(url) {
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.jpg') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.jpeg') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.png') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.gif') != -1 && url.indexOf('http') == 0 ) { return true; }
        
        return false;
};

function isVideo(url) {
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.m4v') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.mp4') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.avi') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.mov') != -1 && url.indexOf('http') == 0 ) { return true; }
        
        return false;
};

function isAudio(url) {
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.mp3') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.m4a') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.wav') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.ogg') != -1 && url.indexOf('http') == 0 ) { return true; }
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('.wmv') != -1 && url.indexOf('http') == 0 ) { return true; }
        
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

  while (f = formats[i ++]) {
    if (seconds < f[0]) {
        return f[2] ? Math.floor(seconds / f[2]) + ' ' + f[1] + ' ago' :  f[1];
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
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
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

		var	_ = utc ? "getUTC" : "get",
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
				d:    d,
				dd:   pad(d),
				ddd:  dateFormat.i18n.dayNames[D],
				dddd: dateFormat.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dateFormat.i18n.monthNames[m],
				mmmm: dateFormat.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
    
    return finalDate;
}();

// Some common format strings
dateFormat.masks = {
	"default":      "HH:MM:ss dd mmm yyyy ", // 17:46:21 09 Jun 2007 
	shortDate:      "m/d/yy", // 6/9/07
	mediumDate:     "d mmm yyyy", // 9 Jun 2007
	longDate:       "d mmmm yyyy", // 9 June 2007
	fullDate:       "dddd, mmmm d, yyyy", // Saturday, June 9, 2007
	shortTime:      "h:MM TT", // 5:46 PM
	mediumTime:     "h:MM:ss TT", // 5:46:21 PM
	longTime:       "h:MM:ss TT Z", // 5:46:21 PM EST
	isoDate:        "yyyy-mm-dd", // 2007-06-09
	isoTime:        "HH:MM:ss", // 17:46:21
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss", // 2007-06-09T17:46:21
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'", // 2007-06-09T22:46:21Z
    
    timeDate:       "dd mmm; h:MM TT" // 09 Jun; 5:46:21 PM
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


// debouncing function from John Hann
// http://unscriptable.com/index.php/2009/03/20/debouncing-javascript-methods/
(function($,sr){
  var debounce = function (func, threshold, execAsap) {
      var timeout;

      return function debounced () {
          var obj = this, args = arguments;
          function delayed () {
              if (!execAsap)
                  func.apply(obj, args);
              timeout = null;
          };

          if (timeout)
              clearTimeout(timeout);
          else if (execAsap)
              func.apply(obj, args);

          timeout = setTimeout(delayed, threshold || 100);
      };
  }
  // smartresize 
  jQuery.fn[sr] = function(fn){  return fn ? this.bind('resize', debounce(fn)) : this.trigger(sr); };

})(jQuery,'smartresize');


//Get padding size for an element
function getVerticalPadding(el) {
	var pt = parseInt($(el).css("padding-top").replace("px", ""));
        var pb = parseInt($(el).css("padding-bottom").replace("px", ""));

        return(pt + pb);
}


//Get margin size for an element
function getVerticalMargins(el) {
        var mt = parseInt($(el).css("margin-top").replace("px", ""));
        var mb = parseInt($(el).css("margin-bottom").replace("px", ""));

        return(mt + mb);
}


//Adjust the size of a cartulized article modal to fill the screen better
function sizeArticleModal(el, loading) {
	<?if($g_platform != "mobile"){?>
	var winH = $(window).height();
        var winW = $(window).width();  
	var winScLeft = $(window).scrollLeft();

	//Set left position
        $(el).css("left", ( (winW - $(el).width() ) / 2 ) + winScLeft + "px");

	if(loading == true) {
		//Reset height params so it looks compact while grabbing data	
                $(el).css("height", "");  
                $(el).css("max-height", "");  
                $(el).css("height", "");
                $(el).css("max-height", "");
	        $(el + ' .modal-body').css('max-height', "");
        	$(el + ' .modal-body').css('height', "");    

                //Set initial size and position based on the current viewport size
                $(el).css("max-width", ( (winW + winScLeft) - 120) + "px");
                $(el).css("max-width", ( $(el).width() - 30) + "px");
		$(el).css("top", (( (winH - $(el).height()) / 2 ) / 2)  + "px");

		return true;
	}

	//Size the modal elements according to the current viewport
	var exPad = getVerticalPadding(el) + getVerticalPadding(el + ' .modal-body') + getVerticalPadding(el + ' .modal-body');
        $(el).css('max-height', winH - exPad + "px");  
        $(el).css('height', winH - exPad + "px");  

        var modH = $(el).outerHeight(true);
        var modW = $(el).outerWidth(true);
        var hfSize = $(el + ' .modal-header').outerHeight(true) + $(el + ' .modal-footer').outerHeight(true);    

        $(el).css("top", (winH - modH) / 2 );
        $(el + ' .modal-body').css('max-height', (modH - hfSize - exPad - 5) + "px" );
        $(el + ' .modal-body').css('height', (modH - hfSize - exPad - 5) + "px" );    


	$(window).smartresize(function(){
		sizeArticleModal(el, false);
	});

	<?}?>			
	return true;
}
