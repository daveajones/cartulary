$(document).ready(function() {
      //Populate the user management section
      if( $('#divUserManage').length > 0 ) {
	      loadUserManage('#divUserManage', '#manageusers-template'); 
	      //Ajaxify the create user form
	      ajaxFormCreateUser('#frmCreateUser');
      }

      //Populate the user management section
      if( $('#divFeedManage').length > 0 ) {
	      loadFeedManage('#divFeedManage', '#managefeeds-template'); 
      }  
});

function loadUserManage(elDiv, elTemplate) {
      $(elDiv).empty();
      $(elDiv).append('<center><p>Loading user list...</p><img src="/images/spinner.gif" alt="" /></center>');
      $.getJSON("/cgi/fc/list.users.json", function(data) {
          if(data.status == "true") {
              $(elDiv).empty();
              $(elTemplate).tmpl(data.data).appendTo(elDiv);
              bindDeleteUser('.aDeleteUser');
	      bindResetUser('.aResetUser');
              bindEditUser('.aEditUser');
	      ajaxFormEditUser('.frmEditUser');
          } else {
              $(elDiv).append("<p>Error retrieving JSON data: [" + data.description + "]</p>");
	  }
      });

      return(true);
}

function loadFeedManage(elDiv, elTemplate) {
      $(elDiv).empty();
      $(elDiv).append('<center><p>Loading feed list...</p><img src="/images/spinner.gif" alt="" /></center>');
      $.getJSON("/cgi/fc/list.feeds.json", function(data) {
          if(data.status == "true") {
              $(elDiv).empty();
              $(elTemplate).tmpl(data.data).appendTo(elDiv);
              bindDeleteFeed('.aDeleteFeed');
	      bindResetFeed('.aResetFeed');
              //bindEditUser('.aEditUser');
	      //ajaxFormEditUser('.frmEditUser');
          } else {
              $(elDiv).append("<p>Error retrieving JSON data: [" + data.description + "]</p>");
	  }
      });

      return(true);
}

function bindDeleteUser(elDeleteLink) {
    $(elDeleteLink).click(function() {
        var userId = $(this).attr("id");
        var userName = $(this).parent().parent().attr("data-name");
        if( confirm("Do you really want to delete '" + userName + "'?") == false ) return false;
        $.getJSON("/cgi/fc/delete.user?userId="+userId, function(data) {
            showMessage( data.description, data.status, 7 );
            if(data.status == "true") {
                $('#tr_user_' + data.userid).css({"text-decoration":"line-through"});
                loadUserManage('#divUserManage', '#manageusers-template'); 
            }
        });
	return(false);
    });

    return(true);
}

function bindDeleteFeed(elDeleteLink) {
    $(elDeleteLink).click(function() {
        var feedId = $(this).attr("id");
        var feedName = $(this).parent().parent().attr("data-name");
        if( confirm("Do you really want to delete '" + feedName + "'?") == false ) return false;
        $.getJSON("/cgi/fc/delete.feed?feedId="+feedId, function(data) {
            showMessage( data.description, data.status, 7 );
            if(data.status == "true") {
                $('#tr_feed_' + data.feedid).css({"text-decoration":"line-through"});
                loadFeedManage('#divFeedManage', '#managefeeds-template'); 
            }
        });
	return(false);
    });

    return(true);
}

function bindResetUser(elResetLink) {
    $(elResetLink).click(function() {
        var userId = $(this).attr("id");
        var userName = $(this).parent().parent().attr("data-name");
        if( confirm("Do you really want to reset " + userName + "'s password?") == false ) return false;
        $.getJSON("/cgi/fc/reset.user?userId="+userId, function(data) {
            showMessage( data.description, data.status, 999 );
        });
	return(false);
    });

    return(true);
}

function bindResetFeed(elResetLink) {
    $(elResetLink).click(function() {
        var feedId = $(this).attr("id");
        var feedName = $(this).parent().parent().attr("data-name");
        if( confirm("Force a re-scan of " + feedName + "?") == false ) return false;
        $.getJSON("/cgi/fc/mark.feed.updated?feedId="+feedId, function(data) {
            showMessage( data.description, data.status, 7 );
        });
	return(false);
    });

    return(true);
}

function bindEditUser(elEditLink) {
    $(elEditLink).click(function() {
        var userId = "user_" + $(this).parent().parent().attr("data-id");
        var editId = "edit_" + $(this).parent().parent().attr("data-id");
        $('#tr_' + editId).toggle();
        if( $('#tr_' + editId).is(":visible") ) {
            $('#tr_' + editId).addClass('cellFocus');
        } else {
            $('#tr_' + editId).removeClass('cellFocus');
        }
        $('#tr_' + userId).toggleClass('cellFocus');
	return(false);
    });

    return(true);
}

function ajaxFormEditUser(elEditUserForm) {
    $(elEditUserForm).ajaxForm({
        dataType: 'json',
        beforeSubmit: function() {
            $('.trEditUser .btn-primary').attr('disabled', true);
            $('.trEditUser .spinner').show();
        },
        success: function(data) {
            showMessage( data.description, data.status, 7 );
            if(data.status == "true") {
                $('.trEditUser').hide();
                loadUserManage('#divUserManage', '#manageusers-template'); 
            }
            $('.trEditUser .spinner').hide();
            $('.trEditUser .btn-primary').attr('disabled', false);
        }
    });

    return(true);
}

function ajaxFormCreateUser(elCreateUserForm) {
    $(elCreateUserForm).ajaxForm({
        dataType: 'json',
        beforeSubmit: function() {
            $('.divCreateSubmit .btn-primary').attr('disabled', true);
            $('.divCreateSubmit .spinner').show();
        },
        success: function(data) {
            showMessage( data.description, data.status, 90 );
            if(data.status == "true") {
                loadUserManage('#divUserManage', '#manageusers-template'); 
            }
            $('.divCreateSubmit .btn-primary').attr('disabled', false);
            $('.divCreateSubmit .spinner').hide();
        }
    });

    return(true);
}
