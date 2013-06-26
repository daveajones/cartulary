jQuery.fn.fadeSlideDown = function (speed, easing, callback) {
    if (this.is(':hidden')) {
        return this.slideDown(speed, easing).fadeTo(speed, 1, easing, callback);
    }
};

var River = River || {};
var resizeTimer = null;
var enclosureActivator = null;

// defaults
River.defaults = {
    url : '/river-json',
    callback : 'onGetRiverStream',
    social : false,
    initialized : false
};

// generate and update stream
River.generate = (function () { 
    $(function () {
        River.generate.loading();
        River.generate.init(url, callback, initialized);
        River.generate.viewToggle();
    });
    
    var url = River.settings.url || River.defaults.url,
        callback = River.settings.callback || River.defaults.callback,
        initialized = River.settings.initialized || River.defaults.initialized,
        $template = $('#template'),
        $stream = $('#stream');
        
    function _loading() {
        var source = River.methods.getDomain(url);
        $stream.html('<p class="notice">Loading news from <b>' + source + '</b>, please wait&hellip;</p><div id="loader"></div>');
    };
    
    function _init(url, callback, initialized) {
        var count, lastItemId, newestItem, riverUpdated, timeoutMsg, errorMsg;

        // if the stream is empty...
        if( initialized == false ) {
 		_populate(jsonRiverData);
		console.log("DEBUG: Populated river locally.");

		<?if($g_platform != "mobile"){?>
                // check river data every 5 minutes
                setInterval(function () {
        		_init(url, callback, initialized);
                }, 300000);
		<?}?>
		
		initialized = true;
	     	return true;
        }

        // get json feed data
        $.ajax({ 
            dataType : "jsonp",
            jsonpCallback : callback,
            url : url,
            timeout : 60000,
            
            success : function (data, status) {
		console.log("DEBUG: Populated river through ajax.");
		initialized = true;
                lastItemId = $('.article:first').attr('id');
                count = 0;
                newestItem = data.updatedFeeds.updatedFeed[0].item[0].id;
                riverUpdated = dateFormat(data.metadata.whenGMT, 'timeDate');
                
                // if the stream is empty...
                if ( ! lastItemId ) {
                    _populate(data);
                    // check river data every 5 minutes
                    setInterval(function () {
                        _init(url, callback, initialized);
                    }, 300000);
                }
                
                // otherwise...
                else { 
                    // check for new items
                    if ( lastItemId !== newestItem ) {
                        $.each(data.updatedFeeds.updatedFeed, function (f, feed) {
                            $.each(data.updatedFeeds.updatedFeed[f].item, function (i, item) {
                                // count the number of newer items
                                if ( item.id > lastItemId ) {
                                    count ++;
                                }
                            });
                        });
                        
			document.title = 'River - New Items';
			$('#divMainMenu .navbar a.homebutton img').removeClass('icon-nav-home').addClass('icon-nav-home-lights');
                        $('#stream-updated .time').text(riverUpdated);
                    } 
                    
                    else {
			$('#divMainMenu .navbar a.homebutton img').removeClass('icon-nav-home-lights').addClass('icon-nav-home');
                        $('#stream-updated .time').text(riverUpdated);
                    }
                }
            },
            
            error : function (status) { 
                var statusText = status.statusText;

                // if the stream is empty...
                if ( (! lastItemId) && (initialized == false) ) {
                    timeoutMsg = '<p class="notice">Sorry, your request has timed out. Please try again.</p>';
                    errorMsg = '<p class="notice">Sorry, something has gone wrong. Please try again.</p>';
                    ( statusText == 'timeout' ) ? $stream.html(timeoutMsg) : $stream.html(errorMsg);
                }

                // otherwise...
                else {
                    //console.log('update error');
                }
            }
        });

    };
    
    // stream view toggle
    function _viewToggle() {
        $('body').delegate('#stream-view', 'click', function (e) {
            e.preventDefault();
            ( $stream.is('.collapsed') ) ? _expand() : _collapse();
        });
    };
    
    // stream view setting
    function _viewSettings() {
        var viewCookie = River.methods.getCookie('river-view');
        ( viewCookie === 'collapsed' ) ? _collapse() : _expand();
    };
   
    // pass data to template, populate stream, mark last item
    function _populate(data, marker) {
        var tmplOutput = $template.tmpl(data);
        $stream.html(tmplOutput);
        $('#stream-notice').hide();
        _viewSettings();
        if (marker) {
            $('.last-old').removeClass('last-old');
            $stream.find('#' + marker).addClass('last-old');
        }
        River.methods.bindStickyLinks();
        River.methods.bindSubscribeLinks();
        River.methods.bindCartLinks();
		River.methods.bindMicroblogLinks();
		River.methods.bindEnclosureLinks();
		River.methods.bindEmbedActivations();
        //Jump to top button
        $('.jumpTop').click(function () {
		$('html, body').animate({ scrollTop: '0px' }, 300); 
		return false;
	});
	<?if($g_platform != "mobile"){?>
	focusFirstVisibleArticle();
	<?}?>
	document.title = 'River';
	$('#divMainMenu .navbar a.homebutton img').removeClass('icon-nav-home-lights').addClass('icon-nav-home');
	River.methods.sortGrid();
    };
    
    // expand stream items
    function _expand() {
        River.methods.setCookie('river-view', 'expanded', 30);
        $('#stream-view').text('Switch to collapsed view');
        $stream.removeClass('collapsed');
    };
    
    // collapse stream items
    function _collapse() {
        River.methods.setCookie('river-view', 'collapsed', 30);
        $('#stream-view').text('Switch to expanded view');
        $stream.addClass('collapsed');
    };
    
    return {
        loading : _loading,
        init : _init,
        viewToggle : _viewToggle
    };
}());


