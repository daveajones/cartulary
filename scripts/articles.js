<!--
$(document).ready( function() {
        var lastChecked = null;
	var $chkboxes = $('.exparticle');
        $chkboxes.click(function(event) {
        	if(!lastChecked) {
	 	       lastChecked = this;
	               return;
	        }

	        if(event.shiftKey) {
	               var start = $chkboxes.index(this);
	               var end = $chkboxes.index(lastChecked);

	               $chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).attr('checked', lastChecked.checked);
	        }

	        lastChecked = this;
        });

	//Ajaxify the article export form
	$('#frmArticleExport').ajaxForm({
        	dataType:       'json',
                cache:          'false',
                beforeSubmit:   function() {
                        $('#imgSpinner').show();
	                $('#btnSubmit').attr("disabled", true);
        	},
                success:        function(data) {
                	if(data.status == "false") {
			        showMessage( data.description, data.status, 5 );
                        } else {
			        showMessage( data.description +  ' <a href="' + data.url + '">Open</a> or <a href="http://127.0.0.1:5337/opmlEditor/editOutlineButton/open?root='+ data.url +'&title=Export">Edit</a>', data.status, 60 );
        	        }
        	}
	});

        //Set up the article deletion links
	$('.aDeleteArticle').click(function() {
		var aobj = $(this);
		var delurl = aobj.attr("href");
		var rowid = aobj.parent().parent().attr("id");
		var artitle = aobj.parent().parent().attr("data-artitle");
    		if(confirm("Delete \"" + artitle + "\"?") == false) {
			return false;
                } else {
	                $.ajax({
				url: delurl,
				type: "GET",
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

        //If any articles are check-marked, the opml icon becomes an export button
        $('#aOpmlExport').click( function() {
		if( $('.exparticle:checked').length > 0 ) {
			$('#frmArticleExport').submit();
			return false;
		}
	});

	//Set up date pickers
        $('#start-date').datepicker({showOn:"button",buttonImage:"/images/glyph/glyphicons_halflings_108_calendar.png"});
        $('#end-date').datepicker({showOn:"button",buttonImage:"/images/glyph/glyphicons_halflings_108_calendar.png"});
        <?if($platform == "mobile" || $platform == "tablet") {?>
	$('.showdatepicker').click( function() {
		$('#date-line').show();	
	});
	<?}?>
	$('#btnSubmitDates').click( function() {
		$('#start-date').prop('disabled', false);
		$('#end-date').prop('disabled', false);
	});
});
-->
