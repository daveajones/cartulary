<!--
//-----------------------------------------------------------------------------------
//Script support for the head of the river page
//-----------------------------------------------------------------------------------

	//----- River globals
	var pathToStreamItem = "#stream .stream-list li.article";
	var pathToActiveItem = "#stream .stream-list li.article.activeItem";
	var pathToStreamList = "#stream .stream-list";

    //----- Widget stuff
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


	//----- River navigation functions
	//Put focus on an article by post id reference
	function focusThisArticle(elid) {
		$(pathToStreamItem + '.activeItem').removeClass("activeItem");				
		$(pathToStreamItem + '#' + elid).addClass("activeItem");
		$('html, body').animate({ scrollTop: $(pathToStreamItem + '#' + elid).offset().top - gPlatformMenubarTopOffset }, 300);
		return false;
	}

	//Put focus on an article by post id, but scroll to it's footer
    function focusThisArticleFooter(elid) {
        $(pathToStreamItem + '.activeItem').removeClass("activeItem");
        $(pathToStreamItem + '#' + elid).addClass("activeItem");
        $('html, body').animate({ scrollTop: $(pathToStreamItem + '#' + elid + ' .footer').offset().top - gPlatformMenubarTopOffset - 50 }, 300);
        return false;
    }

	//Find the first visible article in the viewport and put focus on it
	function focusFirstVisibleArticle() {
		$(pathToStreamItem).each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$(pathToStreamItem + '.activeItem').removeClass("activeItem");				
				$(this).addClass("activeItem");
				return false;
			}
		});
		return false;
	}

	//Find the currently focused article and move focus up to the previous one in the list
    function focusPreviousArticle() {
		if( $(pathToStreamItem + ".activeItem").length > 0 ) {
			// get top positions and references to all articles
			var pos = $(pathToStreamItem).map(function(){
				var $this = $(this);
				if( $this.offset().top < $(pathToStreamItem + ".activeItem").offset().top ) {
					return {
						pid: $this.attr("id"),
				    	top: $this.offset().top
					};
				}			
			}).get();
			pos.sort(function(a,b) {
				return parseInt(b.top,10) - parseInt(a.top,10);
			});
			$(pathToStreamItem + ".activeItem").removeClass("activeItem");
			$(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
			$('html, body').animate( { scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset) }, 300); 
			return false;	
		}

		//If not, we target first visible + 1
		$(pathToStreamItem).each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$('html, body').animate( { scrollTop: ($(pathToStreamItem + ":eq("+(index - 1)+")").offset().top - gPlatformMenubarTopOffset) }, 300); 
				$(pathToStreamItem + ".activeItem").removeClass("activeItem");				
				$(pathToStreamItem + ":eq("+(index - 1)+")").addClass("activeItem");
				return false;
			}
		});
		return false;
	}

	//Find the currently focused article and move focus to the next one in line
	function focusNextArticle() {
		if( $(pathToStreamItem + ".activeItem").length > 0 ) {
			// get top positions and references to all articles
			var pos = $(pathToStreamItem).map(function(){
				var $this = $(this);
				if( $this.offset().top > $(pathToStreamItem + ".activeItem").offset().top ) {
					return {
						pid: $this.attr("id"),
				    	top: $this.offset().top
					};
				}			
			}).get();
			pos.sort(function(a,b) {
				return parseInt(a.top,10) - parseInt(b.top,10);
			});
			$(pathToStreamItem + ".activeItem").removeClass("activeItem");
			$(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
			$('html, body').animate( { scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset) }, 300); 
			return false;	
		}

		//If not, we target first visible + 1
		$(pathToStreamItem).each(function(index) {
			if( $(document).scrollTop() < $(this).offset().top ) {
				$('html, body').animate( { scrollTop: ($(pathToStreamItem + ":eq("+(index + 1)+")").offset().top - gPlatformMenubarTopOffset) }, 300); 
				$(pathToStreamItem + ".activeItem").removeClass("activeItem");				
				$(pathToStreamItem + ":eq("+(index + 1)+")").addClass("activeItem");
				return false;
			}
		});
		return false;
	}

	//Move focus up to the previous article in line that has a different feed id value than the current one
	function focusPreviousFeed() {
		if( $(pathToStreamItem + ".activeItem").length > 0 ) {
			// get top positions and references to all articles
			var pos = $(pathToStreamItem).map(function(){
				var $this = $(this);
				if( ($this.offset().top < $(pathToStreamItem + ".activeItem").offset().top) && ($this.attr('data-feedid') != $(pathToStreamItem + ".activeItem").attr('data-feedid')) ) {
					return {
						pid: $this.attr("id"),
				    	top: $this.offset().top
					};
				}			
			}).get();
			pos.sort(function(a,b) {
				return parseInt(b.top,10) - parseInt(a.top,10);
			});
			$(pathToStreamItem + ".activeItem").removeClass("activeItem");
			$(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
			$('html, body').animate( { scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset) }, 300); 

		} else {

			//If not, we target first visible + 1
			$(pathToStreamItem).each(function(index) {
				if( $(document).scrollTop() < $(this).offset().top ) {
					$('html, body').animate( { scrollTop: ($(pathToStreamItem + ":eq("+(index + 1)+")").offset().top - gPlatformMenubarTopOffset) }, 300); 
					$(pathToStreamItem + ".activeItem").removeClass("activeItem");				
					$(pathToStreamItem + ":eq("+(index + 1)+")").addClass("activeItem");
				}
			});
		}

		return false;
	}

	//Move focus to the next article in line that has a feed id different than this one
	function focusNextFeed() {
		if( $(pathToStreamItem + ".activeItem").length > 0 ) {
			// get top positions and references to all articles
			var pos = $(pathToStreamItem).map(function(){
				var $this = $(this);
				if( ($this.offset().top > $(pathToStreamItem + ".activeItem").offset().top) && ($this.attr('data-feedid') != $(pathToStreamItem + ".activeItem").attr('data-feedid')) ) {
					return {
						pid: $this.attr("id"),
				    	top: $this.offset().top
					};
				}			
			}).get();
			pos.sort(function(a,b) {
				return parseInt(a.top,10) - parseInt(b.top,10);
			});
			$(pathToStreamItem + ".activeItem").removeClass("activeItem");
			$(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
			$('html, body').animate( { scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset) }, 300); 

		} else {
	
			//If not, we target first visible + 1
			$(pathToStreamItem).each(function(index) {
				if( $(document).scrollTop() < $(this).offset().top ) {
					$('html, body').animate( { scrollTop: ($(pathToStreamItem + ":eq("+(index + 1)+")").offset().top - gPlatformMenubarTopOffset) }, 300); 
					$(pathToStreamItem + ".activeItem").removeClass("activeItem");				
					$(pathToStreamItem + ":eq("+(index + 1)+")").addClass("activeItem");
				}
			});
		}

		return false;
	}

	//----- River filters
	function showOnlyItems( klass ) {
    	$(pathToStreamItem).not('.' + klass).hide();
	}
	function showAllItems() {
	    $(pathToStreamItem).show();
	}


    //----- River init stuff
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
