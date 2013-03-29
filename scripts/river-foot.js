$(document).ready( function() {
        // Ajaxify the quick blog
        $('#frmSaySomething').ajaxForm({
                dataType:       'json',
                cache:          false,
                clearForm:      true,
                resetForm:      true,
                timeout:        30000,
                beforeSubmit:   function() {
                        if( $('#txtSaySomething').val() == '' ) {
                                return(false);
                        }
                        $('#imgSpinner').show();
                        $('#btnSaySomething').attr("disabled", true);
                },
                success:        function(data) {
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
                        } else {
                                resetQuickBlogBox();
                                reloadMicroblogWidget();
                        }
                        $('#imgSpinner').hide();
                        $('#btnSaySomething').attr("disabled", false);
               }
        });

	//Ajaxify the addpubfeed form
        $('#frmAddPubFeed').ajaxForm({
                dataType:       'json',
                cache:          false,
                clearForm:      true,
                resetForm:      true,
                timeout:        30000,
                beforeSubmit:   function() {
                        $('#imgPubFeedSpinner').show();
                        $('#btnPubFeedSubmit').attr("disabled", true);
                },
                success:        function(data) {
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
                        } else {
                                showMessage( data.description, data.status, 5 );
                                $('#stream-sidebar-right #ulMyStuff').append('<li><a href="' + data.url  + '">' + data.title  + '</a></li>');
                        }
                        $('#imgPubFeedSpinner').hide();
                        $('#btnPubFeedSubmit').attr("disabled", false);
                }
        });

	//Ajaxify the delete pub feed links
                $('.aDeletePubFeed').click(function() {
                        var aobj = $(this);
                        var delurl = aobj.attr("href");
                        var rowid = aobj.parent().attr("data-url");
                        var title = aobj.parent().attr("data-title");
                        if(confirm("Remove '" + title + "' from your social outline?") == false) {
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
                                                        $("li[data-url='" + rowid + "']").remove();
                                                        showMessage( data.description, data.status, 5 );
                                                }
                                        }
                                });
                        }
                        return false;
                });


        //Quickblog stuff
        $('#txtSaySomething').focus(function() {
                $(this).switchClass('smallSaySomething', 'bigSaySomething', 300);
        });
        $('#txtSaySomething').blur(function() {
		if( isEmpty($('#txtSaySomething').val()) ) {
	                $(this).switchClass('bigSaySomething', 'smallSaySomething', 300);
		}
        });
        $('#imgTwitter').bind('click', function() {
                $('#chkTwitter').prop("checked", !$('#chkTwitter').prop("checked"));
                $('#imgTwitter').toggleClass('icon-twitter');
                $('#imgTwitter').toggleClass('icon-notwitter');
        });


	//Keyboard shortcuts
	key('down', function() {
		var vid = 0;
		$("#stream-items div.section").each(function(index) {
			if( $(document).scrollTop() < $(this).position().top ) {
				if( vid == 1 ) {
					$('html, body').animate( { scrollTop: ($(this).position().top - 3) }, 300);
					return false;
				}
				vid++;
			}
		});
		return false;
        });
	key('up', function() {
		$("#stream-items div.section").each(function(index) {
			if( $(document).scrollTop() < $(this).position().top ) {
				$('html, body').animate( { scrollTop: ($("#stream-items div.section:eq("+(index - 1)+")").position().top - 3) }, 300); 
				return false;
			}
		});
		return false;
        });
	key('right', function() {
		var vid = 0;
		$("#stream-items div.article").each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				if( vid == 1 ) {
					$('html, body').animate( { scrollTop: ($(this).offset().top) }, 300);
					return false;
				}
				vid++;
			}
		});
		return false;
        });
	key('left', function() {
		$("#stream-items div.article").each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$('html, body').animate( { scrollTop: ($("#stream-items div.article:eq("+(index - 1)+")").offset().top) }, 300); 
				return false;
			}
		});
		return false;
        });
});


