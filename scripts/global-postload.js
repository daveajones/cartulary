$(document).ready( function () {
        // Setup the search form in the navbar.  This is globally done, but the action is unique
	// for each page, based on how the html_menubar template sets the action attribute of the
        // form.
        $('#navsearch').ajaxForm({
                dataType:       'json',
                cache:          false,
                timeout:        30000,
                beforeSubmit:   function() {
                        $('.searchbar').hide();
			//Don't try to process empty queries
                        if( $('#navsearchq').val() == '' ) {
                                return(false);
                        }
			//Give some visual indication that we're waiting for results
			//and lock the query box
                        $('#navSpinner').show();
                        $('#navsearchq').attr("disabled", true);
			//Set a default content in case no results are returned.
                        $('.searchbar .body').append('<p>No Results</p>');
                },
                success:        function(data) {
			//If an error occured on the backend, give a warning message
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
				data.query = '';
                                $('.searchbar .body').empty();
                                $('.searchbar .body').append('<center><p>Error during search: ' + data.description + '</p></center>');
                        } else {
				//If we got a blank result, then say so
        	                var results = data.data;
                	        $('.searchbar .body').empty();
				if( results.length < 1 ) {
        		                $('.searchbar .body').append('<p>No Results</p>');
				} else {
                                        $('#search-' + data.section).tmpl(data).appendTo('.searchbar .body');
				}

				//Position the search results box to where we're at now
				$('.searchbar').css('top', $('.navbar').offset().top + 60 + 'px');

				//Show the results
				$('.searchbar .head h4 small').empty().append('(' + data.query + ') max:' + data.max);
                	        $('.searchbar').show();
				$('.searchbar .head .btnCloseSearchResults').unbind('click');
				$('.searchbar .head .btnCloseSearchResults').click( function() {
					$('.searchbar').hide();
        	                        $('.searchbar .body').empty();
					return false;
				});
                        }

			//After the results are appended, call a post-search function if one is defined
                        freedomController.v1.search.methods.postLoad(data.section);

                        //Unlock the search box, hide the spinner and collapse the navbar
                        $('#divMainMenu .navbar .btn-navbar').trigger('click');
                        $('#navSpinner').hide();
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

        key('ctrl+s', function() {           //----- Focus search box
                $('#navsearchq').focus();
                return false;
        });

});
