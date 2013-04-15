$(document).ready( function () {
        // Setup the search form in the navbar.  This is globally done, but the action is unique
	// for each page, based on how the html_menubar template sets the action attribute of the
        // form.
        $('#navsearch').ajaxForm({
                dataType:       'json',
                cache:          false,
                timeout:        30000,
                beforeSubmit:   function() {
			//Don't try to process empty queries
                        if( $('#navsearchq').val() == '' ) {
                                return(false);
                        }
			//Give some visual indication that we're waiting for results
			//and lock the query box
                        $('#navSpinner').show();
                        $('#navsearchq').attr("disabled", true);
			//Set a default content in case no results are returned.
                        $('#divNavSearchResults').append('<p>No Results</p>');
                },
                success:        function(data) {
			//If an error occured on the backend, give a warning message
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
				data.query = '';
                                $('#divNavSearchResults').empty();
                                $('#divNavSearchResults').append('<center><p>Error during search: ' + data.description + '</p></center>');
                        } else {
				//If we got a blank result, then say so
        	                var results = data.data;
                	        $('#divNavSearchResults').empty();
				if( results.length < 1 ) {
        		                $('#divNavSearchResults').append('<p>No Results</p>');
				} else {
                                        $('#search-template').tmpl(data).appendTo('#divNavSearchResults');
				}
                        }

                        //Initialize the popover with the contents of the hidden div
                        $('#divSearchResultsHook').data('popover', null).popover({
                                animation:true,
                                html:true,
                                placement:'bottom',
                                trigger:'manual',
                                title:'Search Results for "<span class="popoverquery">' + data.query + '</span>" <button class="close btnCloseSearchResults">&times;</button>',
                                content: function(ele) { return $('#divNavSearchResults').html(); },
				template: '<div class="popover searchpop <?echo $section?>-searchpop"><div class="arrow"></div><div class="popover-inner popSearchResults"><h4 class="popover-title"></h4><div class="popover-content"><p></p></div></div></div>'
                        });

			//Show the popover
                        $('#divSearchResultsHook').popover('show');
			$('.btnCloseSearchResults').click( function() {
				$('#divSearchResultsHook').popover('hide');
			});
					//After the results are appended, call a post-search function if one is defined
                                        if( typeof searchPostLoad == 'function' ) {
						searchPostLoad();
					}

                        //Unlock the search box, hide the spinner and collapse the navbar
                        $('#navSpinner').hide();
                        $('#divMainMenu .navbar .btn-navbar').trigger('click');
                        $('#navsearchq').attr("disabled", false);
               }
        });


        //Ajaxify the social outline links
        $('.sopmllink').click(function() {
                        var aobj = $(this);
                        var href = aobj.attr("data-href");
	                var cgiurl = $('#cgiUrls').attr("data-getsopml");

                        $('#mdlSocialOutlineView .modal-header').hide();
                        $('#mdlSocialOutlineView .modal-body .sobody').hide();
                        $('#mdlSocialOutlineView .modal-footer').hide();
                        $('#mdlSocialOutlineView .soserver .serverlink').empty();
                        $('#mdlSocialOutlineView .soname').empty();
                        $('#mdlSocialOutlineView .soavatar').attr('src', '/images/blank.gif');
                        $('#mdlSocialOutlineView .sopubfeeds').empty();
                        $('#mdlSocialOutlineView .spinner').show();

                        modalFullHeight('#mdlSocialOutlineView', true);
                        $('#mdlSocialOutlineView').modal('show');
                        $.ajax({
                                url:      cgiurl + '?url=' + href,
                                type:     "GET",
                                dataType: 'json',
                                timeout:  30000,
                                success:  function(data) {
                                                $('#mdlSocialOutlineView .spinner').hide();
                                                if(data.status == "false") {
                                                        $('#mdlSocialOutlineView .sotitle').append(data.title);
                                                        $('#mdlSocialOutlineView .sobody').append(data.description);
                                                } else {
                                                        modalFullHeight('#mdlSocialOutlineView', false);
							//Set the server link
                                                        $('#mdlSocialOutlineView .soserver .serverlink').append(data.server);
                                                        $('#mdlSocialOutlineView .soserver .serverlink').attr('href', 'http://' + data.server);
							//Set the name
                                                        $('#mdlSocialOutlineView .soname').append(data.ownername);
							//Set the avatar
							if( data.avatarurl == '' ) {
								data.avatarurl = '/images/noavatar.png';
							}
                                                        $('#mdlSocialOutlineView .soavatar').attr('src', data.avatarurl);
							//Display the pub feeds list
							for( var i = 0 ; i < data.feeds.pub.length ; i++ ) {
								$('#mdlSocialOutlineView .sopubfeeds').append('<li><a href="' + data.feeds.pub[i].url + '"><img class="icon-feed-raw" src="/images/blank.gif" alt="" /></a><a href="' + data.feeds.pub[i].html + '">' + data.feeds.pub[i].text + '</a></li>');
							}
							//Display some recent activity for this user
							$('#mdlSocialOutlineView .soactivity').append('');

                                                        $('#mdlSocialOutlineView .sofooter .link').attr('href', data.url);
                                                        $('#mdlSocialOutlineView .sofooter').show();
                                                }
                                                $('#mdlSocialOutlineView .modal-header').show();
                                                $('#mdlSocialOutlineView .modal-body .sobody').show();
                                                $('#mdlSocialOutlineView .modal-footer').show();		
                                        },
                                error:  function(x, t, m) {
                                                $('#mdlSocialOutlineView .modal-header').show();
                                                //$('#mdlSocialOutlineView .modal-footer').show();
                                                $('#mdlSocialOutlineView .spinner').hide();
                                                $('#mdlSocialOutlineView .sotitle').append('');
                                                $('#mdlSocialOutlineView .sobody').append('<p>Error communicating with server. Connection problem?</p>');
                                }
                        });
                        return false;
        });


        //Keyboard shortcuts
        key('ctrl+m', function() {           //----- Spawn a new microblog post modal
		<?if( $section == "Microblog" ) {?>
		alert("Stop it. That'd be silly.");
		<?}else{?>
                newMicroblogPostWindow();
		<?}?>
                return false;
        });

});
