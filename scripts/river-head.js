<!--
//-----------------------------------------------------------------------------------
//Script support for the head of the river page
//-----------------------------------------------------------------------------------
        // Widget stuff
        function reloadMicroblogWidget() {
                $('#divMicroblogWidget').empty();

                var ts = Math.round((new Date()).getTime() / 1000);
                var parel = document.getElementById('divMicroblogWidget');
                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.id = 'scrMicroblogWidget';
                script.src = '<?echo $s3widget?>?ts=' + ts;
                parel.appendChild(script);
        }

        function resetQuickBlogBox () {
                $('#chkTwitter').attr('checked', false);
                $('#imgTwitter').removeClass('icon-twitter').addClass('icon-notwitter');
                $('#txtSaySomething').val('');
                $('#txtSaySomething').switchClass('bigSaySomething', 'smallSaySomething', 300);
        }


	//River navigation functions
	function focusThisArticle(elid) {
		$("#stream-items div.article.activeItem").removeClass("activeItem");				
		$("#stream-items div.article#"+elid).addClass("activeItem");
		return false;
	}
	function focusFirstVisibleArticle() {
		$("#stream-items div.article").each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$("#stream-items div.article.activeItem").removeClass("activeItem");				
				$(this).addClass("activeItem");
				return false;
			}
		});
		return false;
	}
	function focusPreviousSection() {
		$("#stream-items div.section").each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$('html, body').animate( { scrollTop: ($("#stream-items div.section:eq("+(index - 1)+")").offset().top - 3) }, 300); 
				$("#stream-items div.section div.article.activeItem").removeClass("activeItem");				
				$("#stream-items div.section:eq("+(index - 1)+") div.article:eq(0)").addClass("activeItem");
				return false;
			}
		});
		return false;
	}
	function focusNextSection() {
		$("#stream-items div.section").each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$('html, body').animate( { scrollTop: ($("#stream-items div.section:eq("+(index + 1)+")").offset().top - 3) }, 300); 
				$("#stream-items div.section div.article.activeItem").removeClass("activeItem");				
				$("#stream-items div.section:eq("+(index + 1)+") div.article:eq(0)").addClass("activeItem");
				return false;
			}
		});
		return false;
	}
        function focusPreviousArticle() {
		$("#stream-items div.article").each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$('html, body').animate( { scrollTop: ($("#stream-items div.article:eq("+(index - 1)+")").offset().top - 3) }, 300); 
				$("#stream-items div.article.activeItem").removeClass("activeItem");				
				$("#stream-items div.article:eq("+(index - 1)+")").addClass("activeItem");
				return false;
			}
		});
		return false;
	}
	function focusNextArticle() {
		$("#stream-items div.article").each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$('html, body').animate( { scrollTop: ($("#stream-items div.article:eq("+(index + 1)+")").offset().top - 3) }, 300); 
				$("#stream-items div.article.activeItem").removeClass("activeItem");				
				$("#stream-items div.article:eq("+(index + 1)+")").addClass("activeItem");
				return false;
			}
		});
		return false;
	}


        // River stuff
        <?if($platform == "mobile") {?>
	var jsonRiverData = <?echo get_river_as_json($uid, TRUE)?>;
	<?} else {?>
	var jsonRiverData = <?echo get_river_as_json($uid)?>;
	<?}?>
        var Hidebigpics = false;
        var Hidepics = false;
        <?if ( $prefs['mobilehidebigpics'] == 1 && $platform == "mobile" ) {?>
        Hidebigpics = true;
        <?}?>
        <?if ( $prefs['mobilehidepics'] == 1 && $platform == "mobile"  ) {?>
        Hidepics = true;
        <?}?>
        var River = River || {};
        River.settings = {
            url : '<?echo $jsonurl?>',
            callback : 'onGetRiverStream',
            social : false,
	    initialized : false
        };
-->
