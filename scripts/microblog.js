function loadPostList() {
	//FOr now just redirect
	window.location = "<?echo $microblogpage?>";

	//Clear the table
	$('#divPostList ul').empty();

	//Get fresh data and populate the table if successful
        $.ajax({
	        url: "<?echo $postlistcgi?>",
                type: "GET",
                async: false,
                dataType:       'json',
                success:        function(data) {
                        if(data.status == "false") {
                                showMessage( data.description, data.status, 5 );
			} else if(data.status == "noposts") {
                	        $('#divPostList').append('<p>You have not posted anything yet. Say something!</p>');
                        } else {
                                for( var i=0 ; i < data.posts.length && i <= <?echo $max?>; i++ ) {
					if(data.posts[i]['url'] != "") {
	                	          $('#divPostList ul:last').append('<li><a class="aDeletePost" href="<?echo $deletepostcgi?>?id=' + data.posts[i]['id'] + '"><i class="icon-remove"></i></a><a href="' + data.posts[i]["url"] + '">' + data.posts[i]["content"] + '</a></li>');
                                        } else {
	                	          $('#divPostList ul:last').append('<li><a class="aDeletePost" href="<?echo $deletepostcgi?>?id=' + data.posts[i]['id'] + '"><i class="icon-remove"></i></a>' + data.posts[i]["content"] + '</li>');
					}
				}
                        }
                }
	});
}

function showEnclosures() {
	$('#divEnclosures').show();
}

$(document).ready( function() {
		var linkExtraction = true;

        	$('.aDeletePost').click(function() {
                	var aobj = $(this);
                	var delurl = aobj.attr("href");
                	var rowid = aobj.parent().attr("id");
                	var title = aobj.parent().attr("data-title");
                	if(confirm("Delete " + title + "?") == false) {
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
                                                	$('#'+rowid).remove();
                                        	}
						<?if($device=="android") {?>alert("Post deleted.");<?}?>
						loadPostList();
                                	}
                        	});
                	}
                	return false;
        	});

		$('#btnAttachFile').bind('click', function() {
			$('#divUpload').show();
		});

		$('#aUploadTrigger').bind('click', function() {
			$('#divEnclosures').show();
			$('#file_upload').uploadifive('upload');
		});

		//Set up the twitter check-box, img interaction
	        $('#imgTweet').bind('click', function() {
        	        $('#chkTweet').prop("checked", !$('#chkTweet').prop("checked"));
                	$('#imgTweet').toggleClass('icon-twitter');
	                $('#imgTweet').toggleClass('icon-notwitter');
        	});
		if( $('#chkTweet').is(":checked") ) {
               		$('#imgTweet').removeClass('icon-notwitter').addClass('icon-twitter');
		} else {
	               	$('#imgTweet').removeClass('icon-twitter').addClass('icon-notwitter');
		}
	        $('#chkTweet').bind('change', function() {
                	$('#imgTweet').toggleClass('icon-twitter');
	                $('#imgTweet').toggleClass('icon-notwitter');
        	});


                $('#frmBlogPost').ajaxForm({
                        <?if($device=="android") {?>dataType:       'html',<?
                        } else {?>dataType:       'json',<?}?>
			cache:		false,
                        clearForm:	true,
			resetForm:	true,
			timeout:	30000,
                        beforeSubmit:   function() {
                                $('#imgSpinner').show();
                                $('#btnSubmit').attr("disabled", true);
                        },
                        success:        function(data) {
                                if(data.status == "false") {
	                                showMessage( data.description, data.status, 5 );
                                } else {
					<?if($device=="android") {?>alert('Post successful.');<?}?>
					$('#txtContent').val('');
                                        $('#txtLink').val('');
					$('#divRtSource').empty();
					$('#divExtEnclosures').empty();
				        <?if($prefs['mbreturnhome'] == 1) {?>
					window.location = '<?echo $startpage?>';
                                        <?} else {?>
					loadPostList();
					<?}?>
                                }
                                $('#imgSpinner').hide();
                                $('#btnSubmit').attr("disabled", false);
				$('#spnCharCount').text( <?echo $default_blog_post_max_chars?> - $('#txtContent').val().length );
                        }
                });

		//Set up some paste handling to catch pasted links
		$('#txtContent').bind('paste', function() {
                        //Is there a link in this content?
                        if(linkExtraction == true) {
				setTimeout(function() {
                                var linkStart = $('#txtContent').val().indexOf('http');
                                if( (linkStart > -1) ) {
                                        $('#txtLink').val( trim($('#txtContent').val().substr(linkStart)) );
                                        $('#divLink').show();
                                        linkExtraction = false;
                                }
				}, 100);
                        }
		});


		//Set up a character count tracker for the post content box
		$('#txtContent').bind('keyup', function () {
                        var contlen = $('#txtContent').val().length;
                        var tweetlen = 138;

			//Is there a link in this content?
			if(linkExtraction == true) {
				var linkStart = $('#txtContent').val().indexOf('http');
        	                var linkEnd = $('#txtContent').val().indexOf(' ', linkStart);
				if( (linkStart > -1) && (linkEnd > linkStart) ) {
					$('#txtLink').val( $('#txtContent').val().substr(linkStart,(linkEnd - linkStart)) );
					$('#divLink').show();
					linkExtraction = false;
				}
			}

			//Test and set
			$('#spnCharCount').text( contlen );
                        if( contlen > 64000 ) {
				$('#spnCharCount').addClass('msgbad');
				$('#divBlogPost #btnSubmit').addClass('btn-danger');
				$('#divBlogPost #btnSubmit').addClass('disabled');
				$('#divBlogPost #btnSubmit').val('Too long!');
				$('#spnTweetWarning').show();
                        <?if( twitter_is_enabled($uid) ) {?>
			} else if ( (contlen > tweetlen) && $('#chkTweet').is(':checked') ) {
				$('#spnCharCount').addClass('msgwarn');
				$('#spnTweetWarning').show();
			<?}?>
			} else {
				$('#spnCharCount').removeClass('msgbad');
				$('#spnCharCount').removeClass('msgwarn');
				$('#divBlogPost #btnSubmit').removeClass('btn-danger');
				$('#divBlogPost #btnSubmit').removeClass('disabled');
				$('#divBlogPost #btnSubmit').val('Post It!');
				$('#spnTweetWarning').hide();
			}
		});

		$('#spnCharCount').text( $('#txtContent').val().length );
		//loadPostList();

		//If there's already a link, don't enable link extraction
		if( $('#txtLink').val() != '') {
			linkExtraction = false;
		}
});
