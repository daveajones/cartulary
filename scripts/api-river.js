//-----------------------------------------------------------------------------------
// ----- River API -----
freedomController.v1.river = {};
freedomController.v1.river.statics = {
    riverJsonUrl: "/river-json",
    pathToStreamItem: "#stream .stream-list .article",
    pathToActiveItem: "#stream .stream-list .article.activeItem",
    pathToStreamList: "#stream .stream-list",
    lsRiverDataKey: "riverdata",
    lsRiverDataPullTime: "riverpulltime",
    lsStickyDataKey: "stickydata",
    lsSessionIdKey: "sessionid"
};

freedomController.v1.river.methods = (function () {
//-----------------------------------------------------------------------------------


//----- River globals -----
    var riverJsonUrl = freedomController.v1.river.statics.riverJsonUrl;
    var pathToStreamItem = freedomController.v1.river.statics.pathToStreamItem;
    var pathToActiveItem = freedomController.v1.river.statics.pathToActiveItem;
    var pathToStreamList = freedomController.v1.river.statics.pathToStreamList;
    var lsRiverDataKey = freedomController.v1.river.statics.lsRiverDataKey;
    var lsRiverDataPullTime = freedomController.v1.river.statics.lsRiverDataPullTime;
    var lsStickyDataKey = freedomController.v1.river.statics.lsStickyDataKey;
    var lsSessionIdKey = freedomController.v1.river.statics.lsSessionIdKey;


//----- River functions -----
    function _searchPostLoad() {
        return _rebindEverything();
    }


    function _removeSessionData() {
        sessionStorage.removeItem(lsStickyDataKey);
        sessionStorage.removeItem(lsRiverDataKey);
        sessionStorage.removeItem(lsRiverDataPullTime);
        sessionStorage.removeItem(lsSessionIdKey);

        return true;
    }


    function _rebindEverything(elid) {
        var _elid = (typeof elid === "undefined") ? "" : elid;

        //Bind microblog links
        _bindMicroblogLinks(_elid);

        //Bind cartulize links
        _bindCartLinks(_elid);

        //Bind sticky links
        _bindStickyLinks(_elid);

        //Bind enclosure links
        _bindEnclosureLinks(_elid);

        //Bind embed activation links
        _bindEmbedActivations(_elid);

        //Bind sopml profile links
        freedomController.v1.people.methods.bindSocialOutlineLinks(_elid);

        return false;
    }


    function _showOnlyItems(klass, title) {
        var streamObj = $('#stream');
        var activeFeedsObj = $('#divActiveFeeds');

        _showAllItems();
        $(pathToStreamItem).not('.' + klass).hide();
        streamObj.prepend('<div class="filternotice">Currently showing only items from ' + title + '.<a class="removefilter">Show all</a></div>');
        streamObj.addClass('filtered');
        streamObj.find('div.filternotice a.removefilter').click(function () {
            _showAllItems();
            return false;
        });
        activeFeedsObj.find('ul.feedlist li.' + klass).addClass('hilite');
        $('html, body').animate({scrollTop: '0px'}, 300);
    }


    function _showAllItems() {
        var streamObj = $('#stream');
        var activeFeedsObj = $('#divActiveFeeds');

        streamObj.find(' div.filternotice').remove();
        streamObj.removeClass('filtered');
        activeFeedsObj.find('ul.feedlist li').removeClass('hilite');
        $(pathToStreamItem).show();
    }


    function _isAvatar(url) {
        if (url.indexOf('gravatar') != -1) {
            return true;
        }
        if (url.indexOf('twimg.com/profile_images') != -1) {
            return true;
        }

        return false;
    }


    function _getBodyText(html) {
        var breakToken = '_______break_______',
            lineBreakedHtml = html.replace(/<br\s?\/?>/gi, breakToken).replace(/<p\.*?>(.*?)<\/p>/gi, breakToken + '$1' + breakToken);
        return $('<div>').html(lineBreakedHtml).text().replace(new RegExp(breakToken, 'g'), '\n');
    }


    function _countEnclosuresOfType(enclosures, typecheck) {
        var cnt = 0;
        $.each(enclosures, function () {
            if (this.type.indexOf(typecheck) != -1) {
                cnt++;
            }
        });

        return cnt;
    }


    function _isImage(url, type) {
        if (type.indexOf('image') != -1) {
            return true;
        }
        if (url.indexOf('.jpg') != -1) {
            return true;
        }
        if (url.indexOf('.jpeg') != -1) {
            return true;
        }
        if (url.indexOf('.png') != -1) {
            return true;
        }
        if (url.indexOf('.gif') != -1) {
            return true;
        }

        return false;
    }


    function _isAudio(url, type) {
        if (type.indexOf('audio') != -1) {
            return true;
        }
        if (url.indexOf('.mp3') != -1) {
            return true;
        }
        if (url.indexOf('.m4a') != -1) {
            return true;
        }
        if (url.indexOf('.wav') != -1) {
            return true;
        }
        if (url.indexOf('.ogg') != -1) {
            return true;
        }
        if (url.indexOf('.wma') != -1) {
            return true;
        }

        return false;
    }


    function _isVideo(url, type) {
        if (type.indexOf('video') != -1) {
            return true;
        }
        if (url.indexOf('.mp4') != -1) {
            return true;
        }
        if (url.indexOf('.m4v') != -1) {
            return true;
        }
        if (url.indexOf('.avi') != -1) {
            return true;
        }
        if (url.indexOf('.mov') != -1) {
            return true;
        }
        if (url.indexOf('.wmv') != -1) {
            return true;
        }

        return false;
    }


    function _isIframe(url, type) {
        if (type.indexOf('text/html') != -1) {
            return true;
        }
        if (type.indexOf('application/pdf') != -1) {
            return true;
        }

        return false;
    }


    function _getMediaType(type) {
        return type.split('/')[0];
    }


    function _getEnclosureSize(bytes) {
        //_____via http://blog.elctech.com/2009/01/06/convert-filesize-bytes-to-readable-string-in-javascript/
        var s, e, t;
        s = ['bytes', 'kB', 'MB', 'GB', 'TB'];
        e = Math.floor(Math.log(bytes) / Math.log(1024));
        t = (bytes / Math.pow(1024, Math.floor(e))).toFixed(2) + " " + s[e];
        return t;
    }


    function _clearActiveFeeds() {

        $('#divActiveFeeds').find('ul.feedlist').empty();

        return true;
    }


    function _addActiveFeed(feedId, feedTitle, feedUrl, urlForIcon, type, position) {
        if (platform == "mobile") {
            return false;
        }
        var activeFeedsObj = $('#divActiveFeeds');

        if (activeFeedsObj.find('ul.feedlist li.' + feedId).length < 1) {
            var feedImg = '/images/blank.gif';
            if (type == 'person') {
                feedImg = urlForIcon;
                imgClass = 'avatar48';
            } else {
                feedImg = _getFavicon(urlForIcon);
                imgClass = 'favicon';
            }
            if (position == 'top') {
                activeFeedsObj.find('ul.feedlist').prepend('<li class="' + type + ' ' + feedId + '">' + feedTitle + ' <img class="' + imgClass + '" src="' + feedImg + '" /></li>');
            } else {
                activeFeedsObj.find('ul.feedlist').append('<li class="' + type + ' ' + feedId + '">' + feedTitle + ' <img class="' + imgClass + '" src="' + feedImg + '" /></li>');
            }
            activeFeedsObj.find('ul.feedlist li.' + feedId).unbind('click');
            activeFeedsObj.find('ul.feedlist li.' + feedId).click(function () {
                _showOnlyItems(feedId, feedTitle);
                return false;
            });
        }

        return false;
    }


    function _getDomain(url) {
        var domain;
        if (( url !== null ) && ( url !== "" )) {
            domain = url.split('?')[0]; // cleans urls of form http://domain.com?a=1&b=2
            domain = domain.split('/')[2];
            if (typeof(domain) != "undefined") {
                domain = domain.replace("www.", "").replace("www2.", "").replace("feedproxy.", "").replace("feeds.", "");
            } else {
                return null;
            }
        }
        return domain;
    }


    function _getFavicon(url) {
        return '//www.google.com/s2/favicons?domain=' + _getDomain(url);
    }


    function _focusFirstVisibleArticle() {
        $(pathToStreamItem).each(function () {
            console.log($(document).scrollTop() + " : " + $(this).offset().top);
            if ($(document).scrollTop() < $(this).offset().top) {
                $(pathToStreamItem + '.activeItem').removeClass("activeItem");
                $(this).addClass("activeItem");
                return false;
            }
        });
        return false;
    }


    function _focusThisArticle(elid) {
        //Put focus on an article by post id reference
        $(pathToStreamItem + '.activeItem').removeClass("activeItem");
        $(pathToStreamItem + '#' + elid).addClass("activeItem");
        console.log("_focusThisArticle(" + elid + ")");
        $('html, body').animate({scrollTop: $(pathToStreamItem + '#' + elid).offset().top - gPlatformMenubarTopOffset}, 300);
        return false;
    }


    function _focusThisArticleFooter(elid) {
        //Put focus on an article by post id, but scroll to it's footer
        $(pathToStreamItem + '.activeItem').removeClass("activeItem");
        $(pathToStreamItem + '#' + elid).addClass("activeItem");
        $('html, body').animate({scrollTop: $(pathToStreamItem + '#' + elid + ' .footer').offset().top - gPlatformMenubarTopOffset - 50}, 300);
        return false;
    }


    function _focusPreviousArticle() {
        if ($(pathToStreamItem + ".activeItem").length > 0) {
            // get top positions and references to all articles
            var pos = $(pathToStreamItem).map(function () {
                var $this = $(this);
                if ($this.offset().top < $(pathToStreamItem + ".activeItem").offset().top) {
                    return {
                        pid: $this.attr("id"),
                        top: $this.offset().top
                    };
                }
            }).get();
            pos.sort(function (a, b) {
                return parseInt(b.top, 10) - parseInt(a.top, 10);
            });
            $(pathToStreamItem + ".activeItem").removeClass("activeItem");
            $(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
            $('html, body').animate({scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset)}, 300);
            return false;
        }

        //If not, we target first visible + 1
        $(pathToStreamItem).each(function (index) {
            if ($(document).scrollTop() < $(this).offset().top) {
                $('html, body').animate({scrollTop: ($(pathToStreamItem + ":eq(" + (index - 1) + ")").offset().top - gPlatformMenubarTopOffset)}, 300);
                $(pathToStreamItem + ".activeItem").removeClass("activeItem");
                $(pathToStreamItem + ":eq(" + (index - 1) + ")").addClass("activeItem");
                return false;
            }
            return false;
        });
        return false;
    }


    function _targetNextArticle() {
        console.log('targetNextArticle');
        return _focusNextArticle(true);
    }


    function _focusNextArticle(noscroll) {
        var _noscroll = (typeof noscroll === "undefined") ? false : noscroll;

        if ($(pathToStreamItem + ".activeItem").length > 0) {
            // get top positions and references to all articles
            var pos = $(pathToStreamItem).map(function () {
                var $this = $(this);
                if ($this.offset().top > $(pathToStreamItem + ".activeItem").offset().top) {
                    return {
                        pid: $this.attr("id"),
                        top: $this.offset().top
                    };
                }
            }).get();
            pos.sort(function (a, b) {
                return parseInt(a.top, 10) - parseInt(b.top, 10);
            });
            $(pathToStreamItem + ".activeItem").removeClass("activeItem");
            $(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
            if (_noscroll === false) {
                $('html, body').animate({scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset)}, 300);
            }
            return false;
        }

        //If not, we target first visible + 1
        $(pathToStreamItem).each(function (index) {
            if ($(document).scrollTop() < $(this).offset().top) {
                if (_noscroll === false) {
                    $('html, body').animate({scrollTop: ($(pathToStreamItem + ":eq(" + (index + 1) + ")").offset().top - gPlatformMenubarTopOffset)}, 300);
                }
                $(pathToStreamItem + ".activeItem").removeClass("activeItem");
                $(pathToStreamItem + ":eq(" + (index + 1) + ")").addClass("activeItem");
                return false;
            }
        });
        return false;
    }


    function _focusPreviousFeed() {
        if ($(pathToStreamItem + ".activeItem").length > 0) {
            // get top positions and references to all articles
            var pos = $(pathToStreamItem).map(function () {
                var $this = $(this);
                if (($this.offset().top < $(pathToStreamItem + ".activeItem").offset().top) && ($this.attr('data-feedid') != $(pathToStreamItem + ".activeItem").attr('data-feedid'))) {
                    return {
                        pid: $this.attr("id"),
                        top: $this.offset().top
                    };
                }
            }).get();
            pos.sort(function (a, b) {
                return parseInt(b.top, 10) - parseInt(a.top, 10);
            });
            $(pathToStreamItem + ".activeItem").removeClass("activeItem");
            $(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
            $('html, body').animate({scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset)}, 300);

        } else {

            //If not, we target first visible + 1
            $(pathToStreamItem).each(function (index) {
                if ($(document).scrollTop() < $(this).offset().top) {
                    $('html, body').animate({scrollTop: ($(pathToStreamItem + ":eq(" + (index + 1) + ")").offset().top - gPlatformMenubarTopOffset)}, 300);
                    $(pathToStreamItem + ".activeItem").removeClass("activeItem");
                    $(pathToStreamItem + ":eq(" + (index + 1) + ")").addClass("activeItem");
                }
            });
        }

        return false;
    }


    function _focusNextFeed() {
        if ($(pathToStreamItem + ".activeItem").length > 0) {
            // get top positions and references to all articles
            var pos = $(pathToStreamItem).map(function () {
                var $this = $(this);
                if (($this.offset().top > $(pathToStreamItem + ".activeItem").offset().top) && ($this.attr('data-feedid') != $(pathToStreamItem + ".activeItem").attr('data-feedid'))) {
                    return {
                        pid: $this.attr("id"),
                        top: $this.offset().top
                    };
                }
            }).get();
            pos.sort(function (a, b) {
                return parseInt(a.top, 10) - parseInt(b.top, 10);
            });
            $(pathToStreamItem + ".activeItem").removeClass("activeItem");
            $(pathToStreamItem + '#' + pos[0].pid).addClass("activeItem");
            $('html, body').animate({scrollTop: ($(pathToStreamItem + ".activeItem").offset().top - gPlatformMenubarTopOffset)}, 300);

        } else {

            //If not, we target first visible + 1
            $(pathToStreamItem).each(function (index) {
                if ($(document).scrollTop() < $(this).offset().top) {
                    $('html, body').animate({scrollTop: ($(pathToStreamItem + ":eq(" + (index + 1) + ")").offset().top - gPlatformMenubarTopOffset)}, 300);
                    $(pathToStreamItem + ".activeItem").removeClass("activeItem");
                    $(pathToStreamItem + ":eq(" + (index + 1) + ")").addClass("activeItem");
                }
            });
        }

        return false;
    }


    function _bindMicroblogLinks(elid) {
        var elid = (typeof elid === "undefined") ? "" : elid;

        $(elid + ' .mblink').unbind('click');
        $(elid + ' .mblink').click(function () {
            var aobj = $(this);
            var postId = aobj.attr("data-id");
            var pathToPost = '#stream .stream-list .article#' + postId;
            var pathToBlog = '#stream .stream-list .article#' + postId + ' .footer .mbinline';
            var pathToForm = '#stream .stream-list .article#' + postId + ' .footer .mbinline .mbinform';
            console.log('pathToPost:' + pathToPost);

            //Don't allow more than one attempt at reblogging
            if ($(pathToBlog).length > 0) {
                _focusThisArticleFooter(postId);
                return false;
            }

            //Tag a class on the end of the footer so we can hook re-blog mode
            $(pathToPost + ' .footer').removeClass('mbposted').addClass('mbmode');

            //Build a form from the post attributes
            var postUrl = $(pathToPost).attr("data-url");
            var postDescription = $(pathToPost + ' .header h3').text();
            var postSourceUrl = $(pathToPost).attr("data-sourceurl");
            var postSourceTitle = $(pathToPost).attr("data-sourcetitle");
            var postOrigin = $(pathToPost).attr("data-origin");

            //Create a little re-blog window in the footer of the post with a form in it
            $(pathToPost + ' .footer').append('<div class="mbinline ' + postId + '"><form class="mbinform ' + postId + '" method="post" action="<?echo $blogpostcgi?>"></form></div>');

            //Add a please wait element
            $(pathToPost + ' .footer').append('<div class="inlineblogmsg hide"><img src="/images/spinner.gif" /> Posting. Please wait...</div>');

            //Build out the form
            //Description
            $(pathToForm).append('<span><textarea class="mbdescription" name="content">' + postDescription.trim() + '</textarea></span>');
            $(pathToForm).append('<img class="icon-extlink" src="/images/blank.gif" /><span><input class="mblink" type="text" name="link" value="' + postUrl.trim() + '" /></span>');
            $(pathToForm).append('<input class="mbsourceurl hide" type=hidden" name="source[url]" value="' + postSourceUrl.trim() + '" />');
            $(pathToForm).append('<input class="mbsourcetitle hide" type="hidden" name="source[title]" value="' + postSourceTitle.trim() + '" />');
            $(pathToForm).append('<input class="mborigin hide" type="hidden" name="origin" value="' + postOrigin.trim() + '" />');

            //Add enclosures to the form
            var encount = 0;
            //Add a div with a list for holding external enclosures
            $(pathToForm).append('<div class="mbextenc hide"><ul></ul></div>');
            $(pathToPost + ' .enclosureview .encobj').each(function () {
                //Add a list element for each enclosure
                $(pathToForm + ' .mbextenc ul').append('<li></li>');

                //Add a delete button for each enclosure
                $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<a href="#" class="delete"><img class="icon-remove-small" src="/images/blank.gif" /></a>');

                //Now add the enclosure thumbnail
                if ($(this).hasClass('enclosurepic')) {
                    $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure" src="' + $(this).attr("src") + '" />');
                } else if ($(this).hasClass('encaudio')) {
                    $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-audio-enclosure" src="/images/blank.gif" alt="" />');
                } else if ($(this).hasClass('encvideo')) {
                    $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-video-enclosure" src="/images/blank.gif" alt="" />');
                } else if ($(this).hasClass('enciframe')) {
                    $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<img class="imgenclosure icon-iframe-enclosure" src="/images/blank.gif" alt="" />');
                }

                //Add the input element to the form
                $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<input type="text" name="extenclosure[' + encount + '][url]" value="' + $(this).attr("data-src") + '" />');
                $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<input type="hidden" name="extenclosure[' + encount + '][type]" value="' + $(this).attr("data-type") + '" />');
                $(pathToForm + ' .mbextenc ul li:eq(' + encount + ')').append('<input type="hidden" name="extenclosure[' + encount + '][length]" value="' + $(this).attr("data-length") + '" />');

                //Attach click handler for the delete button
                $(pathToForm + ' .mbextenc ul li:eq(' + encount + ') a.delete').click(function () {
                    $(this).parent().remove();
                    if ($(pathToForm + ' .mbextenc ul li').length == 0) {
                        $(pathToForm + ' .mbextenc').hide();
                    }
                    return false;
                });

                //Un-hide the external enclosure form since we have at least one external enclosure
                $(pathToForm + ' .mbextenc').show();

                //Increment the enclosure counter
                encount++;
            });

            //Add a twitter toggle if twitter is enabled
            if (cuTwitterIsEnabled) {
                $(pathToForm).append('<label class="checkbox tweetbox"><img class="tweeticon icon-notwitter" src="/images/blank.gif" alt="" /><input class="tweetcheck" name="tweet" type="checkbox" /></label>');
                $(pathToForm + ' .tweeticon').unbind('click');
                $(pathToForm + ' .tweeticon').bind('click', function () {
                    if ($(pathToForm + ' .tweetcheck').prop("checked") == false) {
                        $(pathToForm + ' .tweetcheck').prop("checked", true);
                    } else {
                        $(pathToForm + ' .tweetcheck').prop("checked", false);
                    }
                });
            }

            //Submit buttons go in the div and we'll trigger form submission from outside
            $(pathToForm).append('<a class="btn btn-success mbsubmit ' + postId + '" href="#">Post</a> <a class="btn btn-error mbcancel ' + postId + '" href="#">Cancel</a>');

            //Attach click handlers to the buttons
            $(pathToBlog + ' a.mbsubmit.' + postId).unbind('click');
            $(pathToBlog + ' a.mbsubmit.' + postId).click(function () {
                $(pathToForm + '.' + postId).submit();
                return false;
            });
            $(pathToBlog + ' a.mbcancel.' + postId).unbind('click');
            $(pathToBlog + ' a.mbcancel.' + postId).click(function () {
                $(pathToPost + ' .footer').removeClass('mbmode');
                $(pathToBlog + '.' + postId).remove();
                return false;
            });

            //Ajaxify the form
            $(pathToForm + '.' + postId).ajaxForm({
                dataType: 'json',
                cache: false,
                clearForm: true,
                resetForm: true,
                timeout: 60000,
                beforeSubmit: function () {
                    $(pathToBlog + '.' + postId).hide();
                    $(pathToPost + ' .footer .inlineblogmsg').show();
                },
                success: function (data) {
                    if (data.status == "false") {
                        showMessage(data.description, data.status, 5);
                        $(pathToPost + ' .footer .inlineblogmsg').hide();
                        $(pathToBlog + '.' + postId).show();
                    } else {
                        showMessage("Post Successful!", data.status, 5);
                        $(pathToPost + ' .footer').removeClass('mbmode').addClass('mbposted');
                        $(pathToPost + ' .footer .inlineblogmsg').remove();
                        $(pathToBlog + '.' + postId).remove();

                        var si = {
                            id: data.post.id,
                            avatarUrl: data.post.avatarUrl,
                            link: data.post.link,
                            permaLink: data.post.permaLink,
                            title: data.post.title,
                            body: data.post.body,
                            pubDate: data.post.pubDate
                        }

                        $('#template-subitem').tmpl(si).prependTo(pathToPost + ' .subitems');
                        _focusThisArticleFooter(postId);
                    }
                },
                error: function (x, t, m) {
                    showMessage("Error: " + m + "(" + t + ")", false, 60);
                    $(pathToPost + ' .footer .inlineblogmsg').hide();
                    $(pathToBlog + '.' + postId).show();
                }
            });

            //Set focus on this article and scroll to the
            //re-blog area
            _focusThisArticleFooter(postId);

            //Set focus in description field
            $(pathToForm + '.' + postId + ' span textarea.mbdescription').focus();

            //Set the twitter toggle
            $(pathToForm + ' .tweetbox .tweeticon').removeClass('icon-twitter').addClass('icon-notwitter');
            $(pathToForm + ' .tweetbox .tweetcheck').prop('checked', false);
            $(pathToForm + ' .tweetbox .tweeticon').bind('click', function () {
                $(pathToForm + ' .tweetbox .tweetcheck').prop('checked', !$(pathToForm + ' .tweetbox .tweetcheck').prop('checked'));
                $(pathToForm + ' .tweetbox .tweeticon').toggleClass('icon-twitter');
                $(pathToForm + ' .tweetbox .tweeticon').toggleClass('icon-notwitter');
                $(pathToForm + ' .tweetbox .mbdescription').trigger('keyup');
            });

            return false;
        });

        return true;
    }


    function _bindCartLinks(elid) {
        var elid = (typeof elid === "undefined") ? "" : elid;

        $(elid + ' .cartlink').unbind('click');
        $(elid + ' .cartlink').click(function () {
            var aobj = $(this);
            var postId = aobj.attr("data-id");
            var pathToPost = '#stream .stream-list .article#' + postId;
            console.log('pathToPost:' + pathToPost);

            //Don't allow more than one attempt at cartulizing
            if ($(pathToPost).hasClass('cartulized')) {
                _focusThisArticle(postId);
                return false;
            }

            //Kill any existing messages
            $(pathToPost + ' .description .inlinecartmsg').remove();

            //Make sure a description exists
            if ($(pathToPost + ' .description').length === 0) {
                $(pathToPost + ' .header').after('<div class="description"></div>');
            }

            //Build a cartulize url from the post attributes
            var postUrl = encodeURIComponent($(pathToPost).attr("data-url"));
            var postTitle = encodeURIComponent($(pathToPost + ' .header h3').text());
            var postSourceUrl = encodeURIComponent($(pathToPost).attr("data-sourceurl"));
            var postSourceTitle = encodeURIComponent($(pathToPost).attr("data-sourcetitle"));

            //Set focus on this article
            _focusThisArticle(postId);

            //Make the cartulization call
            $(pathToPost + ' .description').append('<div class="inlinecartmsg"><img src="/images/spinner.gif" /> Cartulizing. Please wait...</div>');
            $.ajax({
                url: '<?echo $cartulizecgi?>?json=true' + '&url=' + postUrl + '&title=' + postTitle + '&surl=' + postSourceUrl + '&stitle=' + postSourceTitle,
                type: "GET",
                dataType: 'json',
                timeout: 30000,
                success: function (data) {
                    if (data.status == 'true') {
                        //Kill wait message
                        $(pathToPost + ' .description .inlinecartmsg').remove();
                        //Save the article id in the post
                        $(pathToPost).attr('data-articleid', data.article.id);
                        //Replace the body text with what we got back
                        if ($(pathToPost + ' .description').length < 1) {
                            $(pathToPost + ' .header').after('<div class="description" />');
                        }
                        $(pathToPost + ' .description').html(data.article.body);
                        $(pathToPost + ' .description a').attr('target', '_blank');
                        $(pathToPost + ' .description a').on('press', function () {
                            var url = '/cgi/in/cartulize?url=' + $(this).attr('href');
                            window.open(url);
                        });
                        //Change to a reading-friendly style
                        $(pathToPost).addClass('cartulized');
                        //Set the title based on what came back
                        $(pathToPost).find('div.header h3 a').empty().html(data.article.title);
                        //Add an edit button next to the title
                        $(pathToPost).find('div.header button.changetitle').remove();
                        $(pathToPost).find('div.header').prepend('<button class="changetitle"><i class="fa fa-edit"></i></button>');
                        $(pathToPost).find('div.header button.changetitle').on('click', function () {
                            var newtitle = prompt("Enter new title...");
                            if (newtitle != null) {
                                //Make the call
                                $.ajax({
                                    url: '/cgi/in/setarticletitle?id=' + data.article.id + '&title=' + newtitle,
                                    type: "GET",
                                    timeout: 20000,
                                    dataType: 'json',
                                    success: function (data) {
                                        if (data.status == "false") {
                                            showMessage(data.description, data.status, 5);
                                        } else {
                                            $(pathToPost).find('div.header h3 a').empty().html(newtitle);
                                        }
                                    }
                                });
                            }
                        });
                        //Change cart link at bottom to an edit article button
                        $(pathToPost).find('div.footer div.actions a.cartlink img.icon-bookmark').removeClass('icon-bookmark').addClass('icon-editor');
                        $(pathToPost).find('div.footer div.actions a.cartlink').removeClass('cartlink').addClass('editlink');
                        $(pathToPost).find('div.footer div.actions a.editlink').on('click', function () {
                            var url = '/editor?aid=' + data.article.id;
                            window.open(url);
                        });
                    } else {
                        //Append error message
                        $(pathToPost + ' .description .inlinecartmsg').html(data.article.body).addClass('carterrormsg');
                    }
                    //Set focus on this article
                    _focusThisArticle(postId);
                },
                error: function (x, t, m) {
                    //Append error message
                    $(pathToPost + ' .description .inlinecartmsg').html('<p>Error communicating with server. Connection problem?</p>').addClass('carterrormsg');
                    //Set focus on this article
                    _focusThisArticle(postId);
                }
            });
            return false;
        });
        return true;
    }


    function _bindStickyLinks(elid) {
        var elid = (typeof elid === "undefined") ? "" : elid;

        //Bind some new clicks to the stickybuttons
        $(elid + ' .aUnSticky').unbind('click');
        $(elid + ' .aUnSticky').click(function (e, data) {
            var bobj = $(this);
            var id = bobj.attr("data-id");
            var fid = bobj.attr("data-feedid");
            console.log("fid: " + fid);
            var idx = bobj.attr("data-idx");
            console.log("idx: " + idx);

            //Hide the item first
            $('#' + id).hide();

            //Focus the next sticky article
            if (data && (data.source === "swipe" || data.source === "hotkey")) {
                var nextId = $('#' + id).next().attr('id');
                _focusThisArticle(nextId);
            }

            //Get any sticky subitems so we can un-sticky them too
            var subitems = $('#' + id + ' .subitem.sticky').map(function () {
                return this.id;
            }).get();

            (function () {
                //Make the call
                $.ajax({
                    url: '/cgi/in/unsticky?id=' + id,
                    type: "GET",
                    timeout: 20000,
                    dataType: 'json',
                    success: function (data) {
                        if (data.status == "false") {
                            showMessage(data.description, data.status, 5);
                        }

                        //If this is the last item from this feed/owner, remove the active feed from the sidebar
                        console.log('unsticky: ' + fid);
                        if ($(pathToStreamItem + '.' + fid).length < 1) {
                            $('#divActiveFeeds').find('ul.feedlist li.' + fid).remove();
                        }

                        //Remove the item
                        $('#' + id).removeClass('sticky').addClass('unsticky');
                        //$('#' + id).remove();
                        //_removeStickyItemLS(idx);
                    }
                });

                //Loop through the sticky subitems and un-sticky them as well
                for (var i = 0; i < subitems.length; i++) {
                    //Make the call
                    $.ajax({
                        url: '/cgi/in/unsticky?id=' + subitems[i],
                        type: "GET",
                        timeout: 20000,
                        dataType: 'json',
                        success: function (data) {
                            if (data.status == "false") {
                                showMessage(data.description, data.status, 5);
                            }
                        }
                    });
                }
            })();

            return false;
        });

        return false;
    }


    function _getStreamWidth() {
        var viewportWidth = $(window).width();
        var sidebarWidth = $('#stream-sidebar-right').width();
        return ((viewportWidth - sidebarWidth) - 80);
    }


    function _sortGrid(sizeOnly) {
        var sizeOnly = (typeof sizeOnly === "undefined") ? false : sizeOnly;
        var streamWidth = _getStreamWidth();
        var colcount = _calculateColumnCount();
        var streamWrapObj = $('#stream-wrap');
        var streamObj = $('#stream');
        var streamItemsObj = streamObj.find('#stream-items');

        //Build the columns
        if (sizeOnly == false) {
            streamItemsObj.find('.col').remove();
            for (c = 1; c <= colcount; c++) {
                streamItemsObj.append('<div class="col col' + c + '"><div class="stream-list"></div></div>');
            }
        }

        if ($(window).outerWidth(true) < 900) {
            $('.navbar').removeClass('navbar-fixed-top');
            $('#stream').css('max-width', '700px');
        } else {
            $('.navbar').addClass('navbar-fixed-top');
            $('#stream').css('max-width', '');
        }

        //Adjust their sizing and layout
        if (platform == "mobile" || platform == "tablet") {
            return colcount;
        }

        //Reset some values
        streamItemsObj.find('.col').addClass('span3');
        streamObj.css('margin-left', 'auto');
        streamObj.css('margin-right', 'auto');
        streamObj.find('.stream-list').css('max-width', '600px');
        streamItemsObj.find('.col').css('max-width', '600px');
        streamWrapObj.css('width', streamWidth + 'px');

        //Size the grid
        if (colcount == 1) {
            streamObj.css('width', '800px');
            streamObj.find('.stream-list').css('width', '600px');
            streamItemsObj.find('.col').css('width', '600px');
            streamWrapObj.css('width', '');
        } else if (colcount == 2) {
            streamObj.css('width', Math.min(1500, (streamWidth * .99).toFixed()) + 'px');
            streamObj.find('.stream-list').css('width', (streamWidth * .47).toFixed() + 'px');
            streamItemsObj.find('.col').css('width', (streamWidth * .47).toFixed() + 'px');
        } else if (colcount == 3) {
            streamObj.css('width', Math.min(2000, (streamWidth * .99).toFixed()) + 'px');
            streamObj.find('.stream-list').css('width', (streamWidth * .30).toFixed() + 'px');
            streamItemsObj.find('.col').css('width', (streamWidth * .30).toFixed() + 'px');
        } else if (colcount == 4) {
            streamObj.css('width', Math.min(2500, (streamWidth * .99).toFixed()) + 'px');
            streamObj.find('.stream-list').css('width', (streamWidth * .22).toFixed() + 'px');
            streamItemsObj.find('.col').css('width', (streamWidth * .22).toFixed() + 'px');
        } else if (colcount == 5) {
            streamObj.css('width', Math.min(3000, (streamWidth * .99).toFixed()) + 'px');
            streamObj.find('.stream-list').css('width', (streamWidth * .17).toFixed() + 'px');
            streamItemsObj.find('.col').css('width', (streamWidth * .17).toFixed() + 'px');
        } else {
            streamObj.find('.stream-list').css('width', '');
        }

        return colcount;
    }


    function _resizeGrid() {
        console.log('resize grid');
        _changeStreamNotice('<img src="/images/spinner.gif" /> Adjusting layout...');
        return _buildRiver(true);
    }


    function _changeStreamNotice(notice, hide) {
        var hide = (typeof hide === "undefined") ? false : hide;
        var noticeObj = $('#stream').find('p.notice');

        noticeObj.html(notice);
        if (hide == false) {
            noticeObj.show();
        } else {
            noticeObj.hide();
        }

        return true;
    }


    function _buildRiver(cached) {
        var cached = (typeof cached === "undefined") ? false : cached;
        var cols = 1;

        _showAllItems();
        _clearActiveFeeds();
        _changeStreamNotice('<img src="/images/spinner.gif" /> Building river...');

        //Size columns and build the html structure
        cols = _sortGrid();

        //Get the data and show it
        console.log("build river from cache? " + cached);
        $.when(_getRiverStickyItems(cached), _getRiverItems(cached)).done(function () {
            //Bindings
            _rebindEverything();

            //Focus on first article
            _focusFirstVisibleArticle();

            //Remove loading notice
            _changeStreamNotice('', true);

            //Timestamp the data pull in local storage
            if (cached == false) {
                sessionStorage.setItem(lsRiverDataPullTime, Math.round(new Date().getTime() / 1000));
            }

            //Re-trigger masonry when viewport is resized on desktop browsers
            if (platform != "mobile" && platform != "tablet") {
                $(window).off('debouncedresize');
                $(window).on('debouncedresize', function (event) {
                    if ($(pathToStreamList).length != _calculateColumnCount()) {
                        console.log('reflowing grid');
                        freedomController.v1.river.methods.resizeGrid();
                    } else {
                        console.log('no re-flow needed');
                        _sortGrid(true);
                    }
                });
            }

        });

        return true;
    }


    function _populateGridSticky(cols, data) {
        var col = 1;

        //Get data out of local storage
        var lsdata = sessionStorage.getItem(lsStickyDataKey);
        if (lsdata !== null) {
            data = JSON.parse(lsdata);
        }

        $.each(data.data.items, function (i, item) {
            //Add an index
            item.index = i;

            if (item.hidden != 1) {
                //Add each discovered feed to the active feed sidebar
                if (item.feed.linkedOutlineType === 'sopml') {
                    freedomController.v1.river.methods.addActiveFeed(item.feed.linkedOutlineId, item.feed.ownerName, item.feed.linkedOutlineUrl, item.feed.avatarUrl, "person", 'top');
                } else {
                    freedomController.v1.river.methods.addActiveFeed(item.feed.feedId, item.feed.feedTitle, item.feed.feedUrl, item.feed.websiteUrl, "feed", 'bottom');
                }

                //Check if the item exists already
                if ($(pathToStreamItem + '#' + item.id).length > 0) {
                    $(pathToStreamItem + '#' + item.id).prependTo('#stream .col' + col + ' .stream-list');
                    _makePostSticky(pathToStreamItem + '#' + item.id, item.id, item.index, item.feed.feedId);

                } else {
                    //Check if the origin exists already
                    if (item.origin != "" && $(pathToStreamItem + '[data-origin="' + item.origin + '"]').length > 0) {
                        //Add as sub-item
                        $('#template-subitem').tmpl(item).appendTo(pathToStreamItem + '[data-origin="' + item.origin + '"]');
                        console.log('append subitem to: ' + pathToStreamItem + '#' + item.id);
                    } else {
                        //Add as item
                        $('#template-sticky').tmpl(item).prependTo('#stream .col' + col + ' .stream-list');

                        //Increment column counter
                        if (col == cols) {
                            col = 1;
                        } else {
                            col++;
                        }
                    }
                }

                //Add a swipe handler for mobile
                if (platform == "mobile") {
                    (function () {
                        $(pathToStreamItem + '#' + item.id + ' div.header h3 a').on('press', function () {
                            $(pathToStreamItem + '#' + item.id + ' .cartlink').trigger('click');
                        });
                        $(pathToStreamItem + '#' + item.id).on('flick', function (e) {
                            //A LtoR flick triggers un-sticky call
                            if (e.orientation === 'horizontal' && e.direction === 1) {
                                $(pathToStreamItem + '#' + item.id).hide();
                                $(pathToStreamItem + '#' + item.id + ' .aUnSticky').trigger('click', {source: "swipe"});
                            }
                            //If article cart'd already, then RtoL flick prompts for title change
                            if (e.orientation === 'horizontal' && e.direction === -1 && $(pathToStreamItem).hasClass('cartulized')) {
                                var articleid = $(pathToStreamItem).data('articleid');
                                var newtitle = prompt("Enter new title...");
                                if (newtitle != null) {
                                    //Make the call
                                    $.ajax({
                                        url: '/cgi/in/setarticletitle?id=' + articleid + '&title=' + newtitle,
                                        type: "GET",
                                        timeout: 20000,
                                        dataType: 'json',
                                        success: function (data) {
                                            if (data.status == "false") {
                                                showMessage(data.description, data.status, 5);
                                            } else {
                                                $(pathToStreamItem + '#' + item.id + ' div.header h3 a').empty().append(newtitle);
                                            }
                                        }
                                    });
                                }
                            }
                            //If article not cart'd already, then RtoL flick carts it
                            if (e.orientation === 'horizontal' && e.direction === -1 && !$(pathToStreamItem).hasClass('cartulized')) {
                                $(pathToStreamItem + '#' + item.id + ' .cartlink').trigger('click');
                            }
                        })
                    })();
                }
            }

        });

        return false;
    }


    function _populateGrid(cols, data) {
        var col = 1;

        //Get data out of local storage
        var lsdata = sessionStorage.getItem(lsRiverDataKey);
        if (lsdata !== null) {
            data = JSON.parse(lsdata);
        }

        $.each(data.updatedFeeds.updatedFeed, function (f, feed) {
            //Add each discovered feed to the active feed sidebar
            if (feed.linkedOutlineType === 'sopml') {
                freedomController.v1.river.methods.addActiveFeed(feed.linkedOutlineId, feed.ownerName, feed.linkedOutlineUrl, feed.avatarUrl, "person", 'top');
            } else {
                freedomController.v1.river.methods.addActiveFeed(feed.feedId, feed.feedTitle, feed.feedUrl, feed.websiteUrl, "feed", 'bottom');
            }

            //----- Post loop -----
            $.each(feed.item, function (i, item) {
                //Check if the item exists already
                if ($(pathToStreamItem + '#' + item.id).length < 1) {
                    //Make the feed info available to the item
                    item.feed = feed;
                    delete item.feed.item;

                    if (item.origin != "" && $(pathToStreamItem + '[data-origin="' + item.origin + '"]').length > 0) {
                        //Add as sub-item
                        $('#template-subitem').tmpl(item).appendTo(pathToStreamItem + '[data-origin="' + item.origin + '"]');
                        console.log('append subitem to: ' + pathToStreamItem + '#' + item.id);
                    } else {
                        //Add as item
                        $('#template').tmpl(item).appendTo('#stream .col' + col + ' .stream-list');

                        //Increment column counter
                        if (col == cols) {
                            col = 1;
                        } else {
                            col++;
                        }
                    }

                    //Make source link into a feed filter trigger
                    (function () {
                        $(pathToStreamItem + '#' + item.id + ' .footer .source a.filter').click(function () {
                            _showOnlyItems(item.feed.feedId, item.feed.feedTitle);
                            return false;
                        });
                    })();
                }

                //Add a swipe handler for mobile
                if (platform == "mobile") {
                    (function () {
                        $(pathToStreamItem + '#' + item.id + ' div.header h3 a').on('press', function () {
                            $(pathToStreamItem + '#' + item.id + ' .cartlink').trigger('click');
                        });
                        $(pathToStreamItem + '#' + item.id).on('flick', function (e) {
                            //If article cart'd already, then RtoL flick prompts for title change
                            if (e.orientation === 'horizontal' && e.direction === -1 && $(pathToStreamItem).hasClass('cartulized')) {
                                var articleid = $(pathToStreamItem).data('articleid');
                                var newtitle = prompt("Enter new title...");
                                if (newtitle != null) {
                                    //Make the call
                                    $.ajax({
                                        url: '/cgi/in/setarticletitle?id=' + articleid + '&title=' + newtitle,
                                        type: "GET",
                                        timeout: 20000,
                                        dataType: 'json',
                                        success: function (data) {
                                            if (data.status == "false") {
                                                showMessage(data.description, data.status, 5);
                                            } else {
                                                $(pathToStreamItem + '#' + item.id + ' div.header h3 a').empty().append(newtitle);
                                            }
                                        }
                                    });
                                }
                            }
                            //If article not cart'd already, then RtoL flick carts it
                            if (e.orientation === 'horizontal' && e.direction === -1 && !$(pathToStreamItem).hasClass('cartulized')) {
                                $(pathToStreamItem + '#' + item.id + ' .cartlink').trigger('click');
                            }
                        })
                    })();
                }
            });
        });

        return false;
    }


    function _getRiverStickyItems(cached) {
        var timestamp = Math.round(new Date().getTime() / 1000);

        if (cached == true) {
            _populateGridSticky(_calculateColumnCount());
            return true;
        }

        //Get the sticky river json data and parse it
        return $.ajax({
            url: '/cgi/out/list.river.sticky?ts=' + timestamp,
            dataType: "json",
            success: function (data) {
                //console.log('store data: ' + data);
                sessionStorage.setItem(lsStickyDataKey, JSON.stringify(data));
                _populateGridSticky(_calculateColumnCount(), data);
            },
            error: function (x, y, z) {
                _changeStreamNotice('Error: ' + x.responseText);
            }
        });
    }


    function _getRiverItems(cached) {
        if (cached == true) {
            _populateGrid(_calculateColumnCount());
            return true;
        }

        //Get the river json data and parse it
        return $.ajax({
            url: riverJsonUrl + '?callback=?',
            jsonpCallback: 'onGetRiverStream',
            dataType: "jsonp",
            success: function (data) {
                //console.log('store data: ' + data);
                sessionStorage.setItem(lsRiverDataKey, JSON.stringify(data));
                _populateGrid(_calculateColumnCount(), data);
            },
            error: function (x, y, z) {
                _changeStreamNotice('Error: ' + x.responseText);
            }
        });

    }


    function _removeStickyItemLS(index) {
        //Get data out of local storage
        var lsdata = sessionStorage.getItem(lsStickyDataKey);
        if (lsdata !== null) {
            data = JSON.parse(lsdata);
            data.data.items[index].hidden = 1;
            sessionStorage.setItem(lsStickyDataKey, JSON.stringify(data));
            console.log('sticky item removed');
        }
        return true;
    }


    function _bindEnclosureLinks(elid) {
        var elid = (typeof elid === "undefined") ? "" : elid;

        if (platform == "mobile" || platform == "tablet") {
            return true;
        }

        $(elid + ' .enclosureview a').unbind('click');
        $(elid + ' .enclosureview a').click(function () {
            if ($(this).children('img').length > 0) {
                openMediaShadowbox($(this).children('img'));
                return false;
            }
        });

        return true;
    }


    function _bindEmbedActivations(elid) {
        var elid = (typeof elid === "undefined") ? "" : elid;

        $(elid + ' .enciframe.inactive').each(function (k, v) {
            var _this = this;
            $(this).children('div.play').unbind('click');
            $(this).children('div.play').bind('click', function () {
                var dsrc = $(_this).attr("data-src");
                var $iframe = $('<iframe>', {class: 'encobj enciframe', src: dsrc, frameborder: 0});
                $(_this).replaceWith($iframe)
            });
        });

        return true;
    };


    function _makePostSticky(elid, pid, idx, fid) {
        $(elid).addClass('sticky');
        $(elid).append('<a class="aUnSticky" href="#" data-idx="' + idx + '" data-id="' + pid + '" data-feedid="' + fid + '"><img class="icon-unsticky" src="/images/blank.gif" alt="" /></a>');

        return true;
    }


    function _calculateColumnCount() {
        if (platform == "mobile" || platform == "tablet") {
            return 1;
        }

        if (cuRiverColumnCount > 0) {
            return cuRiverColumnCount;
        }

        var streamWidth = _getStreamWidth();

        var ccsize = streamWidth / 500;

        //Limit to a max of 5 columns for now
        var colcount = Math.min(5, Math.floor(ccsize));
        if (ccsize < 2) {
            colcount = 1;
        }

        return colcount;
    }


//-----------------------------------------------------------------------------------

    return {
        searchPostLoad: _searchPostLoad,
        removeSessionData: _removeSessionData,
        rebindEverything: _rebindEverything,
        showOnlyItems: _showOnlyItems,
        showAllItems: _showAllItems,
        isAvatar: _isAvatar,
        getBodyText: _getBodyText,
        countEnclosuresOfType: _countEnclosuresOfType,
        isImage: _isImage,
        isAudio: _isAudio,
        isVideo: _isVideo,
        isIframe: _isIframe,
        getMediaType: _getMediaType,
        getEnclosureSize: _getEnclosureSize,
        clearActiveFeeds: _clearActiveFeeds,
        addActiveFeed: _addActiveFeed,
        getDomain: _getDomain,
        getFavicon: _getFavicon,
        focusFirstVisibleArticle: _focusFirstVisibleArticle,
        focusThisArticle: _focusThisArticle,
        focusThisArticleFooter: _focusThisArticleFooter,
        focusPreviousArticle: _focusPreviousArticle,
        focusNextArticle: _focusNextArticle,
        focusPreviousFeed: _focusPreviousFeed,
        focusNextFeed: _focusNextFeed,
        targetNextArticle: _targetNextArticle,
        bindMicroblogLinks: _bindMicroblogLinks,
        bindCartLinks: _bindCartLinks,
        bindStickyLinks: _bindStickyLinks,
        sortGrid: _sortGrid,
        resizeGrid: _resizeGrid,
        buildRiver: _buildRiver,
        bindEnclosureLinks: _bindEnclosureLinks,
        bindEmbedActivations: _bindEmbedActivations,
        calculateColumnCount: _calculateColumnCount
    };

//-----------------------------------------------------------------------------------
}());