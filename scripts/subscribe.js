//-----------------------------------------------------------------------------------
//Script support for the subscription management page
//-----------------------------------------------------------------------------------
$(document).ready( function() {
	//##: ----------------------------------
	//Make the checkboxes range selectable
	//##: ----------------------------------
        var lastChecked1 = null;
        var $chkboxes1 = $('.ulFeedsIFollow .selector');
        $chkboxes1.click(function(event) {
                if(!lastChecked1) {
                       lastChecked1 = this;
                       return;
                }

                if(event.shiftKey) {
                       var start = $chkboxes1.index(this);
                       var end = $chkboxes1.index(lastChecked1);

                       $chkboxes1.slice(Math.min(start,end), Math.max(start,end)+ 1).attr('checked', lastChecked1.checked);
                }

                lastChecked1 = this;
        });
        var lastChecked2 = null;
        var $chkboxes2 = $('.ulManagedLists .selector');
        $chkboxes2.click(function(event) {
                if(!lastChecked2) {
                       lastChecked2 = this;
                       return;
                }

                if(event.shiftKey) {
                       var start = $chkboxes2.index(this);
                       var end = $chkboxes2.index(lastChecked2);

                       $chkboxes2.slice(Math.min(start,end), Math.max(start,end)+ 1).attr('checked', lastChecked2.checked);
                }

                lastChecked2 = this;
        });



	//##: ----------------------------------
	//The feed subscription form
	//##: ----------------------------------
        $('#frmSubscribe').ajaxForm({
                dataType:       'json',
                timeout:        60000,
                beforeSubmit:   function() {
                        $('#frmSubscribe .imgSpinner').show();
                        $('#frmSubscribe .btnSubmit').attr("disabled", true);
                },
                success:        function(data) {
                        if(data.status == "false") {
				showMessage( data.description, data.status, 5 );
                        } else {
				showMessage( data.description, data.status, 5 );
                                window.location = "/subscribe";
                        }
                        $('#frmSubscribe .imgSpinner').hide();
                        $('#frmSubscribe .btnSubmit').attr("disabled", false);
                }
        });
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//The feed finder form
	//##: ----------------------------------
        $('.feedFinderForm').ajaxForm({
                dataType:       'json',
                timeout:        60000,
                beforeSubmit:   function() {
                        $('.imgSpinner').show();
                        $('.feedFinderForm .btnSubmit').attr("disabled", true);
                },
                success:        function(data) {
                        if(data.status == "false") {
				showMessage( data.description, data.status, 5 );
                        } else {
				showMessage( data.description, data.status, 5 );
                                window.location = "/subscribe";
                        }
                        $('.imgSpinner').hide();
                        $('.feedFinderForm .btnSubmit').attr("disabled", false);
                }
        });
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
        //Bind events to the file import button
	//##: ----------------------------------
        $('#btnFileShow').click( function() {
                $('#fileOpml').trigger('click');
        });
        $('#fileOpml').change( function() {
		$('#btnSubscribe').trigger('click');
        });
	//##: ----------------------------------
	//##: ----------------------------------

	//##: ----------------------------------
	//Set up the main unsubscribe button
	//##: ----------------------------------
	$('#btnUnsubscribe').click( function() {
		var feeds = [];
                var cgiurl = $('#cgiUrls').attr("data-unsubscribe");

		$('.ulFeedsIFollow li .selector').each(function() {
			if( $(this).is(':checked') ) {
				feeds.push( $(this).parent().attr("data-id") );
			}
		});

		//Check for empty result set
		if( feeds.length < 1 ) {
			alert("There are no feeds selected for this action.");
			return(false);
		}

		//Assemble a form submission with this array of feed id's
		var newform = $( document.createElement('form') );
		newform.attr('name', 'newform');
		newform.attr('id', 'frmOnTheFly');
		newform.attr('method', 'POST');
		newform.attr('action', cgiurl);
		for( var i = 0; i < feeds.length; i++ ) {
		  newform.append('<input type="hidden" name="id[]" value="' + feeds[i] + '" />');
                }

		//Confirm and then submit the form		
                if(confirm("Unsubscribe from " + feeds.length + " feeds?") == false) {
			newform.remove();
                        return false;
                } else {
		        newform.ajaxForm({
                		dataType:       'json',
		                timeout:        60000,
		                beforeSubmit:   function() {
                		        $('.mainControls .imgSpinner').show();
		                        $('.mainControls button').attr("disabled", true);
                		},
		                success:        function(data) {
                		        if(data.status == "false") {
						showMessage( data.description, data.status, 5 );
		                        } else {
						showMessage( data.description, data.status, 5 );
                                		window.location.reload();
		                        }
		                        $('.mainControls .imgSpinner').hide();
		                        $('.mainControls button').attr("disabled", false);
		                }
		        });
			newform.submit();
			newform.remove();
		}

                return false;
	});
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//Set up the main add to list button
	//##: ----------------------------------
	$('#btnAddToList').click( function() {
		var feeds = [];
		var lists = [];
                var cgiurl = $('#cgiUrls').attr("data-addfeedstolist");

		//Get selected feeds
		$('.ulFeedsIFollow li .selector').each(function() {
			if( $(this).is(':checked') ) {
				feeds.push( $(this).parent().attr("data-id") );
			}
		});
		if( feeds.length < 1 ) {
			alert("There are no feeds selected for this action.");
			return(false);
		}

		//Get selected lists
		$('.ulManagedLists li .selector').each(function() {
			if( $(this).is(':checked') ) {
				lists.push( $(this).parent().attr("data-id") );
			}
		});
		if( lists.length < 1 ) {
			alert("There are no lists selected for this action.");
			return(false);
		}

		//Assemble a form submission with this array of feed id's
		var newform = $( document.createElement('form') );
		newform.attr('name', 'newform');
		newform.attr('id', 'frmOnTheFly');
		newform.attr('method', 'POST');
		newform.attr('action', cgiurl);
		for( var i = 0; i < feeds.length; i++ ) {
		  newform.append('<input type="hidden" name="fid[]" value="' + feeds[i] + '" />');
                }
		for( var i = 0; i < lists.length; i++ ) {
		  newform.append('<input type="hidden" name="oid[]" value="' + lists[i] + '" />');
                }

		//Confirm and then submit the form		
                if(confirm("Assign " + feeds.length + " feeds to " + lists.length + " lists?") == false) {
			newform.remove();
                        return false;
                } else {
		        newform.ajaxForm({
                		dataType:       'json',
		                timeout:        60000,
		                beforeSubmit:   function() {
                		        $('.mainControls .imgSpinner').show();
		                        $('.mainControls button').attr("disabled", true);
                		},
		                success:        function(data) {
                		        if(data.status == "false") {
						showMessage( data.description, data.status, 5 );
		                        } else {
						showMessage( data.description, data.status, 5 );
                                		window.location.reload();
		                        }
		                        $('.mainControls .imgSpinner').hide();
		                        $('.mainControls button').attr("disabled", false);
		                }
		        });
			newform.submit();
			newform.remove();
		}

                return false;
	});
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//Set up the main remove from list button
	//##: ----------------------------------
	$('#btnRemoveFromList').click( function() {
                var cgiurl = $('#cgiUrls').attr("data-removefeedsfromlist");

		//Assemble a form submission with this array of feed id's
		var newform = $( document.createElement('form') );
		newform.attr('name', 'newform');
		newform.attr('id', 'frmOnTheFly');
		newform.attr('method', 'POST');
		newform.attr('action', cgiurl);

		//Make sure something is checked before continuing
		if( $('.ulManagedLists li .selector:checked').length < 1 ) {
			alert("There are no list feeds selected for this action.");
			newform.remove();
			return(false);
		}

		//Get selected lists items
		var fdcount = 0;
		$('.ulManagedLists li .selector').each(function() {
			if( $(this).is(':checked') ) {
				var listid = $(this).parent().parent().parent().attr("data-id");
				var feedid = $(this).parent().attr("data-id");
		                newform.append('<input type="hidden" name="ids[' + listid + '][]" value="' + feedid + '" />');
				fdcount++;
			}
		});

		//Confirm and then submit the form		
                if(confirm("Remove these " + fdcount + " feeds from their respective lists?") == false) {
			newform.remove();
                        return false;
                } else {
		        newform.ajaxForm({
                		dataType:       'json',
		                timeout:        60000,
		                beforeSubmit:   function() {
                		        $('.mainControls .imgSpinner').show();
		                        $('.mainControls button').attr("disabled", true);
                		},
		                success:        function(data) {
                		        if(data.status == "false") {
						showMessage( data.description, data.status, 5 );
		                        } else {
						showMessage( data.description, data.status, 5 );
                                		window.location.reload();
		                        }
		                        $('.mainControls .imgSpinner').hide();
		                        $('.mainControls button').attr("disabled", false);
		                }
		        });
			newform.submit();
			newform.remove();
		}

                return false;
	});
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//Set up the main add to new list button
	//##: ----------------------------------
	$('#btnAddToNewList').click( function() {
		var feeds = [];
                var cgiurl = $('#cgiUrls').attr("data-addfeedstonewlist");

		//Get selected feeds
		$('.ulFeedsIFollow li .selector').each(function() {
			if( $(this).is(':checked') ) {
				feeds.push( $(this).parent().attr("data-id") );
			}
		});
		if( feeds.length < 1 ) {
			alert("There are no feeds selected for this action.");
			return(false);
		}

		//We need a name for this list
		var listname = prompt("What will we name this list?");
		if( listname == null || listname == '') {
			alert("Sorry, lists must have names.");
			return(false);
		}

		//Assemble a form submission with this array of feed id's
		var newform = $( document.createElement('form') );
		newform.attr('name', 'newform');
		newform.attr('id', 'frmOnTheFly');
		newform.attr('method', 'POST');
		newform.attr('action', cgiurl);
                newform.append('<input type="hidden" name="listname" value="' + listname + '" />');
		for( var i = 0; i < feeds.length; i++ ) {
		  newform.append('<input type="hidden" name="fid[]" value="' + feeds[i] + '" />');
                }

		//Confirm and then submit the form		
                if(confirm("Assign " + feeds.length + " feeds to a new list called '" + listname + "'?") == false) {
			newform.remove();
                        return false;
                } else {
		        newform.ajaxForm({
                		dataType:       'json',
		                timeout:        60000,
		                beforeSubmit:   function() {
                		        $('.mainControls .imgSpinner').show();
		                        $('.mainControls button').attr("disabled", true);
                		},
		                success:        function(data) {
                		        if(data.status == "false") {
						showMessage( data.description, data.status, 5 );
		                        } else {
						showMessage( data.description, data.status, 5 );
                                		window.location.reload();
		                        }
		                        $('.mainControls .imgSpinner').hide();
		                        $('.mainControls button').attr("disabled", false);
		                }
		        });
			newform.submit();
			newform.remove();
		}

                return false;
	});
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//Expand people on hover
	//##: ----------------------------------
        $('#divSubscribePeople .ulPeople .liPerson').click( function() {
		hidePeople('#divSubscribePeople .ulPeople .liPerson');
        	$(this).switchClass('smallPerson', 'bigPerson', 300);
                $(this).children('.personcaption').switchClass('personcaption-block', 'personcaption-inline', 0);
		$(this).children('.personList').show();
		$(this).children('.persondrop').show();
	});
	function hidePeople(peopleSelector) {
		$(peopleSelector).children('.persondrop').hide();
		$(peopleSelector).children('.personList').hide();
               	$(peopleSelector).children('.personcaption').switchClass('personcaption-inline', 'personcaption-block', 0);
               	$(peopleSelector).switchClass('bigPerson', 'smallPerson', 200);

		return(true);
	}
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//Expand lists on hover
	//##: ----------------------------------
        $('.ulManagedLists .liManagedList').hover(
		function() {
			$(this).children('.outlineList').show();
		},
		function() {
			if( $(this).find('.ulListFeeds .selector:checked').length < 1 ) {
				$(this).children('.outlineList').hide();
			}
		}
	);
        $('.ulOutsideLists .liOutsideList').hover(
		function() {
			$(this).children('.outlineList').show();
		},
		function() {
			$(this).children('.outlineList').hide();
		}
	);
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//Clear all checkboxes
	//##: ----------------------------------
	$('input:checkbox').removeAttr('checked');
	//##: ----------------------------------
	//##: ----------------------------------


	//##: ----------------------------------
	//Do other work at startup
	//##: ----------------------------------
	rebindEverything();
	//##: ----------------------------------
	//##: ----------------------------------
});



//-----------------------------------------------------------------------------------
//Functions
//-----------------------------------------------------------------------------------
function searchPostLoad() {
	rebindEverything();
}

function rebindEverything() {
	bindUnsubLinks();
        bindStickyButtons();
        bindHiddenButtons();
}

function bindUnsubLinks() {
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

function bindStickyButtons() {
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

function bindHiddenButtons() {
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

function adjustPageLayout() {
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
//-----------------------------------------------------------------------------------