River.methods = (function () {
	function _sortGrid() {
		<?if($g_platform != "mobile"){?>
		var viewportWidth = $(window).width();
		var sidebarWidth = $('#stream-sidebar-right').width();
		var streamWidth = (viewportWidth - sidebarWidth) - 80;

		$('#stream').css('width', streamWidth + 'px')

		var ccsize = streamWidth / 570;
		var colcount = ccsize.toFixed();
		if( ccsize < 2 ) {
			colcount = 1;
		}
		<?if($g_prefs['rivercolumns'] > 0) {?>
		colcount = <?echo $g_prefs['rivercolumns']?>;
		<?}?>
		if( colcount == 1 ) {
			$('#stream .stream-list').css('width', '600px');
			$('#stream .stream-list').css('margin-left', 'auto');
			$('#stream .stream-list').css('margin-right', 'auto');
		} else {
			$('#stream .stream-list').css('width', '');
		}
		$('#stream .stream-list').css('column-count', '1');
		$('#stream .stream-list').css('column-count', colcount);
		$('#stream .stream-list').css('-webkit-column-count', colcount);
		$('#stream .stream-list').css('-moz-column-count', colcount);
		<?}?>
	}

	function _bindStickyLinks() {
        	//Bind some new clicks to the stickybuttons
        	$('.aUnSticky').click(function() {
                	var bobj = $(this);
                	var id = bobj.attr("data-id");
                	var fid = bobj.attr("data-feedid");

					//Get any sticky subitems so we can un-sticky them too
					var subitems = $('#' + id + ' .subitem.sticky').map(function() { return this.id; }).get();	
	
                	//Make the call
                	$.ajax({
                        	url:    '/cgi/in/unsticky?id=' + id,
                        	type:   "GET",
                        	timeout:        20000,
                        	dataType:       'json',
                        	success:        function(data) {
                                	if(data.status == "false") {
                                                showMessage( data.description, data.status, 5 );
                                	} else {
										//Add the un-stickied item id to sessionStorage
										var postProperties = {};
										var post = sessionStorage.getItem(id);
										if( post !== null ) {
											postProperties = JSON.parse(post);
										}
										postProperties.sticky = false;
										sessionStorage.setItem(id, JSON.stringify(postProperties));
                                	}

									//Remove the item
									$('#' + id).remove();

									//Remove feed from the active feed list if no more posts exist
									console.log("feedid:"+fid);
									if( $('.stream-list li.article.' + fid).length < 1 ) {
										$('#divActiveFeeds ul.feedlist li.' + fid).remove();
									}
					
									//Ajust grid
                                    _sortGrid();
	
									//Target the next article after this one goes away
										focusFirstVisibleArticle();
		                        	}
                	});

			//Loop through the sticky subitems and un-sticky them as well
			for( var i =0 ; i < subitems.length ; i++ ) {
	                	//Make the call
        	        	$.ajax({
                	        	url:    '/cgi/in/unsticky?id=' + subitems[i],
                        		type:   "GET",
                        		timeout:        20000,
	                        	dataType:       'json',
        	                	success:        function(data) {
                	                	if(data.status == "false") {
                        	                        showMessage( data.description, data.status, 5 );
	                                	}
                	        	}
                		});
			}
	
                	return false;
        	});
	}

        function _bindFeedStickyLinks() {
                //Bind some new clicks to the stickybuttons
                $('.aFeedUnSticky').click(function() {
                        var bobj = $(this);
                        var id = bobj.attr("data-id");
                        bobj.addClass("fsticky" + id);

                        //Make the call
                        $.ajax({
                                url:    '/cgi/in/setfeedprops?fid=' + id + '&unsticky=true',
                                type:   "GET",
                                timeout:        20000,
                                dataType:       'json',
                                success:        function(data) {
                                        if(data.status == "false") {
                                                showMessage( data.description, data.status, 5 );
                                        } else {
                                                showMessage( data.description, data.status, 5 );
                                                $('.fsticky' + data.id).removeClass('aFeedUnSticky').addClass('aFeedSticky');
                                                $('.fsticky' + data.id + '> img').removeClass('icon-feed-unsticky').addClass('icon-feed-sticky');
                                        }
                                }
                        });

                        return false;
                });
                $('.aFeedSticky').click(function() {
                        var bobj = $(this);
                        var id = bobj.attr("data-id");
                        bobj.addClass("fsticky" + id);

                        //Make the call
                        $.ajax({
                                url:    '/cgi/in/setfeedprops?fid=' + id + '&sticky=true',
                                type:   "GET",
                                timeout:        20000,
                                dataType:       'json',
                                success:        function(data) {
                                        if(data.status == "false") {
                                                showMessage( data.description, data.status, 5 );
                                        } else {
                                                showMessage( data.description, data.status, 5 );
                                                $('.fsticky' + data.id).removeClass('aFeedSticky').addClass('aFeedUnSticky');
                                                $('.fsticky' + data.id + '> img').removeClass('icon-feed-sticky').addClass('icon-feed-unsticky');
                                        }
                                }
                        });

                        return false;
                });
        }


	//Modalize the cartulize links
	function _bindCartLinks() {
        	$('.cartlink').unbind('click');
        	$('.cartlink').click(function() {
	   	       	var aobj = $(this);
        	   	var postId = aobj.attr("data-id");
				var pathToPost = '#stream .stream-list li.article#' + postId;
				console.log('pathToPost:' + pathToPost);

				//Don't allow more than one attempt at reblogging
				if( $(pathToPost).hasClass('cartulized') ) {
					focusThisArticle(postId);
					return false;
				}

				//Kill any existing messages
				$(pathToPost + ' .description .inlinecartmsg').remove();

				//Build a cartulize url from the post attributes
				var postUrl = encodeURIComponent($(pathToPost + ' .postinfo').attr("data-url"));
				var postTitle = encodeURIComponent($(pathToPost + ' .postinfo').attr("data-title"));
				var postSourceUrl = encodeURIComponent($(pathToPost + ' .postinfo').attr("data-sourceurl"));
				var postSourceTitle = encodeURIComponent($(pathToPost + ' .postinfo').attr("data-sourcetitle"));
	
				//Set focus on this article
				focusThisArticle(postId);
			
				//Make the cartulization call
				$(pathToPost + ' .description').append('<div class="inlinecartmsg"><img src="/images/spinner.gif" /> Cartulizing. Please wait...</div>');
		        $.ajax({
        	        url:      '<?echo $cartulizecgi?>?json=true' + '&url=' + postUrl + '&title=' + postTitle + '&surl=' + postSourceUrl + '&stitle=' + postSourceTitle,
            	    type:     "GET",
                	dataType: 'json',
	                timeout:  30000,
    	            success:  function(data) {
						if( data.status == 'true' ) {
							//Kill wait message
							$(pathToPost + ' .description .inlinecartmsg').remove();
							//Replace the body text with what we got back
        	                $(pathToPost + ' .description').html(data.article.body);
							//Change to a reading-friendly style
							$(pathToPost).addClass('cartulized');
						} else {
							//Append error message
							$(pathToPost + ' .description .inlinecartmsg').html(data.article.body).addClass('carterrormsg');
						}
						//Set focus on this article
						focusThisArticle(postId);
                    },
	                error:  function(x, t, m) {
						//Append error message
						$(pathToPost + ' .description .inlinecartmsg').html('<p>Error communicating with server. Connection problem?</p>').addClass('carterrormsg');
						//Set focus on this article
						focusThisArticle(postId);
	                }
    	    	});
				return false;
        	});
        	return true;
	}


	//Modalize the MB links
	function _bindMicroblogLinks() {
        	$('.mblink').unbind('click');
        	$('.mblink').click(function() {
	   	       	var aobj = $(this);
        	   	var postId = aobj.attr("data-id");
				var pathToPost = '#stream .stream-list li.article#' + postId;
				var pathToBlog = '#stream .stream-list li.article#' + postId + ' .footer .mbinline';
				var pathToForm = '#stream .stream-list li.article#' + postId + ' .footer .mbinline .mbinform';
				console.log('pathToPost:' + pathToPost);

				//Don't allow more than one attempt at reblogging
				if( $(pathToBlog).length > 0 ) {
					focusThisArticleFooter(postId);
					return false;
				}

				//Tag a class on the end of the footer so we can hook re-blog mode
				$(pathToPost + ' .footer').removeClass('mbposted').addClass('mbmode');

				//Build a form from the post attributes
				var postUrl = $(pathToPost + ' .postinfo').attr("data-url");
				var postDescription = $(pathToPost + ' .postinfo').attr("data-title");
				var postSourceUrl = $(pathToPost + ' .postinfo').attr("data-sourceurl");
				var postSourceTitle = $(pathToPost + ' .postinfo').attr("data-sourcetitle");
				var postOrigin = $(pathToPost + ' .postinfo').attr("data-origin");
	
				//Create a little re-blog window in the footer of the post with a form in it
				$(pathToPost + ' .footer').append('<div class="mbinline ' + postId + '"><form class="mbinform ' + postId + '" method="post" action="<?echo $blogpostcgi?>"></form></div>');
				
				//Add a please wait element
				$(pathToPost + ' .footer').append('<div class="inlineblogmsg hide"><img src="/images/spinner.gif" /> Posting. Please wait...</div>');

				//Build out the form
				//Description
				$(pathToForm).append('<span><textarea class="mbdescription" name="content">' + postDescription.trim() + '</textarea></span>');
				$(pathToForm).append('<img class="icon-extlink" src="/images/blank.gif" /><span><input class="mblink" type="text" name="link" value="' + postUrl.trim() + '" /></span>');
				$(pathToForm).append('<input class="mbsourceurl hide" type=hidden" name="source[url]" value="' + postSourceUrl.trim() + '" />');
				$(pathToForm).append('<input class="mbsourcetitle hide" type="hidden" name="source[title]" value="' + postSourceTitle.trim() + '" />');
				$(pathToForm).append('<input class="mborigin hide" type="hidden" name="origin" value="' + postOrigin.trim() + '" />');

				//Add enclosures to the form
				var encount = 0;
				//Add a div with a list for holding external enclosures	
				$(pathToForm).append('<div class="mbextenc hide"><ul></ul></div>');
				$(pathToPost + ' .enclosureview .encobj').each(function() {
					//Add a list element for each enclosure
					$(pathToForm + ' .mbextenc ul').append('<li></li>');

					//Add a delete button for each enclosure
					$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<a href="#" class="delete"><img class="icon-remove-small" src="/images/blank.gif" /></a>');

					//Now add the enclosure thumbnail
					if( $(this).hasClass('enclosurepic') ) {
						$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure" src="' + $(this).attr("src") + '" />');
					} else
					if( $(this).hasClass('encaudio') ) {
						$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-audio-enclosure" src="/images/blank.gif" alt="" />');
					} else
					if( $(this).hasClass('encvideo') ) {
						$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-video-enclosure" src="/images/blank.gif" alt="" />');
					} else
					if( $(this).hasClass('enciframe') ) {
						$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-iframe-enclosure" src="/images/blank.gif" alt="" />');
					}

					//Add the input element to the form
					$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<input type="text" name="extenclosure[' + encount + '][url]" value="' + $(this).attr("src") + '" />');
					$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<input type="hidden" name="extenclosure[' + encount + '][type]" value="' + $(this).attr("data-type") + '" />');
					$(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<input type="hidden" name="extenclosure[' + encount + '][length]" value="' + $(this).attr("data-length") + '" />');

					//Attach click handler for the delete button
					$(pathToForm + ' .mbextenc ul li:eq(' + encount + ') a.delete').click(function() {
						$(this).parent().remove();
						if( $(pathToForm + ' .mbextenc ul li').length == 0 ) {  $(pathToForm + ' .mbextenc').hide();  }
						return false;
					});

					//Un-hide the external enclosure form since we have at least one external enclosure
					$(pathToForm + ' .mbextenc').show();			

					//Increment the enclosure counter
					encount++;
				});

				//Add a twitter toggle if twitter is enabled
				<?if( twitter_is_enabled($g_uid) ) {?>
				$(pathToForm).append('<label class="checkbox tweetbox"><img class="tweeticon icon-notwitter" src="/images/blank.gif" alt="" /><input class="tweetcheck hide" name="tweet" type="checkbox" /></label>');
				<?}?>

				//Submit buttons go in the div and we'll trigger form submission from outside
				$(pathToForm).append('<a class="btn btn-success mbsubmit ' + postId + '" href="#">Post</a> <a class="btn btn-error mbcancel ' + postId + '" href="#">Cancel</a>');

				//Attach click handlers to the buttons
				$(pathToBlog + ' a.mbsubmit.' + postId).unbind('click');
				$(pathToBlog + ' a.mbsubmit.' + postId).click(function() {
					$(pathToForm + '.' + postId).submit();
					return false;
				});
				$(pathToBlog + ' a.mbcancel.' + postId).unbind('click');
				$(pathToBlog + ' a.mbcancel.' + postId).click(function() {
					$(pathToPost + ' .footer').removeClass('mbmode');
					$(pathToBlog + '.' + postId).remove();
					return false;
				});

				//Ajaxify the form
				$(pathToForm + '.' + postId).ajaxForm({
	                <?if($device=="android") {?>
					dataType:       'html',
					<?} else {?>
					dataType:       'json',
					<?}?>
	                cache:          false,
	                clearForm:      true,
	                resetForm:      true,
	                timeout:        60000,
	                beforeSubmit:   function() {
						$(pathToBlog + '.' + postId).hide();    					                    
						$(pathToPost + ' .footer .inlineblogmsg').show();
	                },
	                success:        function(data) {
                        if(data.status == "false") {
                            showMessage( data.description, data.status, 5 );
							$(pathToPost + ' .footer .inlineblogmsg').hide();
							$(pathToBlog + '.' + postId).show();    					                    
                        } else {
                            showMessage( "Post Successful!", data.status, 5 );
							$(pathToPost + ' .footer').removeClass('mbmode').addClass('mbposted');
							$(pathToPost + ' .footer .inlineblogmsg').remove();
							$(pathToBlog + '.' + postId).remove();			  

							$(pathToPost).append('<div class="subitem">Comment Posted</div>');
                        }
	                },
	                error:          function(x, t, m) {
                        showMessage( "Error: " + m + "(" + t + ")", false, 60 );
						$(pathToPost + ' .footer .inlineblogmsg').hide();
   						$(pathToBlog + '.' + postId).show();    					                    
    	            }
				});
	
				//Set focus on this article and scroll to the
				//re-blog area
				focusThisArticleFooter(postId);

				//Set focus in description field
				$(pathToForm + '.' + postId + ' span textarea.mbdescription').focus();
				
		    	//Set the twitter toggle
		    	$(pathToForm + ' .tweetbox .tweeticon').removeClass('icon-twitter').addClass('icon-notwitter');
		    	$(pathToForm + ' .tweetbox .tweetcheck').prop('checked', false);
		    	$(pathToForm + ' .tweetbox .tweeticon').bind('click', function() {
		        	$(pathToForm + ' .tweetbox .tweetcheck').prop('checked', !$(pathToForm + ' .tweetbox .tweetcheck').prop('checked'));
		        	$(pathToForm + ' .tweetbox .tweeticon').toggleClass('icon-twitter');
		        	$(pathToForm + ' .tweetbox .tweeticon').toggleClass('icon-notwitter');
		        	$(pathToForm + ' .tweetbox .mbdescription').trigger('keyup');
		    	});

				return false;
        	});

        	return true;
	}


	//Modalize the enclosure links
	function _bindEnclosureLinks() {
		<?if( $g_platform != "mobile" ) {?>
        	$('.enclosureview a').unbind('click');
        	$('.enclosureview a').click(function() {
			if( $(this).children('img').length > 0 ) {
				openMediaShadowbox( $(this).children('img') );
				return false;
			}
        	});
		<?}?>
        	return true;
	}
	

	function _bindSubscribeLinks() {
        	//Bind some new clicks to the subscription links
        	$('.aSubscribe').click(function() {
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

    function _convertYoutube(url) {
	var ytcode;
        if ( ( url !== null ) && ( url !== "" ) && ( url.indexOf('youtube.com') > -1 || url.indexOf('youtu.be') > -1) ) {
                ytcode = url.split('v=')[1];
		if(typeof ytcode === 'undefined') {
			return false;
		}
                var ampersandPosition = ytcode.indexOf('&');
                if(ampersandPosition != -1) {
                        ytcode = ytcode.substring(0, ampersandPosition);
                }

                ytcode = 'http://www.youtube.com/embed/' + ytcode;
	        return ytcode;
        }

	return false;
    };

    function _isAvatar(url) {
        if ( url.indexOf('gravatar') != -1 ) { return true; }
        if ( url.indexOf('twimg.com/profile_images') != -1 ) { return true; }
	
	return false;
    };

    function _convertEnclosure(url, type) {
	if( type.indexOf('image') != -1 ) { return true; }

    }

    function _urlNotRelative(url) {
        if ( url.indexOf(systemUrl) == -1 && url.indexOf('http') == 0 ) { return true; }

        return false;
    };

    function _countEnclosuresOfType(enclosures, typecheck) {
	var cnt = 0;
        $.each(enclosures, function() {
	        if ( this.type.indexOf(typecheck) != -1 ) { 
			cnt++;
        	}
	});
	
	return cnt;
    };

    function _isImage(url, type) {
        if ( type.indexOf('image') != -1 ) {  return true;  }
        if ( url.indexOf('.jpg')   != -1 ) {  return true;  }
        if ( url.indexOf('.jpeg')   != -1 ) {  return true;  }
        if ( url.indexOf('.png')   != -1 ) {  return true;  }
        if ( url.indexOf('.gif')   != -1 ) {  return true;  }
	
	return false;
    };

    function _isAudio(url, type) {
        if ( type.indexOf('audio') != -1 ) {  return true;  }
        if ( url.indexOf('.mp3')   != -1 ) {  return true;  }
        if ( url.indexOf('.m4a')   != -1 ) {  return true;  }
        if ( url.indexOf('.wav')   != -1 ) {  return true;  }
        if ( url.indexOf('.ogg')   != -1 ) {  return true;  }
        if ( url.indexOf('.wma')   != -1 ) {  return true;  }
	
	return false;
    };

    function _isVideo(url, type) {
        if ( type.indexOf('video') != -1 ) {  return true;  }
        if ( url.indexOf('.mp4')   != -1 ) {  return true;  }
        if ( url.indexOf('.m4v')   != -1 ) {  return true;  }
        if ( url.indexOf('.avi')   != -1 ) {  return true;  }
        if ( url.indexOf('.mov')   != -1 ) {  return true;  }
        if ( url.indexOf('.wmv')   != -1 ) {  return true;  }
	
	return false;
    };

    function _isIframe(url, type) {
        if ( type.indexOf('text/html') != -1 ) {
		return true;
        }
        if ( type.indexOf('application/pdf') != -1 ) {
		return true;
        }
	
	return false;
    };

    function _bindEmbedActivations() {
	$('.enciframe.inactive').each(function(k, v) {
          var _this = this;
          console.log('['+k+']: ' + $(this).attr('data-src'));
          $(this).children('div.play').bind('click', function() {
	    var dsrc = $(_this).attr("data-src");
	    console.log("load video");
	    var $iframe = $('<iframe>', { class: 'encobj enciframe', src: dsrc, frameborder: 0 });
	    $(_this).replaceWith($iframe)
          });
        });

	return false;
    };

    function _getDomain(url) {
        var domain;
        if (( url !== null ) && ( url !== "" )) {
            domain = url.split('?')[0]; // cleans urls of form http://domain.com?a=1&b=2
            domain = domain.split('/')[2];
            if(typeof(domain) != "undefined") {
              domain = domain.replace("www.","").replace("www2.", "").replace("feedproxy.", "").replace("feeds.", "");
            } else {
	      return null;
	    }
        }
        return domain;
    };
    
    function _getFavicon(url) {
        return 'http://www.google.com/s2/favicons?domain=' + River.methods.getDomain(url);
    };
    
    function _getImages(html) {
        var ehtml = document.createElement('div');
        ehtml.innerHTML = html;

	var imgs = ehtml.getElementsByTagName("img");
	for( var i=0 ; i < imgs.length ; i++ ){
	    if( _urlNotRelative(imgs[i].src) ) {
		return(imgs[i].src);
	    }
	}

	return(false);
    }

    function _getVideos(html) {
        var ehtml = document.createElement('div');
        ehtml.innerHTML = html;

	var vids = ehtml.getElementsByTagName("video");
	for(var i=0;i<vids.length;i++){
	    var vid=vids[i];

            if ( vid.hasAttribute('src') ) {
	    	if ( _urlNotRelative(vid.src) ) { 
	    		return(vid.src);
            	}
            }

	    var sources=vid.getElementsByTagName("source");
            for(var n=0;n<sources.length;n++){
	    	var source=sources[n];
	        if ( _urlNotRelative(vid.src) ) { 
	            return(source.src);
            	} else {
		    return(false);
                }  
	    }
	}

        return(false);
    }

    function _getAudios(html) {
        var ehtml = document.createElement('div');
        ehtml.innerHTML = html;

	var vids = ehtml.getElementsByTagName("audio");
	for(var i=0;i<vids.length;i++){
	    var vid=vids[i];

            if ( vid.hasAttribute('src') ) {
	    	if ( _urlNotRelative(vid.src) ) { 
	    		return(vid.src);
            	}
            }

	    var sources=vid.getElementsByTagName("source");
            for(var n=0;n<sources.length;n++){
	    	var source=sources[n];
	        if ( _urlNotRelative(vid.src) ) { 
	            return(source.src);
            	} else {
		    return(false);
                }  
	    }
	}

        return(false);
    }

    function _getIframes(html) {
        var ehtml = document.createElement('div');
        ehtml.innerHTML = html;

	var vids = ehtml.getElementsByTagName("iframe");
	for(var i=0;i<vids.length;i++){
	    var vid=vids[i];
            if ( _urlNotRelative(vid.src) ) { 
	            return(vid.src);
            } else {
	    	    return(false);
            }
	}

	return(false);
    }

    function _newGetText(html) {
      var breakToken = '_______break_______',
      lineBreakedHtml = html.replace(/<br\s?\/?>/gi, breakToken).replace(/<p\.*?>(.*?)<\/p>/gi, breakToken + '$1' + breakToken);
      //return $('<div>').html(lineBreakedHtml).text().replace(new RegExp(breakToken, 'g'), '\n');
      return $('<div>').html(lineBreakedHtml).text().replace(new RegExp(breakToken, 'g'), '\n');
    }

    function _getText(html) {
        return $('<div>' + html + '</div>').text();
    };
    
    function _getMediaType(type) {
        return type.split('/')[0];
    };
    
    // http://blog.elctech.com/2009/01/06/convert-filesize-bytes-to-readable-string-in-javascript/
    function _getEnclosureSize(bytes) {
        var s, e, t;
        s = ['bytes', 'kB', 'MB', 'GB', 'TB'];
        e = Math.floor(Math.log(bytes) / Math.log(1024));
        t = (bytes / Math.pow(1024, Math.floor(e))).toFixed(2) + " " + s[e];
        return t;
    };
    
    // http://www.quirksmode.org/js/cookies.html
    function _setCookie(name, value, days) {
        var date, expires;
        if (days) {
            date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toGMTString();
        } else { 
            expires = "";
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    };

    function _getCookie(name) {
        var nameEQ, ca, c, i = 0;
        nameEQ = name + "=";
        ca = document.cookie.split(';');
        for (i; i < ca.length; i ++) {
            c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    };

    function _deleteCookie(name) {
        setCookie(name, "", -1);
    };

    function _htmlDecode(input){
        var e = document.createElement('div');
        e.innerHTML = input;
        return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
    }
    
    // Modified from http://ejohn.org/blog/javascript-pretty-date/#comment-297458
    function _prettyDate(date) {
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

	function _prependActiveFeed(feedId, feedTitle, feedUrl, urlForIcon, type) {
		<?if( $g_platform != "mobile" ) {?>
		if( $('#divActiveFeeds ul.feedlist li.' + feedId).length < 1  ) {
			var feedImg = '/images/blank.gif';
			if( type == 'person' ) {
				feedImg = urlForIcon;
				imgClass = 'avatar48';
			} else {
				feedImg = _getFavicon(urlForIcon);
				imgClass = 'favicon';
			}
			$('#divActiveFeeds ul.feedlist').prepend('<li class="' + type + ' ' + feedId + '">' + feedTitle + ' <img class="'+ imgClass +'" src="' + feedImg + '" /></li>');
			$('#divActiveFeeds ul.feedlist li.' + feedId).unbind('click');
			$('#divActiveFeeds ul.feedlist li.' + feedId).click(function () {
				$('#stream div.filternotice').remove();
				showAllItems();
				showOnlyItems(feedId);
				$('#stream').prepend('<div class="filternotice">Currently showing only items from '+feedTitle+'.<a class="removefilter">Show all</a></div>');
				$('#stream div.filternotice a.removefilter').click(function() {
					showAllItems();
					$('#stream div.filternotice').remove();
					return false;
				});
				return false;
			});
		}
		<?}?>
		return false;
	}

	function _appendActiveFeed(feedId, feedTitle, feedUrl, urlForIcon, type) {
		<?if( $g_platform != "mobile" ) {?>
		if( $('#divActiveFeeds ul.feedlist li.' + feedId).length < 1  ) {
			var feedImg = '/images/blank.gif';
			if( type == 'person' ) {
				feedImg = urlForIcon;
				imgClass = 'avatar48';
			} else {
				feedImg = _getFavicon(urlForIcon);
				imgClass = 'favicon';
			}
			$('#divActiveFeeds ul.feedlist').append('<li class="' + type + ' ' + feedId + '">' + feedTitle + ' <img class="'+ imgClass +'" src="' + feedImg + '" /></li>');
			$('#divActiveFeeds ul.feedlist li.' + feedId).unbind('click');
			$('#divActiveFeeds ul.feedlist li.' + feedId).click(function () {
				$('#stream div.filternotice').remove();
				showAllItems();
				showOnlyItems(feedId);
				$('html, body').animate({ scrollTop: '0px' }, 300); 		
				$('#stream').prepend('<div class="filternotice">Currently showing only items from '+feedTitle+'.<a class="removefilter">Show all</a></div>');
				$('#stream div.filternotice a.removefilter').click(function() {
					showAllItems();
					$('#stream div.filternotice').remove();
					return false;
				});
				return false;
			});
		}
		<?}?>
		return false;
	}

	function _alreadyUnsticky(postId) {
		var pp;
				
		pp = sessionStorage.getItem(postId);
		if( pp === null ) {  return false;  }
		pp = JSON.parse(pp);
        if( pp.sticky === false ) {  return true;  }

		return false;
	}
    
    return {
		sortGrid : _sortGrid,
        bindStickyLinks : _bindStickyLinks,
        bindFeedStickyLinks : _bindFeedStickyLinks,
        bindSubscribeLinks : _bindSubscribeLinks,
		bindCartLinks : _bindCartLinks,
		bindMicroblogLinks : _bindMicroblogLinks,
		bindEnclosureLinks : _bindEnclosureLinks,
        convertYoutube : _convertYoutube,
		isAvatar : _isAvatar,
        urlNotRelative : _urlNotRelative,
        countEnclosuresOfType : _countEnclosuresOfType,
		isImage : _isImage,
		isAudio : _isAudio,
        isVideo : _isVideo,
        isIframe  : _isIframe,
        bindEmbedActivations : _bindEmbedActivations,
        getDomain : _getDomain,
        getFavicon : _getFavicon,
		newGetText : _newGetText,
        getText : _getText,
        getMediaType : _getMediaType,
        getEnclosureSize : _getEnclosureSize,
        setCookie : _setCookie,
        getCookie : _getCookie,
        deleteCookie : _deleteCookie,
        htmlDecode : _htmlDecode,
        getImages : _getImages,
        getVideos : _getVideos,
        getAudios : _getAudios,
        getIframes : _getIframes,
        prettyDate : _prettyDate,
		prependActiveFeed : _prependActiveFeed,
		appendActiveFeed : _appendActiveFeed,
		alreadyUnsticky : _alreadyUnsticky
    };
}());


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
