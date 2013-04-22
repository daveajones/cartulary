//-----------------------------------------------------------------------------------
// ----- Feed Subscription API -----
freedomController.v1.subscribe = {};
freedomController.v1.subscribe.methods = (function() {
//-----------------------------------------------------------------------------------

function _searchPostLoad() {
	_rebindEverything();
}

function _rebindEverything() {
	_bindUnsubLinks();
        _bindStickyButtons();
        _bindHiddenButtons();
	_bindFullTextButtons();
}

function _bindUnsubLinks() {
        //Bind some new clicks to the refreshed Unsub links
        $('.aUnsubscribe').click(function() {
                var aobj = $(this);
                var cgiurl = $('#cgiUrls').attr("data-unsubscribe");
                var rowid = aobj.parent().attr("id");
                var feedtitle = aobj.parent().attr("data-title");
                if(confirm("Unsubscribe from '" + feedtitle + "'?") == false) {
                        return false;
                } else {
                        $.ajax({
                                url: cgiurl + '?id=' + rowid,
                                type: "GET",
                                timeout: 60000,
                                dataType:       'json',
                                success:        function(data) {
                                        if(data.status == "false") {
						showMessage( data.description, data.status, 5 );
                                        } else {
                				showMessage( data.description, data.status, 5 );
                                                $('#'+rowid).remove();
                                        }
                                }
                        });
                }
                return false;
        });
}

function _bindStickyButtons() {
        //Bind some new clicks to the stickybuttons
        $('.stickybutton').click(function() {
                var bobj = $(this);
                var cgiurl = $('#cgiUrls').attr("data-setfeedpropscgi");
                var fid = bobj.parent().parent().attr("data-id");
                var sticky = '&unsticky=true';

                //Set the flags
                if( bobj.hasClass('btnFeedNotSticky') ) {
                        sticky = '&sticky=true';
                }

                //Make the call
                $.ajax({
                        url:    cgiurl + '?fid=' + fid + sticky,
                        type:   "GET",
                        timeout:        60000,
                        dataType:       'json',
                        success:        function(data) {
                                if(data.status == "false") {
					showMessage( data.description, data.status, 5 );
                                } else {
					showMessage( data.description, data.status, 5 );
                                        if( sticky != '&unsticky=true' ) {
                                                bobj.removeClass('btnFeedNotSticky').addClass('btnFeedSticky');
                                        } else {
                                                bobj.removeClass('btnFeedSticky').addClass('btnFeedNotSticky');
                                        }
                                }
                        }
                });

                return false;
        });
}

function _bindHiddenButtons() {
        //Bind some new clicks to the hiddenbuttons
        $('.hiddenbutton').click(function() {
                var bobj = $(this);
                var cgiurl = $('#cgiUrls').attr("data-setfeedpropscgi");
                var fid = bobj.parent().parent().attr("data-id");
                var hidden = '&unhidden=true';

                //Set the flags
                if( bobj.hasClass('btnFeedNotHidden') ) {
                        hidden = '&hidden=true';
                }

                //Make the call
                $.ajax({
                        url:    cgiurl + '?fid=' + fid + hidden,
                        type:   "GET",
                        timeout:        60000,
                        dataType:       'json',
                        success:        function(data) {
                                if(data.status == "false") {
					showMessage( data.description, data.status, 5 );
                                } else {
					showMessage( data.description, data.status, 5 );
                                        if( hidden != '&unhidden=true' ) {
                                                bobj.removeClass('btnFeedNotHidden').addClass('btnFeedHidden');
                                        } else {
                                                bobj.removeClass('btnFeedHidden').addClass('btnFeedNotHidden');
                                        }
                                }
                        }
                });

                return false;
        });
}

function _bindFullTextButtons() {
        //Bind some new clicks to the fulltext buttons
        $('.fulltextbutton').click(function() {
                var bobj = $(this);
                var cgiurl = $('#cgiUrls').attr("data-setfeedpropscgi");
                var fid = bobj.parent().parent().attr("data-id");
                var fulltext = '&unfulltext=true';

                //Set the flags
                if( bobj.hasClass('btnFeedNotFullText') ) {
                        fulltext = '&fulltext=true';
                }

                //Make the call
                $.ajax({
                        url:    cgiurl + '?fid=' + fid + fulltext,
                        type:   "GET",
                        timeout:        60000,
                        dataType:       'json',
                        success:        function(data) {
                                if(data.status == "false") {
					showMessage( data.description, data.status, 5 );
                                } else {
					showMessage( data.description, data.status, 5 );
                                        if( fulltext != '&unfulltext=true' ) {
                                                bobj.removeClass('btnFeedNotFullText').addClass('btnFeedFullText');
                                        } else {
                                                bobj.removeClass('btnFeedFullText').addClass('btnFeedNotFullText');
                                        }
                                }
                        }
                });

                return false;
        });
}

function _adjustPageLayout() {
	if( $('#divFeedColumn .feedItem').length < 1) {
		$('#divFeedColumn').remove();
		$('#divFeedColumn').removeClass('span6').removeClass('span4');
		$('#divOutlineColumn').removeClass('span3').removeClass('span5');
	} else if( $('#divOutlineColumn .feedItem').length > $('#divFeedColumn .feedItem').length ) {
		$('#divOutlineColumn').removeClass('span3').addClass('span5');
		$('#divFeedColumn').removeClass('span6').addClass('span4');
	}
}
//-----------------------------------------------------------------------------------

return {
        searchPostLoad      : _searchPostLoad,
	bindUnsubLinks      : _bindUnsubLinks,
        bindStickyButtons   : _bindStickyButtons,
        bindHiddenButtons   : _bindHiddenButtons,
	bindFullTextButtons : _bindFullTextButtons,
	adjustPageLayout    : _adjustPageLayout
};

//-----------------------------------------------------------------------------------
}());
