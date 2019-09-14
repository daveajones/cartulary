//Globals
acb = false;
abb = false;
gUid = '<?echo $g_uid?>';

function feedcurl() {
    var s3curlprefix = "<?echo $s3curlprefix?>";
    var bucketname = "[bucket name]/";
    var mbfilename = "<?echo get_microblog_feed_filename($uid)?>";
    var cartfilename = "<?echo get_cartulary_feed_filename($uid)?>";

    $('#s3curlA').empty();
    $('#s3curlB').empty();
    $('#s3curlC').empty();
    $('#s3curlD').empty();

    if ($('#s3bucket').val() != "") {
        $('#s3feednames').show();
        bucketname = $('#s3bucket').val() + '/';
        if ($('#mbfilename').val() != "") {
            mbfilename = $('#mbfilename').val();
        }
        if ($('#cartfilename').val() != "") {
            cartfilename = $('#cartfilename').val();
        }
    } else {
        $('#s3feednames').hide();
    }

    if ($('#s3cname').val() != "") {
        s3curlprefix = 'http://' + $('#s3cname').val();
        bucketname = '';
    } else {
        s3curlprefix = "<?echo $s3curlprefix?>";
        bucketname = $('#s3bucket').val() + '/';
    }

    $('#s3curlA').append(s3curlprefix + "/" + bucketname + cartfilename);
    $('#aS3curlA').attr('href', s3curlprefix + "/" + bucketname + cartfilename);
    $('#s3curlB').append(s3curlprefix + "/" + bucketname + mbfilename);
    $('#aS3curlB').attr('href', s3curlprefix + "/" + bucketname + mbfilename);
    $('#s3curlC').append(s3curlprefix + "/" + bucketname + "<?echo $default_opml_file_name?>");
    $('#aS3curlC').attr('href', s3curlprefix + "/" + bucketname + "<?echo $default_opml_file_name?>");
    $('#s3curlD').append(s3curlprefix + "/" + bucketname + "<?echo $default_blog_opml_file_name?>");
    $('#aS3curlD').attr('href', s3curlprefix + "/" + bucketname + "<?echo $default_blog_opml_file_name?>");

    return (true);
};

$(document).ready(function () {
    $('#frmPrefs').ajaxForm({
        dataType: 'json',
        beforeSubmit: function () {
            $('.imgSpinnerSub').show();
            $('#btnPrefSubmit').attr("disabled", true);
        },
        success: function (data) {
            $('#divPref .control-group').removeClass('error');
            if (data.status == "false") {
                showMessage(data.description, data.status, 5);
                $('#divPref [name="' + data.prefname + '"]').parent().parent().addClass('error');
            } else {
                showMessage(data.description, data.status, 5);
            }
            if (data.goloc != "") {
                window.location = data.goloc;
            }
            $('.imgSpinnerSub').hide();
            $('#btnPrefSubmit').attr("disabled", false);
        }
    });

    $('#frmSetpw').ajaxForm({
        dataType: 'json',
        beforeSubmit: function () {
            $('.imgSpinnerPwd').show();
            $('#btnPwdSubmit').attr("disabled", true);
        },
        success: function (data) {
            $('#divSetpw .control-group').removeClass('error');
            if (data.status == "false") {
                showMessage(data.description, data.status, 5);
                $('#divSetpw [name="' + data.prefname + '"]').parent().parent().addClass('error');
            } else {
                showMessage(data.description, data.status, 5);
            }
            $('.imgSpinnerPwd').hide();
            $('#btnPwdSubmit').attr("disabled", false);
        }
    });

    $('#frmUploadAvatar').ajaxForm({
        dataType: 'json',
        beforeSubmit: function () {
            $('.imgSpinnerAvatar').show();
            $('#btnUploadAvatar').attr("disabled", true);
        },
        success: function (data) {
            if (data.status == "false") {
                showMessage(data.description, data.status, 5);
            } else {
                showMessage(data.description, data.status, 5);
                window.location.reload();
            }
            $('.imgSpinnerAvatar').hide();
            $('#btnUploadAvatar').attr("disabled", false);
        }
    });

    //Initialize the display value
    feedcurl('#s3curl');

    $('#s3bucket').bind('keyup', function () {
        feedcurl('#s3curl');
    });
    $('#s3cname').bind('keyup', function () {
        feedcurl('#s3curl');
    });
    $('#mbfilename').bind('keyup', function () {
        feedcurl('#s3curl');
    });
    $('#cartfilename').bind('keyup', function () {
        feedcurl('#s3curl');
    });

    //Bind events to the file import button
    $('#btnUploadAvatar').click(function () {
        $('#fileAvatar').trigger('click');
    });
    $('#fileAvatar').change(function () {
        $('#btnAvatarUpload').trigger('click');
    });

    //Bookmarklet stuff
    $('#aCartBookmarklet').click(function () {
        if (acb == false) {
            $('#liCartBookmarklet').append('<br/><textarea>' + $('#aCartBookmarklet').attr('href') + '</textarea>');
        }
        acb = true;
        return (false);
    });
    $('#aBlogBookmarklet').click(function () {
        if (abb == false) {
            $('#liBlogBookmarklet').append('<br/><textarea>' + $('#aBlogBookmarklet').attr('href') + '</textarea>');
        }
        abb = true;
        return (false);
    });
    $('#aSubscribeBookmarklet').click(function () {
        if (abb == false) {
            $('#liSubscribeBookmarklet').append('<br/><textarea>' + $('#aSubscribeBookmarklet').attr('href') + '</textarea>');
        }
        abb = true;
        return (false);
    });

    //Toggle public river stuff
    $('#chkPubriver').bind('change', function () {
        $('#divPubriver').toggle($('#chkPubriver').prop("checked"));
    });
    $('#chkPubriver').trigger('change');

    //Toggle imap secure port number if standard
    $('#chkImapSecure').bind('change', function () {
        if ($(this).is(':checked') && $('#txtImapPort').val() == "143") {
            $('#txtImapPort').val("993");
        } else if (!$(this).is(':checked') && $('#txtImapPort').val() == "993") {
            $('#txtImapPort').val("143");
        }
    });

    //Toggle smtp secure port number if standard
    $('#chkSmtpSecure').bind('change', function () {
        if ($(this).is(':checked') && $('#txtSmtpPort').val() == "25") {
            $('#txtSmtpPort').val("465");
        } else if (!$(this).is(':checked') && $('#txtSmtpPort').val() == "465") {
            $('#txtSmtpPort').val("25");
        }
    });

    //SMTP should stop tracking on imap focus out
    $('#txtImapServer').bind('blur', function () {
        if ($('#txtSmtpServer').val() == "") {
            $('#txtSmtpServer').val($('#txtImapServer').val());
        }
    });

    //Open subs change
    $('#chkOpenSubs').bind('change', function () {
        if ($(this).is(':checked')) {
            $('#chkOpenSubs').parent().append("<span>(Use <a href='" + "/opensub?u=" + gUid + "'> this</a> url for open subs page.)</span>");
        } else {
            $('#chkOpenSubs').parent().find('span').remove();
        }
    });

    //Mastodon
    $('button#resetMastodon').bind('click', function() {
        $('form#frmPrefs').append('<input type="hidden" name="reregistermastodon" value="TRUE" />');
        $('button.btnPrefSubmit').trigger('click');
    });

    //Cart token
    $('button#newCartToken').bind('click', function() {
        $('form#frmPrefs').append('<input type="hidden" name="newcarttoken" value="TRUE" />');
        $('button.btnPrefSubmit').trigger('click');
    });
});