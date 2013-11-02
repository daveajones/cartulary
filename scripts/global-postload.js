$(document).ready( function () {
        // Setup the search form in the navbar.  This is globally done, but the action is unique
	// for each page, based on how the html_menubar template sets the action attribute of the
        // form.
	(function() {
	var lastQuery = "";
        $('#navsearch').ajaxForm({
                dataType:       'json',
                cache:          false,
                timeout:        30000,
                beforeSubmit:   function() {
                        $('.searchbar').hide();
                        $('.searchbar .body').empty();
			$('.searchbar .search-more').empty();
			$('.searchbar .search-extra').remove();
			//Don't try to process empty queries
                        if( $('#navsearchq').val() === '' ) {
                                return(false);
                        } else {
				lastQuery = $('#navsearchq').val();
			}
			//Give some visual indication that we're waiting for results
			//and lock the query box
                        $('#navSpinner').show();
                        $('#navsearchq').attr("disabled", true);
                },
                success:        function(data) {
			//If an error occured on the backend, give a warning message
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
				data.query = '';
                                $('.searchbar .body').append('<center><p>Error during search: ' + data.description + '</p></center>');
                        } else {
				//Parse the returned data into the appropriate section template
                                $('#search-' + data.section).tmpl(data).appendTo('.searchbar .body');

				//Print 'no results' if there was no data returned
			        if( data.data.length < 1 ) {
			                $('.searchbar .body').append('<p>No Results</p>');
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
                        freedomController.v1.search.methods.postLoad(lastQuery, data.section, '.searchbar');

                        //Unlock the search box, hide the spinner and collapse the navbar
                        $('#divMainMenu .navbar .btn-navbar').trigger('click');
                        $('#navSpinner').hide();
                        $('#navsearchq').attr("disabled", false);                       
               }
        });
	})();

    //Side drawer handler
    $('#slideout > .drawertab').click(function() {
        $('#slideout').toggleClass('open');
        return false;
    });

	//Bindings
	freedomController.v1.people.methods.bindSocialOutlineLinks( "body" );


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
