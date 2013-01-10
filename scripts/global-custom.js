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

