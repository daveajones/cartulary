$(document).ready( function() {
	//Keyboard shortcuts
	key('t', function() {		//----- Target the first visible article
		freedomController.v1.river.methods.focusFirstVisibleArticle();
		return false;
        });
	key('down', function() {	//----- Jump to next feed
		if( $(freedomController.v1.river.statics.pathToActiveItem).hasClass('cartulized') ) {  return true;  }
		freedomController.v1.river.methods.focusNextFeed();
		return false;
        });
	key('up', function() {	//----- Jump to previous feed
		if( $(freedomController.v1.river.statics.pathToActiveItem).hasClass('cartulized') ) {  return true;  }
		freedomController.v1.river.methods.focusPreviousFeed();
		return false;
        });
	key('right', function() {	//----- Jump to next article
		freedomController.v1.river.methods.focusNextArticle();
		return false;
        });
	key('left', function() {	//----- Jump to previous article
		freedomController.v1.river.methods.focusPreviousArticle();
		return false;
        });
	key('u', function() {		//----- Un-sticky the currently targeted item
		if( $(freedomController.v1.river.statics.pathToStreamItem + ".activeItem").hasClass("sticky") ) {
			$(freedomController.v1.river.statics.pathToStreamItem + ".activeItem .aUnSticky").trigger('click', {source:'hotkey'});
		}
		return false;
        });
	key('c', function() {		//----- Cartulize the currently targeted item
		$(freedomController.v1.river.statics.pathToStreamItem + ".activeItem .cartlink").trigger('click');
		return false;
        });
	key('m', function() {		//----- Microblog the currently targeted item
		$("#mdlShowArticle").modal('hide');
		$(freedomController.v1.river.statics.pathToStreamItem + ".activeItem .rtgo").trigger('click');
		return false;
        });
	key('o', function() {		//----- Open original link of the currently targeted item
		if( $(freedomController.v1.river.statics.pathToStreamItem + ".activeItem .header a.articlelink").length > 0 ) {
			window.open($(freedomController.v1.river.statics.pathToStreamItem + ".activeItem .header a.articlelink").attr('href'));
		}
                return false;
        });

	//Turn the home button into a river re-generator if it's the active button
	$('#divMainMenu a.homebutton').click(function() {
    	$('html, body').animate({ scrollTop: '0px' }, 300);
		freedomController.v1.river.methods.buildRiver(false);  
	    return false;
	});

	//Initial river build
	var nowt = Math.round(new Date().getTime() / 1000);
	var rdpt = sessionStorage.getItem(freedomController.v1.river.statics.lsRiverDataPullTime);
	if( rdpt == "undefined" || (nowt - rdpt) > 300 ) {
		freedomController.v1.river.methods.buildRiver(false);
	} else {
		freedomController.v1.river.methods.buildRiver(false);
	}
	console.log("time since last river pull [" + (nowt - rdpt) + "]");

    //Point new users to the prefs page
    if( $.removeCookie('cartularynewuser') ) {
        showMessage("You can go to the prefs page to set up your avatar and other goodies. Just click your name in the menu bar.", true, 30);
    }
});


