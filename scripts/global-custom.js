//http://stackoverflow.com/questions/4652734/return-html-from-a-user-selection/4652824#4652824
//Get html of selected text
function getSelectionHtml() {
    var html = "";
    if (typeof window.getSelection != "undefined") {
        var sel = window.getSelection();
        if (sel.rangeCount) {
            var container = document.createElement("div");
            for (var i = 0, len = sel.rangeCount; i < len; ++i) {
                container.appendChild(sel.getRangeAt(i).cloneContents());
            }
            html = container.innerHTML;
        }
    } else if (typeof document.selection != "undefined") {
        if (document.selection.type == "Text") {
            html = document.selection.createRange().htmlText;
        }
    } else if ($('#outliner').length > 0) {
        html = opGetLineText();
    }
    return html;
}


//Convert links found in twitter messages into the target link of the message
function sanitize_twitter(divStream) {
    $(divStream + ' a').each(function () {
        var alobj = $(this);
        var artitle = alobj.text();
        if (alobj.attr("href")) {
            if (alobj.attr("href").indexOf("twitter.com") != -1) {
                //If this twitter post contains a URL, then attach that URL to the link
                if (artitle.indexOf("http://") != -1) {
                    var twurl = artitle.match(/\bhttp\:\/\/([A-Za-z0-9\.\/\+\&\@\~\-\%\?\=\_\#\!]*)/gi);
                    var twlen = twurl.length;
                    for (var i = 0; i < twlen; i++) {
                        alobj.parent().append(' &rarr; <a class="twurl" href="' + twurl[i] + '">Link</a>');
                    }
                } else {
                    return true;
                }
            }
        }
    });
}


//Adjust the size of a cartulized article modal to fill the screen better
function modalFullHeight(el, loading) {
    var winH = $(window).height();
    var winW = $(window).width();
    var winScLeft = $(window).scrollLeft();

    $(el).show();

    //Set left position
    $(el).css("left", ( (winW - $(el).width() ) / 2 ) + winScLeft + "px");

    if (loading == true) {
        //Reset height params so it looks compact while grabbing data
        $(el).css("height", "");
        $(el).css("max-height", "");
        $(el).css("height", "");
        $(el).css("max-height", "");
        $(el + ' .modal-body').css('max-height', "");
        $(el + ' .modal-body').css('height', "");

        //Set initial size and position based on the current viewport size
        $(el).css("max-width", ( (winW + winScLeft) - 120) + "px");
        $(el).css("max-width", ( $(el).width() - 30) + "px");
        $(el).css("top", (( (winH - $(el).height()) / 2 ) / 2) + "px");

        return true;
    }

    //Size the modal elements according to the current viewport
    var exPad = getVerticalPadding(el) + getVerticalPadding(el + ' .modal-body') + getVerticalPadding(el + ' .modal-header');
    $(el).css('max-height', winH - exPad + "px");
    $(el).css('height', winH - exPad + "px");

    var modH = $(el).outerHeight(true);
    var modW = $(el).outerWidth(true);
    var hfSize = $(el + ' .modal-header').outerHeight(true) + $(el + ' .modal-footer').outerHeight(true);

    $(el).css("top", (winH - modH) / 2);
    $(el + ' .modal-body').css('max-height', (modH - hfSize - exPad - 5) + "px");
    $(el + ' .modal-body').css('height', (modH - hfSize - exPad - 5) + "px");

    //When the window resizes, we should resize the modal
    $(window).off('debouncedresize');
    $(window).on('debouncedresize', function (event) {
        modalFullHeight(el, false);
    });

    //When modal closes we should un-hook from the window resize event
    $(el).on('hidden', function () {
        $(window).off('debouncedresize');
    });

    return true;
}


//Spawn a microblog post box
function newMicroblogPostWindow(item, opmlsource, type) {
    var modal = '#mdlMicroblogPost';
    var item = (typeof item === "undefined") ? false : item;
    var opmlsource = (typeof opmlsource === "undefined") ? false : opmlsource;
    var type = (typeof type === "undefined") ? 0 : type;
    var compact = true;

    //Set the description
    $(modal + ' .bpdescription textarea').val("");
    $(modal + ' .bpdescription textarea').val(getSelectionHtml());
    if (item != false) {
        if ($(modal + ' .bpdescription textarea').val() == "") {
            $(modal + ' .bpdescription textarea').val($(item + ' .header').text().trim());
        }
    }

    //Zero out the title
    $(modal + ' .bptitle input').val("");

    //Set the link
    $(modal + ' .bplink input').val("");
    if (item != false) {
        $(modal + ' .bplink input').val($(item + ' .header a.articlelink').attr('href').trim());
    }

    //Set the short link
    $(modal + ' .bpshortlink input').val("");

    //Set the source
    $(modal + ' .bpsourceurl').val("");
    $(modal + ' .bpsourcetitle').val("");
    if (item != false) {
        $(modal + ' .bpsourceurl').val($(item + ' .footer span.source a').attr('href').trim());
        $(modal + ' .bpsourcetitle').val($(item + ' .footer span.source a').text().trim());
    }

    //Attach opml source if there was any
    if (opmlsource != false) {
        $(modal + ' .bpopmlsource input').val(opmlsource);
    }

    //Set the type
    if (type != 0) {
        $(modal + ' .bptype input').val(type);
    }

    //Set the origin
    $(modal + ' .bporigin input').val("");
    if (item != false) {
        $(modal + ' .bporigin input').val($(item + ' .footer span.origin').text().trim());
    }

    //Set external enclosures
    $(modal + ' .bpextenc').hide();
    $(modal + ' .bpextenc ul').empty();
    if (item != false) {
        var encount = 0;
        $(item + ' .enclosureview .encobj').each(function () {
            $(modal + ' .bpextenc ul').append('<li></li>');
            $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<a href="#" class="delete"><img class="icon-remove-small" src="/images/blank.gif" /></a>');
            if ($(this).hasClass('enclosurepic')) {
                $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure" src="' + $(this).attr("src") + '" />');
            }
            if ($(this).hasClass('encaudio')) {
                $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-audio-enclosure" src="/images/blank.gif" alt="" />');
            }
            if ($(this).hasClass('encvideo')) {
                $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-video-enclosure" src="/images/blank.gif" alt="" />');
            }
            if ($(this).hasClass('enciframe')) {
                $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-iframe-enclosure" src="/images/blank.gif" alt="" />');
            }
            $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<input type="text" name="extenclosure[' + encount + '][url]" value="' + $(this).attr("src") + '" />');
            $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<input type="hidden" name="extenclosure[' + encount + '][type]" value="' + $(this).attr("data-type") + '" />');
            $(modal + ' .bpextenc ul li:eq(' + encount + ')').append('<input type="hidden" name="extenclosure[' + encount + '][length]" value="' + $(this).attr("data-length") + '" />');
            $(modal + ' .bpextenc ul li:eq(' + encount + ') a.delete').click(function () {
                $(this).parent().remove();
                if ($(modal + ' .bpextenc ul li').length == 0) {
                    $(modal + ' .bpextenc').hide();
                }
                return false;
            });

            encount++;
        });
        if (encount > 0) {
            compact = false;
            $(modal + ' .bpextenc').show();
        }
    }

    //Clear the upload queue
    $(modal + ' #divEnclosures').hide();
    $(modal + ' #divUpload').hide();
    $(modal + ' #file_upload').uploadifive('clearQueue');

    //Show the modal
    $(modal).modal('show');
    modalFullHeight(modal, compact);

    //Ajaxify the form
    $(modal + ' .mbpostform').ajaxForm({
        dataType: 'json',
        cache: false,
        clearForm: true,
        resetForm: true,
        timeout: 60000,
        beforeSubmit: function () {
            $(modal + ' .spinner').show();
            $(modal + ' input,textarea,button').attr("disabled", true);
        },
        success: function (data) {
            if (data.status == "false") {
                showMessage(data.description, data.status, 5);
            } else {
                showMessage("Post Successful!", data.status, 5);
            }
            $(modal + ' .spinner').hide();
            $(modal + ' input,textarea,button').attr("disabled", false);
            $(modal).modal('hide');
            reloadMicroblogWidget();
        },
        error: function (x, t, m) {
            showMessage("Error: " + m + "(" + t + ")", false, 60);
            $(modal + ' input,textarea,button').attr("disabled", false);
        }
    });
    $(modal + ' .mbsubmit').click(function () {
        $(modal + ' .mbpostform').submit();
        return false;
    });

    //Set the twitter toggle
    $(modal + ' .tweeticon').removeClass('icon-twitter').addClass('icon-notwitter');
    $(modal + ' .tweetcheck').prop('checked', false);
    $(modal + ' .tweeticon').bind('click', function () {
        $(modal + ' .tweetcheck').prop('checked', !$(modal + ' .tweetcheck').prop('checked'));
        $(modal + ' .tweeticon').toggleClass('icon-twitter');
        $(modal + ' .tweeticon').toggleClass('icon-notwitter');
        $(modal + ' .bpdescription textarea').trigger('keyup');
    });

    //Set the mastodon toggle
    $(modal + ' .tooticon').removeClass('icon-mastodon').addClass('icon-nomastodon');
    $(modal + ' .tootcheck').prop('checked', false);
    $(modal + ' .tooticon').bind('click', function () {
        $(modal + ' .tootcheck').prop('checked', !$(modal + ' .tootcheck').prop('checked'));
        $(modal + ' .tooticon').toggleClass('icon-mastodon');
        $(modal + ' .tooticon').toggleClass('icon-nomastodon');
        $(modal + ' .bpdescription textarea').trigger('keyup');
    });    
    
    //Track text length
    $(modal + ' .mbcharcount').text($(modal + ' .bpdescription textarea').val().length);
    $(modal + ' .bpdescription textarea').bind('keyup', function () {
        var cc = $(modal + ' .bpdescription textarea').val().length;
        $(modal + ' .mbcharcount').text(cc);
        if ($(modal + ' .tweeticon').hasClass('icon-twitter') && cc > 138) {
            $(modal + ' .mbcharcount').addClass('msgwarn');
            $(modal + ' .mbcharcount').text(cc + ' - Twitter will truncate this message.');
        } else {
            $(modal + ' .mbcharcount').removeClass('msgwarn');
        }
    });

    //When modal closes we should clean up
    $(modal).on('hidden', function () {
        $(modal + ' .mbsubmit').unbind('click');
        $(modal + ' .tweeticon').unbind('click');
        $(modal + ' .tooticon').unbind('click');
        $(modal + ' .bpdescription textarea').unbind('keyup');
    });

    //Set focus to the textarea
    $(modal).on('shown', function () {
        $(modal + ' .bpdescription textarea').focus();
    });


    return false;
}


//Open a modal with an article displayed in it
function showArticleWindow(riveritem) {
    var modal = '#mdlShowArticle';
    var href = $(riveritem + ' .footer a.cartlink').attr('data-href').trim();
    var compact = true;

    $(modal + ' .arfooter').hide();
    $(modal + ' .arfooter .rt').click(function () {
        $(modal + ' .arbody img').each(function () {
            $(riveritem + ' .enclosureview').append('<img class="encobj enclosurepic hide" src="' + $(this).attr('src') + '" />');
        });
        $(modal).modal('hide');
        $(riveritem + ' .rtgo').trigger('click');
        return false;
    });

    $(modal + ' .artitle').empty();
    $(modal + ' .arbody').empty();
    $(modal + ' .arfooter .opml').attr('href', "#");
    $(modal + ' .arfooter .print').attr('href', "#");
    $(modal + ' .arfooter .link').attr('href', "#");
    $(modal + ' .spinner').show();
    $(modal + ' .modal-header').hide();
    $(modal + ' .modal-footer').hide();

    modalFullHeight(modal, true);
    $(modal).modal('show');
    $.ajax({
        url: href + '&json=true',
        type: "GET",
        dataType: 'json',
        timeout: 30000,
        success: function (data) {
            $(modal + ' .modal-header').show();
            $(modal + ' .modal-footer').show();
            $(modal + ' .spinner').hide();
            if (data.status == "false") {
                $(modal + ' .artitle').append(data.article.title);
                $(modal + ' .arbody').append(data.article.body);
            } else {
                modalFullHeight(modal, false);
                $(modal + ' .artitle').append(data.article.title);
                $(modal + ' .arbody').append(data.article.body);
                $(modal + ' .arfooter .opml').attr('href', "/showarticle-opml?aid=" + data.article.id);
                $(modal + ' .arfooter .print').attr('href', "/showarticle-print?aid=" + data.article.id);
                $(modal + ' .arfooter .link').attr('href', data.article.url);
                $(modal + ' .arfooter').show();
            }
        },
        error: function (x, t, m) {
            $(modal + ' .modal-header').show();
            $(modal + ' .modal-footer').show();
            $(modal + ' .spinner').hide();
            $(modal + ' .artitle').append('');
            $(modal + ' .arbody').append('<p>Error communicating with server. Connection problem?</p>');
        }
    });

    //Jump up to the top
    $(modal + ' .arbody').scrollTop(0);

    //When modal closes we should clean up
    $(modal).on('hidden', function () {
        $(modal + ' .arfooter .rt').unbind('click');
        $(modal + ' .artitle').empty();
        $(modal + ' .arbody').empty();
    });


    return false;
}


//Open a modal to display a large sized image in a shadow box
function openMediaShadowbox(imgtag) {
    var modal = '#mdlMediaShadowbox';
    var href = imgtag.attr('src');
    var compact = false;

    //Prep the modal
    $(modal + ' .sbtitle').empty();
    $(modal + ' .sbbody').empty();
    $(modal + ' .spinner').hide();
    $(modal + ' .modal-footer').hide();

    //Set the image
    $(modal + ' .sbbody').append('<img class="media" src="' + href + '" alt="" />');

    //Size the modal
    modalFullHeight(modal, compact);

    //Size the image
    $(modal + ' .sbbody img').css("max-width", ($(modal + ' .modal-body').width() - 10) + "px");
    $(modal + ' .sbbody img').css("max-height", ($(modal + ' .modal-body').height() - 10) + "px");

    //Reveal the modal
    $(modal).modal('show');

    //When modal closes we should clean up
    $(modal).on('hidden', function () {
        //$(modal + ' .arfooter .rt').unbind('click');
    });


    return false;
}
