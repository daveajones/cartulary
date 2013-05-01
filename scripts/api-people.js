//-----------------------------------------------------------------------------------
// ----- People API -----
freedomController.v1.people = {};
freedomController.v1.people.methods = (function() {
//-----------------------------------------------------------------------------------

function _searchPostLoad( query, elParent ) {
        //If this is a people search, then
        //Get a list of the other servers we know about and ping each of them as well
        $(elParent + ' .body .search-pre').append("Local");
        $.ajax({
                url:      "/cgi/out/list.servers",
                type:     "GET",
                dataType: 'json',
                timeout:  10000,
                success:  function(data) {
	                if(data.status != "false") {
				//Loop through the server list and call each one
                                $.each(data.servers, function(k, v) {
					if( v.address !== systemUrl ) {
						(function() {
	                	                  var lpUrl = "http://" + v.address + "/cgi/out/list.people?q=" + encodeURI(query) + "&callback=?";
						  var serverclass = "server" + k;

					          $(elParent).append('<div class="search-extra ' + serverclass + '">' + v.address + '<img class="search-server-spinner" src="/images/spinner.gif" alt="" /></div>');
						  $.ajax({
							url:      lpUrl,
							dataType: "jsonp",
							timeout:  10000,
							success:  function(data) {
								if( data.status == "false" ) {
		                        		                $(elParent + ' .' + serverclass).append('<p class="search-pre">Server:' + v.address + '</p>');
									$(elParent + ' .' + serverclass).append('<p class="result-msg">Private.</p>');
								} else
                                                                if( data.data.length < 1 ) {
		                        		                $(elParent + ' .' + serverclass).append('<p class="search-pre">Server:' + v.address + '</p>');
									$(elParent + ' .' + serverclass).append('<p class="result-msg">No matches.</p>');
								} else {
									$(elParent + ' .' + serverclass).empty();
	                	        	                        $('#search-people').tmpl(data).appendTo(elParent + ' .' + serverclass);
	                        			                $(elParent + ' .' + serverclass + ' .search-pre').append('Server:' + v.address);
								}
		                               	                $(elParent + ' .' + serverclass).addClass('search-remote-results').addClass('search-extra').addClass('search-more');
								$(elParent + ' .' + serverclass + ' .search-server-spinner').remove();
								_rebindEverything( elParent );
							},
							error:	  function() {
								$(elParent + ' .' + serverclass).empty();
	                        		                $(elParent + ' .' + serverclass).append('<p class="search-pre">Server:' + v.address + '</p>');
								$(elParent + ' .' + serverclass).append('<p class="result-msg">Connection error.</p>');
							}
						  });
						})();
					}
                                });
                	}
                }
        });

	_rebindEverything( elParent );
}

function _rebindEverything( elParent ) {
	_bindSubscribeLinks( elParent );
	_bindSocialOutlineLinks( elParent );
}

function _bindSubscribeLinks( elParent ) {
        //Bind some new clicks to the subscription links
        $(elParent + ' .aSubscribe').unbind('click');
        $(elParent + ' .aSubscribe').click(function() {
                var bobj = $(this);
                var sourceurl = bobj.attr("data-sourceurl");

                //Make the call
                $.ajax({
                        url:    '/cgi/in/subscribe?url=' + sourceurl,
                        type:   "GET",
                        timeout:        20000,
                        dataType:       'json',
                        success:        function(data) {
                                if(data.status == "false") {
                                        showMessage( data.description, data.status, 5 );
                                } else {
                                        showMessage( data.description, data.status, 5 );
                                }
                        }
                });

                return false;
        });
}


function _bindSocialOutlineLinks( elParent ) {
        //Ajaxify the social outline links
        $(elParent + ' .sopmllink').click(function() {
			var modal = '#mdlSocialOutlineView';
                        var aobj = $(this);
                        var href = aobj.attr("data-href");
	                var cgiurl = $('#cgiUrls').attr("data-getsopml");

                        $(modal + ' .modal-header').hide();
                        $(modal + ' .modal-body .sobody').hide();
                        $(modal + ' .modal-footer').hide();

                        $(modal + ' .modal-footer .aSubscribe').attr('data-sourceurl', href);
			_bindSubscribeLinks(modal + ' .modal-footer');

                        $(modal + ' .soserver .serverlink').empty();
                        $(modal + ' .soname').empty();
                        $(modal + ' .soavatar').attr('src', '/images/blank.gif');
                        $(modal + ' .sopubfeeds').empty();
                        $(modal + ' .spinner').show();

                        modalFullHeight(modal, true);
                        $(modal).modal('show');
                        $.ajax({
                                url:      cgiurl + '?url=' + href,
                                type:     "GET",
                                dataType: 'json',
                                timeout:  30000,
                                success:  function(data) {
                                                $(modal + ' .spinner').hide();
                                                if(data.status == "false") {
                                                        $(modal + ' .sotitle').append(data.title);
                                                        $(modal + ' .sobody').append(data.description);
                                                } else {
                                                        modalFullHeight(modal, false);
							//Set the server link
                                                        $(modal + ' .soserver .serverlink').append(data.server);
                                                        $(modal + ' .soserver .serverlink').attr('href', 'http://' + data.server);
							//Set the name
                                                        $(modal + ' .soname').append(data.ownername);
							//Set the avatar
							if( data.avatarurl == '' ) {
								data.avatarurl = '/images/noavatar.png';
							}
                                                        $(modal + ' .soavatar').attr('src', data.avatarurl);
							//Display the pub feeds list
							for( var i = 0 ; i < data.feeds.pub.length ; i++ ) {
								$(modal + ' .sopubfeeds').append('<li><a href="' + data.feeds.pub[i].url + '"><img class="icon-feed-raw" src="/images/blank.gif" alt="" /></a><a href="' + data.feeds.pub[i].html + '">' + data.feeds.pub[i].text + '</a></li>');
							}
							//Display some recent activity for this user
							$(modal + ' .soactivity').append('');

                                                        $(modal + ' .sofooter .link').attr('href', data.url);
                                                        $(modal + ' .sofooter').show();
                                                }
                                                $(modal + ' .modal-header').show();
                                                $(modal + ' .modal-body .sobody').show();
                                                $(modal + ' .modal-footer').show();		
                                        },
                                error:  function(x, t, m) {
                                                $(modal + ' .modal-header').show();
                                                //$(modal + ' .modal-footer').show();
                                                $(modal + ' .spinner').hide();
                                                $(modal + ' .sotitle').append('');
                                                $(modal + ' .sobody').append('<p>Error communicating with server. Connection problem?</p>');
                                }
                        });
                        return false;
        });
}


//-----------------------------------------------------------------------------------

return {
        searchPostLoad      	: _searchPostLoad,
	bindSubscribeLinks  	: _bindSubscribeLinks,
	bindSocialOutlineLinks  : _bindSocialOutlineLinks
};

//-----------------------------------------------------------------------------------
}());
