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
	key('t', function() {		//----- Target the first visible article
		focusFirstVisibleArticle();
		return false;
        });
	key('down', function() {	//----- Jump to next section
		focusNextSection();
		return false;
        });
	key('up', function() {		//----- Jump to previous section
		focusPreviousSection();
		return false;
        });
	key('right', function() {	//----- Jump to next article
		focusNextArticle();
		return false;
        });
	key('left', function() {	//----- Jump to previous article
		focusPreviousArticle();
		return false;
        });
	key('u', function() {		//----- Un-sticky the currently targeted item
		if( $("#stream-items div.article.activeItem").hasClass("sticky") ) {
			$("#stream-items div.article.activeItem .aUnSticky").trigger('click');
			focusNextArticle();
		}
		return false;
        });
	key('c', function() {		//----- Cartulize the currently targeted item
		$("#stream-items div.article.activeItem .cartlink").trigger('click');
		return false;
        });
	key('m', function() {		//----- Microblog the currently targeted item
		$("#stream-items div.article.activeItem .rtlink").trigger('click');
		return false;
        });
	key('o', function() {		//----- Open original link of the currently targeted item
		if( $("#stream-items div.article.activeItem .header a.articlelink").length > 0 ) {
			window.open($("#stream-items div.article.activeItem .header a.articlelink").attr('href'));
		}
                return false;
        });
});


