$(document).ready(function () {
    var loading = $('div.loading');
    var outliner = $('#outliner');
    var hoverTimer = null;
    var sheetopen = $('#divEditSheetOpen');
    var srmodal = $('#mdlEditorSearchReplace');
    var sheetinclude = $('#divEditSheetInclude');
    var sheetimport = $('#divEditSheetImport');
    var chkToggleRender = $('.rendertoggle');
    var chkDisqusInclude = $('.menuDisqusToggle');
    var chkToggleWatch = $('.menuWatchToggle');
    var chkToggleLock = $('.menuLockToggle');
    var menubar = $('#menubarEditor');
    var nodeTypeSelector = menubar.find('.menuType ul.dropdown-menu');
    var elTitle = $('.divOutlineTitle input.title');
    var oscillator;
    var amp;
    var nextStep = 0;
    var speed = 0.9;
    var dit = 60;
    var audioOut = false;
    var audioAnimate;

    //Node type menu sets
    var nodeTypeMenuStandard = [
        {class: "menuTypeNone", title: "No type.", type: "none", text: "not set"},
        {class: "menuTypeOutline", title: "A basic indented look.", type: "outline", text: "Outline"},
        {class: "menuTypeDocument", title: "A flat, fully expanded document.", type: "document", text: "Document"},
        {class: "menuTypeIndex", title: "A collapsible outline.", type: "index", text: "Index"},
        {class: "menuTypeHtml", title: "Renders as raw html.", type: "html", text: "Html"},
        {
            class: "menuTypeCollaborate",
            title: "Enable collaboration on this outline.",
            type: "collaborate",
            text: "Collaborate"
        },
        {
            class: "menuTypePresentation",
            title: "Make this outline a slide show presentation.",
            type: "presentation",
            text: "Presentation"
        },
        {class: "menuTypeRedirect", title: "A meta-refresh redirect.", type: "redirect", text: "Redirect"},
        {class: "menuTypeTabs", title: "A tabbed view.", type: "tabs", text: "Tabs"},
        {class: "menuTypeMenu", title: "A menu bar structure.", type: "menu", text: "Menu"},
        {class: "menuTypeLink", title: "Link to a url.", type: "link", text: "Link"},
        {class: "menuTypeInclude", title: "Bring in another outline's content.", type: "include", text: "Include"},
        {class: "menuTypeImage", title: "Add an image node to the outline.", type: "image", text: "Image"},
        {class: "menuTypeCode", title: "Add a code node to the outline.", type: "code", text: "Code"}
    ];
    var nodeTypeMenuRSS = [
        {class: "menuTypeNone", title: "No type.", type: "none", text: "not set"},
        {class: "menuTypeOutline", title: "A basic indented look.", type: "outline", text: "Outline"},
        {class: "menuTypeDescription", title: "Describe this item or feed.", type: "description", text: "Description"},
        {class: "menuTypeLink", title: "Link to a url.", type: "link", text: "Link"},
        {class: "menuTypeInclude", title: "Bring in another outline's content.", type: "include", text: "Include"},
        {class: "menuTypeImage", title: "Add an image node to the outline.", type: "image", text: "Image"},
        {class: "menuTypeItem", title: "An rss feed item.", type: "item", text: "Item"},
        {class: "menuTypeTitle", title: "The title of an item.", type: "title", text: "Title"},
        {class: "menuTypeCategory", title: "Podcast categories.", type: "category", text: "Category"},
        {class: "menuTypeExplicit", title: "Explicit content flag.", type: "explicit", text: "Explicit"},
        {class: "menuTypeKeywords", title: "Keywords for podcast.", type: "keyword", text: "Keyword"},
        {class: "menuTypeEnclosure", title: "An item enclosure.", type: "enclosure", text: "Enclosure"},
        {class: "menuTypeGuid", title: "A guid node for an item.", type: "guid", text: "Guid"},
        {class: "menuTypeAuthor", title: "The author of the feed.", type: "author", text: "Author"},
        {class: "menuTypePubDate", title: "The pubDate of the feed or item.", type: "pubdate", text: "PubDate"}
    ];

    //New button
    menubar.find('.menuNew').click(function () {
        window.location = "/editor";
    });
    menubar.find('.menuNewRSS').click(function () {
        window.location = "/editor?type=1"
    });

    //Save buttons
    menubar.find('.menuSaveAs').click(function () {
        if (lockedOutline) {
            bbCaption = "This outline is locked. Type a new file name.";
        } else {
            bbCaption = "What file name do you want to use?";
        }
        bootbox.prompt(bbCaption, function (result) {
            if (result !== null) {
                //Grab the current title
                title = elTitle.val();

                //Set a title
                opSetTitle(title);
                lasttitle = title;

                //Get the filenames sorted
                //oldfilename = filename;
                filename = result.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';

                //Save the file
                saveFile(title, filename, type, redirect, includeDisqus, wysiwygOn, watchedOutline, lockedOutline, opOutlineToXml(ownerName, ownerEmail));
            }
        });
        $('#dropdownSave').dropdown('toggle');
        return false;
    });
    menubar.find('.menuSave').click(function () {
        //Grab the current title
        title = elTitle.val();

        //Get a file name
        oldfilename = "";
        if (filename == "") {
            if (title != "") {
                filename = title.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';
            } else {
                filename = bufilename;
            }
        }

        //Set a title
        opSetTitle(title);
        lasttitle = title;

        //Save the file
        if (lockedOutline && wasLocked) {
            menubar.find('.menuSaveAs').trigger('click');
        } else {
            saveFile(title, filename, type, redirect, includeDisqus, wysiwygOn, watchedOutline, lockedOutline, opOutlineToXml(ownerName, ownerEmail));
        }
        $('#dropdownSave').dropdown('hide');
        return false;
    });
    menubar.find('.menuSaveArticle').click(function () {
        //Grab the current title
        title = elTitle.val();

        //Get a file name
        oldfilename = "";
        if (filename == "") {
            if (title != "") {
                filename = title.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';
            } else {
                filename = bufilename;
            }
        }

        //Set a title
        opSetTitle(title);
        lasttitle = title;

        //Save the file
        if (lockedOutline && wasLocked) {
            alert("This file is currently locked for editing.");
        } else {
            saveFile(title, filename, type, redirect, includeDisqus, wysiwygOn, watchedOutline, lockedOutline, opOutlineToXml(ownerName, ownerEmail), undefined, true);
        }
        $('#dropdownSave').dropdown('hide');
        return false;
    });

    //Publish button
    menubar.find('.menuPublish').click(function () {
        var pburl = "";
        if (isEmpty(url)) {
            showMessage("You must save the document first.", false, 3);
            return (false);
        }
        if (isEmpty(redirect)) {
            pburl = htmlurl;
        } else {
            pburl = 'http://' + redirect;
        }

        $('#divEditOutline .ouitem').remove();
        $('#divEditOutline').append('<div class="ouitem hide"><div class="header"><a class="articlelink" href="' + pburl + '">' + title + '</a></div></div>');
        $('#divEditOutline .ouitem').append('<div class="footer"><span class="source"><a class="articlelink" href=""></a></span></div>');
        $('#divEditOutline .ouitem .footer').append('<span class="origin">' + url + '</span>');
        newMicroblogPostWindow('#divEditOutline .ouitem', opOutlineToXml(), 1);
        return (false);
    });

    //Open button
    menubar.find('.menuOpen').click(function () {
        if (sheetopen.hasClass('open')) {
            $('div.sheet a.sheetclose').trigger('click');
            return false;
        }

        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/out/get.recentfiles',
            dataType: "json",
            success: function (data) {
                //Clear the table for new data
                $('.recentfilesopen').empty();

                //Iterate
                $.each(data.files, function (i, item) {
                    var re = /\.$/;
                    var newtitle = item.title.replace(re, "").toLowerCase();
                    var rfLocked = "";
                    if (item.locked === 1) {
                        rfLocked = ' <i class="fa fa-lock"></i> ';
                    }
                    var rfEye = "";
                    if (item.watched === 1) {
                        rfEye = ' <i class="fa fa-eye"></i> ';
                    }
                    var rfType = "";
                    if (item.type === 1) {
                        type = 1;
                        rfType = ' <i class="fa fa-rss"></i> ';
                    }
                    $('.recentfilesopen').append('<li><a style="text-transform: capitalize;" href="/editor?url=' + item.url + '">' + newtitle + '</a> ' + prettyDate(item.time * 1000).toLowerCase() + '. ' + rfType + rfLocked + rfEye + '</li>');
                });

                //Open the dropdown sheet
                sheetopen.toggleClass('open');
                sheetopen.css('min-height', '80%');
                sheetopen.find('.list-container').css('min-height', '90%');
            }
        });

        return false;
    });

    //Redirect button
    menubar.find('.menuRedirect').click(function () {
        bootbox.prompt("What url should redirect to this document?", function (result) {
            if (result !== null) {
                //Store the xml data
                var opml = opOutlineToXml();
                redirect = result;
                updateOutlineInfo('', '', result);
            } else {
                redirect = "";
                updateOutlineInfo('', '', '');
            }
        });

        //Put an existing value in the input box
        setTimeout(function () {
            $('input.bootbox-input').val(redirect);
        }, 500);
    });

    //Close sheet button
    $('div.sheet a.sheetclose').click(function () {
        sheetopen.css('min-height', '');
        sheetopen.find('.list-container').css('min-height', '');
        $('div.sheet').removeClass('open');
    });

    //Open file by url button
    sheetopen.find('a.openbyurl').click(function () {
        bootbox.prompt("What url to open?", function (result) {
            if (result !== null) {
                window.location = "/editor?url=" + result;
            }
        });
    });

    //Import by url button
    sheetimport.find('a.openbyurl').click(function () {
        bootbox.prompt("Url of outline to import?", function (geturl) {
            if (geturl !== null) {
                //Set the node's attributes
                opSetOneAtt('type', 'import');
                opSetOneAtt('url', geturl);
                //Now call out and get the outline data
                if (!opHasSubs()) {
                    $.ajax({
                        type: 'POST',
                        url: '/cgi/out/get.url.json?url=' + geturl,
                        dataType: "json",
                        beforeSend: function () {
                            loading.show();
                        },
                        success: function (data) {
                            opInsertXml(data.data, right);
                            loading.hide();
                        },
                        error: function () {
                            showMessage("Error loading import url.", false, 5);
                            loading.hide();
                        }
                    });
                }
                //Close the sheet
                $('div.sheet').removeClass('open');
            }
        });
    });

    //Open inclusion by url button
    sheetinclude.find('a.openbyurl').click(function () {
        bootbox.prompt("Url of outline to link to?", function (geturl) {
            if (geturl !== null) {
                //Set the node's attributes
                opSetOneAtt('type', 'include');
                opSetOneAtt('url', geturl);
                //Now call out and get the outline data
                if (!opHasSubs()) {
                    $.ajax({
                        type: 'POST',
                        url: '/cgi/out/get.url.json?url=' + geturl,
                        dataType: "json",
                        beforeSend: function () {
                            loading.show();
                        },
                        success: function (data) {
                            opInsertXml(data.data, right);
                            loading.hide();
                        },
                        error: function () {
                            showMessage("Error loading include url.", false, 5);
                            loading.hide();
                        }
                    });
                }
                //Close the sheet
                $('div.sheet').removeClass('open');
            }
        });
    });

    //Type dropdown button
    menubar.find('.menuType').on('click', '.menuTypeSelection', function () {
        var thistype = $(this).attr('data-type');

        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + $(this).html() + ') <b class="caret"></b>');
        if (thistype == 'link' || thistype == 'redirect') {
            bootbox.prompt("What url to point to?", function (result) {
                if (result !== null) {
                    opSetOneAtt('type', thistype);
                    opSetOneAtt('url', result);
                }
            });
        } else if (thistype == 'include') {
            //Make the ajax call to get the recent file list
            $.ajax({
                type: 'POST',
                url: '/cgi/out/get.recentfiles',
                dataType: "json",
                success: function (data) {
                    //Clear the table for new data
                    $('.templateopen').empty();

                    //Iterate
                    $.each(data.files, function (i, item) {
                        var re = /\.$/;
                        var newtitle = item.title.replace(re, "").toLowerCase();
                        //Add an entry for each url returned
                        $('.templateopen').append('<li><a href="#" data-url="' + item.url + '">' + newtitle + '</a> ' + prettyDate(item.time * 1000).toLowerCase() + '.</li>');
                    });

                    //Link apply function to each file link
                    $('.templateopen li a').click(function () {
                        var geturl = $(this).data('url');
                        sheetinclude.removeClass('open');
                        //Set the node's attributes
                        opSetOneAtt('type', thistype);
                        opSetOneAtt('url', geturl);
                        //Now call out and get the outline data
                        $.ajax({
                            type: 'POST',
                            url: '/cgi/out/get.url.json?url=' + geturl,
                            dataType: "json",
                            beforeSend: function () {
                                loading.show();
                                opDeleteSubs();
                            },
                            success: function (data) {
                                opInsertXml(data.data, right);
                                loading.hide();
                            },
                            error: function () {
                                showMessage("Error loading include url.", false, 5);
                                loading.show();
                            }
                        });
                        return false;
                    });

                    //Open the dropdown sheet
                    sheetinclude.toggleClass('open');
                }
            });
        } else {
            opSetOneAtt('type', thistype);
        }

        //None type
        if (thistype == 'none') {
            opSetOneAtt('icon', 'caret-right');
            outliner.concord().op.attributes.removeOne('icon');
            outliner.concord().op.attributes.removeOne('type');
            outliner.concord().op.attributes.removeOne('url');
        }

        //Image type node
        if (thistype == 'image') {
            if (!opGetOneAtt('url')) {
                var text = opGetLineText();
                var match = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/.exec(text);
                if (match !== null) {
                    opSetOneAtt('url', match[0]);
                } else {
                    bootbox.prompt("What is the url of the image?", function (result) {
                        if (result !== null) {
                            opSetOneAtt('type', thistype);
                            opSetOneAtt('url', result);
                            opSetLineText('<img src="' + result + '">');
                        }
                    });
                }
            }
            opSetOneAtt('icon', 'image');
        }

        //Description type node
        if (thistype == 'description') {
            opSetOneAtt('icon', 'newspaper-o');
        }

        //Enclosure type node
        if (thistype == 'enclosure') {
            if (!opGetOneAtt('url')) {
                var text = opGetLineText();
                var match = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/.exec(text);
                if (match !== null) {
                    opSetOneAtt('url', match[0]);
                } else {
                    bootbox.prompt("What is the url of the enclosure?", function (result) {
                        if (result !== null) {
                            opSetOneAtt('type', thistype);
                            opSetOneAtt('url', result);
                            opSetLineText(result);
                        }
                    });
                }
            }
            opSetOneAtt('icon', 'file-audio-o');
        }

        //Title type node
        if (thistype == 'item') {
            //Set the node's attributes
            opSetOneAtt('type', 'item');
            opSetOneAtt('icon', 'rss-square');

            //Make the ajax call to get the recent file list
            $.ajax({
                type: 'POST',
                url: '/cgi/out/get.recentfiles',
                dataType: "json",
                success: function (data) {
                    //Clear the table for new data
                    $('.templateopen').empty();

                    //Iterate
                    $.each(data.files, function (i, item) {
                        var re = /\.$/;
                        var newtitle = item.title.replace(re, "").toLowerCase();
                        //Add an entry for each url returned
                        $('.templateopen').append('<li><a href="#" data-url="' + item.url + '">' + newtitle + '</a> ' + prettyDate(item.time * 1000).toLowerCase() + '.</li>');
                    });

                    //Link apply function to each file link
                    $('.templateopen li a').click(function () {
                        var geturl = $(this).data('url');
                        sheetimport.removeClass('open');
                        //Now call out and get the outline data
                        $.ajax({
                            type: 'POST',
                            url: '/cgi/out/get.url.json?url=' + geturl,
                            dataType: "json",
                            beforeSend: function () {
                                loading.show();
                            },
                            success: function (data) {
                                opInsertXml(data.data, right);
                                loading.hide();
                                //Prompt for a url
                                bootbox.prompt("Set a url for this item?", function (result) {
                                    if (result !== null) {
                                        opSetOneAtt('url', result);
                                    }
                                    //Prompt for a title
                                    bootbox.prompt("Set a title for this item?", function (result) {
                                        if (result !== null) {
                                            opSetLineText(result);
                                        }
                                    });
                                });
                            },
                            error: function () {
                                showMessage("Error loading import url.", false, 5);
                                loading.hide();
                            }
                        });
                        return false;
                    });

                    //Open the dropdown sheet
                    sheetimport.toggleClass('open');

                }
            });
        }

        //Title type node
        if (thistype == 'title') {
            opSetOneAtt('icon', 'terminal');
        }

        //Guid type node
        if (thistype == 'guid') {
            opSetOneAtt('icon', 'qrcode');
        }

        //Author type node
        if (thistype == 'author') {
            opSetOneAtt('icon', 'user');
        }

        //Pubdate type node
        if (thistype == 'pubdate') {
            opSetOneAtt('icon', 'clock-o');
            if(isBlank(opGetLineText())) opSetLineText(new Date().toUTCString());
        }


        //Category type node
        if (thistype == 'category') {
            opSetOneAtt('icon', 'tags');
            if(isBlank(opGetLineText())) opSetLineText("Category");
        }

        //Explicit type node
        if (thistype == 'explicit') {
            opSetOneAtt('icon', 'exclamation-triangle');
            opSetLineText("Yes")
        }

        //Keyword type node
        if (thistype == 'keyword') {
            opSetOneAtt('icon', 'info-circle');
            if(isBlank(opGetLineText())) opSetLineText("Keyword");
        }

        //Collaboration node
        if (thistype == 'collaborate') {
            opSetLineText("[Collaboration node - do not delete.]");
        }

        //Presentation
        if (thistype == 'presentation') {
            showMessage("Now build your slideshow structure as children below this node.", true, 5);
        }

        return true;
    });

    //Toggle render mode
    chkToggleRender.click(function () {
        if ($(this).parent().hasClass('active')) {
            console.log('T: ' + outliner.concord().op.setRenderMode(false));
            wysiwygOn = false;
            $(this).parent().removeClass('active');
        } else {
            console.log('F: ' + outliner.concord().op.setRenderMode(true));
            wysiwygOn = true;
            $(this).parent().addClass('active');
        }
    });

    //Toggle disqus comments in html
    chkDisqusInclude.click(function () {
        if ($(this).parent().hasClass('active')) {
            $(this).parent().removeClass('active');
            includeDisqus = false;
        } else {
            $(this).parent().addClass('active');
            includeDisqus = true;
        }
    });

    //Toggle watch this outline
    chkToggleWatch.click(function () {
        if ($(this).parent().hasClass('active')) {
            $(this).parent().removeClass('active');
            watchedOutline = false;
        } else {
            $(this).parent().addClass('active');
            watchedOutline = true;
        }
    });

    //Toggle locked outline
    chkToggleLock.click(function () {
        if ($(this).parent().hasClass('active')) {
            $(this).parent().removeClass('active');
            lockedOutline = false;
            wasLocked = false;
        } else {
            $(this).parent().addClass('active');
            lockedOutline = true;
        }
    });

    //Toolbox buttons
    menubar.find('.menuAddLink').click(function () {
        editorToolAddLink();
        return false;
    });
    menubar.find('.menuSearchReplace').click(function () {
        srmodal.off('shown').on('shown', function () {
            srmodal.find('input.srsearch').focus();
        });
        srmodal.modal('show');
    });
    menubar.find('.menuImportOutline').click(function () {
        //Make the ajax call to get the recent file list
        $.ajax({
            type: 'POST',
            url: '/cgi/out/get.recentfiles',
            dataType: "json",
            success: function (data) {
                //Clear the table for new data
                $('.templateopen').empty();

                //Iterate
                $.each(data.files, function (i, item) {
                    var re = /\.$/;
                    var newtitle = item.title.replace(re, "").toLowerCase();
                    //Add an entry for each url returned
                    $('.templateopen').append('<li><a href="#" data-url="' + item.url + '">' + newtitle + '</a> ' + prettyDate(item.time * 1000).toLowerCase() + '.</li>');
                });

                //Link apply function to each file link
                $('.templateopen li a').click(function () {
                    var geturl = $(this).data('url');
                    sheetimport.removeClass('open');
                    //Set the node's attributes
                    opSetOneAtt('type', 'import');
                    opSetOneAtt('url', geturl);
                    //Now call out and get the outline data
                    $.ajax({
                        type: 'POST',
                        url: '/cgi/out/get.url.json?url=' + geturl,
                        dataType: "json",
                        beforeSend: function () {
                            loading.show();
                        },
                        success: function (data) {
                            opInsertXml(data.data, right);
                            loading.hide();
                        },
                        error: function () {
                            showMessage("Error loading import url.", false, 5);
                            loading.hide();
                        }
                    });
                    return false;
                });

                //Open the dropdown sheet
                sheetimport.toggleClass('open');

            }
        });
    });
    menubar.find('.menuInsertFiles').click(function () {
        showEditorFileDropZone();
        $('#uploadifive-editor_upload > input[type=file]:last-child').trigger('click');
        return false;
    });
    menubar.find('.menuArchiveNodes').click(function () {
        //Get a timestamp
        var arctime = Math.round((new Date()).getTime() / 1000);
        var arcplaceholder = "#arc#" + arctime + "#arc#";
        var multiarc = false;
        var hadsubs = opHasSubs();

        //Convert the selected nodes to xml

        //Get the filenames sorted
        atitle = "Archived from " + title.replace(/\W/g, '').substring(0, 20) + ' at ' + arctime;
        afilename = "archive" + title.replace(/\W/g, '').substring(0, 20) + '-' + arctime + '.opml';
        if (outliner.find(".concord-node.selected").length > 1) {
            multiarc = true;
            atext = "Archived - " + arctime;
        } else if (opGetLineText() == "") {
            atext = "Archived - " + arctime;
        } else {
            atext = opGetLineText();
        }

        //If this is a single node with subs then we treat the subs as the thing to be archived

        //Delete the selected nodes and replace with a single include node that points to the url of the new archive
        opCut();
        opInsert(atext, down);
        opGo(right, 1);
        opInsert("", right);
        opPaste();
        opGo(up, 32767);
        outliner.concord().op.deleteLine();
        //opGo(left, 1);
        var xmlArchive = outliner.concord().op.cursorToXmlSubsOnly();
        if (!multiarc && hadsubs) {
            opGo(right, 1);
            var xmlArchive = outliner.concord().op.cursorToXmlSubsOnly();
            opGo(left, 1);
        }
        //alert(xmlArchive);
        opDeleteSubs();
        opCollapse();
//        if( multiarc ) {
//            opReorg(right, 1);
//        }
        opSetAtts({
            "type": "include",
            "url": arcplaceholder
        });

        //Save the file
        saveArchive(atitle, afilename, type, '', false, false, xmlArchive, arcplaceholder);
    });
    menubar.find('.menuGenerateMorse').click(function () {
        if (audioOut == true) {
            nextStep = stopTone();
            return false;
        }

        var morsestring = getSelected();
        if (morsestring == "" || morsestring == "...") {
            morsestring = opGetLineText();
        } else {
            morsestring = new String(morsestring);
        }

        morsestring = stripTags(morsestring);
        morsestring.replace(/[^a-zA-Z 0-9?.]+/g, '');
        morsestring = morsestring.toLowerCase().split("");

        //Initialize values according to current speed
        cDit = (dit / speed) / 1000;
        cDot = (dit / speed) / 1000;
        cDash = (cDot * 3);
        clGap = cDash + cDot;
        cwGap = (cDot * 7);

        initAudio();
        nextStep = startTone(700);


        morsestring.forEach(function (letter) {
            console.log("STarting letter: [" + letter + "]");
            if (letter !== " ") nextStep += clGap;
            if (letter === " ") {
                nextStep += cwGap;
            } else {
                var md = convertLetterToMorseData(letter);
                nextStep = outputLetter(md, nextStep);
            }
        });

        nextStep = stopTone(nextStep + 1);
        return false;
    });
    menubar.find('.menuChangeTimestamp').click(function () {
        var oldcreated = opGetOneAtt('created');
        bootbox.prompt({
            title: "Set a new timestamp.",
            value: oldcreated,
            callback: function (newcreated) {
                if (newcreated !== null) {
                    opSetOneAtt('created', newcreated);
                }
            }});
        return false;
    });
    $('.modalsrgo').click(function () {
        //Hide the form
        srmodal.find('form.srpostform').hide();
        srmodal.find('.spinner').show();

        //Get the terms
        var search = srmodal.find('input.srsearch').val();
        var replace = srmodal.find('input.srreplace').val();

        //Iterate over every node, doing the replacement
        opVisitAll(function (op) {
            var ltext = op.getLineText();
            if (ltext.indexOf(search) != -1) {
                var r = new RegExp(search, 'g');
                op.setLineText(ltext.replace(r, replace));
            }
        });

        //Close the modal
        setTimeout(function () {
            srmodal.modal('hide');
            //Un-hide the form
            srmodal.find('.spinner').hide();
            srmodal.find('form.srpostform').show();
        }, 2000);
    });

    //Handle opacity on focus change
    elTitle.on("focus", function () {
        clearTimeout(hoverTimer);
        $('.divOutlineTitle').removeClass('dim');
    });
    elTitle.on("blur", function () {
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(function () {
            $('.divOutlineTitle').addClass('dim');
        }, 3000);
    });

    //Full opacity on title hover
    $('.divOutlineTitle').hover(
        function () {
            clearTimeout(hoverTimer);
            $('.divOutlineTitle').removeClass('dim');
        },
        function () {
            if (!$('.divOutlineTitle input.title').is(':focus')) {
                hoverTimer = setTimeout(function () {
                    $('.divOutlineTitle').addClass('dim');
                }, 3000);
            }
        }
    );

    //Load up the outline
    outliner.concord({
        "callbacks": {
            "opCursorMoved": opCursorMovedCallback,
            "opExpand": opExpandCallback,
            "opCollapse": opCollapseCallback,
            "opKeystroke": opKeystrokeCallback,
            "opInsert": opInsertCallback
        },
        "prefs": {
            "outlineFont": "Calibri",
            "outlineFontSize": 18,
            "outlineLineHeight": 24,
            "renderMode": chkToggleRender.parent().hasClass('active'),
            "readonly": false,
            "typeIcons": appTypeIcons
        }
    });

    //Load the outline content
    if (!isBlank(url) && type != 2) {
        $.ajax({
            type: 'POST',
            url: '/cgi/out/get.url.json',
            data: {
                "url": url
            },
            dataType: "json",
            beforeSend: function () {
                loading.show();
            },
            success: function (data) {
                //Show returned info and re-enable the save btton
                //url = data.url;
                //htmlurl = data.html;

                //Load the outline body data
                opXmlToOutline(data.data);
                title = opGetTitle();

                //Set a title
                if (title == "Untitled") {
                    elTitle.val('');
                } else {
                    elTitle.val(title);
                }
                //Set the title of the html document
                document.title = title + " - FC";


                //Set up the root node type correctly
                getRootNodeType();

                //Set the menu bar info
                //updateOutlineInfo(url, data.html, redirect);

                //Set the default toggle states
                if (includeDisqus) {
                    chkDisqusInclude.parent().addClass('active');
                }
                if (wysiwygOn) {
                    outliner.concord().op.setRenderMode(true);
                    chkToggleRender.parent().addClass('active');
                }
                if (watchedOutline) {
                    chkToggleWatch.parent().addClass('active');
                }
                if (lockedOutline) {
                    chkToggleLock.parent().addClass('active');
                }
                loading.hide();
            }
        });
    } else {
        if(type == 1) {
            opXmlToOutline(initialRssOpmlText);
        } else {
            opXmlToOutline(initialOpmlText);
        }
        title = opGetTitle();
        elTitle.val(title);
        //Set the title of the html document
        document.title = title + " - FC";

    }
    title = opGetTitle();

    //Refresh the outliner info pane
    updateOutlineInfo(url, {}, redirect);
    if (badurl == true) {
        showMessage('Parse error. Please check the url.', false, 5);
    }

    //Set type dropdown menu selections based on the document type
    if (type === 1) {
        buildNodeTypeMenu(nodeTypeMenuRSS);
    } else {
        buildNodeTypeMenu(nodeTypeMenuStandard);
    }


    //Hot keys
    $(window).bind('keydown keyup keypress', function (event) {
        if (event.ctrlKey || event.metaKey) {
            switch (String.fromCharCode(event.which).toLowerCase()) {
                case 's':
                    event.preventDefault();
                    break;
                case 'l':
                    event.preventDefault();
                    break;
                case 'f':
                    event.preventDefault();
                    break;
                case 'g':
                    event.preventDefault();
                    break;
            }
        }
    });
    key('ctrl+s,command+s', function () {
        menubar.find('.menuSave').trigger('click');
        return false;
    });
    key('ctrl+shift+space,command+shift+space', function () {
        menubar.find('.menuArchiveNodes').trigger('click');
        return false;
    });
    key('ctrl+l,command+l', function () {
        menubar.find('.menuAddLink').trigger('click');
        return false;
    });
    key('ctrl+shift+f,command+shift+f', function () {
        showEditorFileDropZone();
        $('#uploadifive-editor_upload > input[type=file]:last-child').trigger('click');
        return false;
    });
    key('ctrl+left,command+left', function () {
        if (!opInTextMode()) {
            opFirstSummit();
            window.scrollTo(0, 0);
        } else {
            var box = $('#outliner').find('.concord-cursor .concord-wrapper .concord-text')[0];
            moveCursorToStart(box);
        }
        return false;
    });
    key('ctrl+right,command+right', function () {
        if (!opInTextMode()) {
            opGo(left, 32767);
            opGo(down, 32767);
            window.scrollTo(0, 999999);
            while (opHasSubs() && opSubsExpanded()) {
                opGo(right, 1);
                opGo(down, 32767);
            }
            window.scrollTo(0, 999999);
        } else {
            var box = $('#outliner').find('.concord-cursor .concord-wrapper .concord-text')[0];
            moveCursorToEnd(box);
        }
        return false;
    });
    key('ctrl+up,command+up', function () {
        $('#outliner').concord().op.go('up', 32767);
    });
    key('ctrl+down,command+down', function () {
        $('#outliner').concord().op.go('down', 32767);
    });
    key('escape', function () {
        if (!opInTextMode()) {
            opSetTextMode(true);
        } else {
            opSetTextMode(false);
        }
    });

    //Position the drop zone
    $('#divEditorEnclosures').offset({top: 0, left: 0}).offset($('#divEditOutline').offset());

    //Save a file
    function saveFile(ftitle, fname, ftype, fredirect, fdisqus, fwysiwyg, fwatched, flocked, fopml, foldname, asarticle) {
        var _foldname = (typeof foldname === "undefined") ? "" : foldname;
        var _asarticle = (typeof asarticle === "undefined") ? false : asarticle;
        var menubar = $('#menubarEditor');

        //Render the title and byline?
        var rendertitle = true;
        if (!$('.rendertitle').is(':checked')) {
            rendertitle = false;
        }

        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/in/save.opml',
            data: {
                "opml": fopml,
                "type": ftype,
                "oldfilename": _foldname,
                "filename": fname,
                "redirect": fredirect,
                "disqus": fdisqus,
                "wysiwyg": fwysiwyg,
                "watched": fwatched,
                "locked": flocked,
                "title": ftitle,
                "rendertitle": rendertitle,
                "aid": aid,
                "articleoverwrite": asarticle
            },
            dataType: "json",
            beforeSend: function () {
                //Disable the save button and show a spinner
                menubar.find('.saves').attr('disabled', true);
                menubar.find('#dropdownSave').html('<i class="fa fa-spinner"></i> Saving...');
            },
            success: function (data) {
                //Show returned info and re-enable the save button
                url = data.url;
                htmlurl = data.html;

                if (data.status === "true") {
                    updateOutlineInfo(url, data, redirect);
                    showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                } else {
                    showMessage(data.description, data.status, 8);
                }
                menubar.find('#dropdownSave').html('Save');
                menubar.find('.saves').attr('disabled', false);
                wasLocked = flocked;

                //Set the title of the html document
                document.title = ftitle + " - FC";
            }
        });

        return true;
    }

    //Save an archive
    function saveArchive(ftitle, fname, ftype, fredirect, fdisqus, fwysiwyg, fopml, arcurlstamp) {
        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/in/save.opml',
            data: {
                "opml": fopml,
                "type": ftype,
                "oldfilename": "",
                "filename": fname,
                "redirect": fredirect,
                "disqus": fdisqus,
                "wysiwyg": fwysiwyg,
                "title": ftitle,
                "rendertitle": false
            },
            dataType: "json",
            success: function (data) {
                showMessage("Nodes archived to " + '<a href="' + data.url + '">Link</a>', data.status, 2);

                //Replace the placeholder in the archive include node href
                opFirstSummit();
                opVisitAll(function (op) {
                    if (op.attributes.exists("url")) {
                        if (op.attributes.getOne("url") == arcurlstamp) {
                            op.attributes.setOne("url", data.url);
                            return false;
                        }
                    }
                });
            }
        });

        return true;
    }

    //Linkify some text in the outline
    function editorToolAddLink() {
        var outliner = $('#outliner');
        var urltolink = opGetOneAtt('url') || "";
        var seltxt = "";

        //Is this already linked?
        var link = opGetOneAtt('url');

        //Check if the user is in text mode.  If not, we need to turn the whole node
        //into a 'link' node type
        if (!opInTextMode()) {
            seltxt = opGetLineText();
            if (/^http[s]?\:\/\//.test(seltxt)) {
                console.log(seltxt);
                urltolink = seltxt;
            } else {
                bootbox.prompt({
                    title: "Type the target link.",
                    value: urltolink,
                    callback: function (result) {
                    if (result !== null) {
                        console.log(result);
                        urltolink = result;
                        if (urltolink != "" && /^http[s]?\:\/\//.test(urltolink)) {
                            opSetOneAtt('type', 'link');
                            opSetOneAtt('url', urltolink);
                        }
                    }
                }});
            }
            if (urltolink != "" && /^http[s]?\:\/\//.test(urltolink)) {
                opSetOneAtt('type', 'link');
                opSetOneAtt('url', urltolink);
            }
        } else {
            seltxt = getSelected();
            seltxt = new String(seltxt);
            if (/^http[s]?\:\/\//.test(seltxt)) {
                console.log(seltxt);
                urltolink = seltxt;
            } else {
                bootbox.prompt("Type the target link.", function (result) {
                    if (result !== null) {
                        console.log(result);
                        urltolink = result;
                        if (urltolink != "" && /^http[s]?\:\/\//.test(urltolink)) {
                            outliner.concord().op.link(urltolink);
                        }
                    }
                });
            }
            if (urltolink != "" && /^http[s]?\:\/\//.test(urltolink)) {
                outliner.concord().op.link(urltolink);
            }
        }
        return false;
    }

    //Apply a template overlay on to this file
    function applyTemplate(urltoget) {
        //Call out and get the url
        $.ajax({
            type: 'POST',
            url: '/cgi/out/get.url.json',
            data: {
                "url": urltoget
            },
            dataType: "json",
            success: function (data) {
                var inserted = false;

                //First collapse everything
                opCollapseEverything();

                //Go to the very top of the outline and wrap what we have into a single node
                opFirstSummit();
                opInsert("[##ORIGINAL##]", "up");
                opFirstSummit();
                opDemote();
                opCollapseEverything();

                //Now read this collapsed everything node into an xml string
                original = opCursorToXml();

                //Blow away the outline
                opWipe();

                //Now bring in the template outline
                opInsertXml(data.data);

                //Find the template placeholder
                opFirstSummit();
                opVisitAll(function (op) {
                    if (op.attributes.getOne('type') == "replace") {
                        var cursor = op.getCursor();

                        //Remove replace type
                        op.attributes.removeOne('type');

                        //Put the original xml back in
                        op.insertXml(original, "up");
                        op.setCursor(cursor);
                        op.deleteLine();

                        (function () {
                            opFirstSummit();
                            opVisitAll(function (op) {
                                if (op.getLineText() == "[##ORIGINAL##]") {
                                    var cursor = op.getCursor();
                                    op.expand();
                                    op.promote();
                                    op.setCursor(cursor);
                                    op.deleteLine();
                                    opFirstSummit();
                                    op.deleteLine();
                                    return false;
                                }
                            });
                        })();

                        inserted = true;

                        return false;
                    }
                });
            }
        });
    }

    //Get root node type and set menu text
    function getRootNodeType() {
        var rootnodetype;

        opFirstSummit();
        rootnodetype = opGetOneAtt('type');
        if (typeof(rootnodetype) == "undefined") {
            rootnodetype = "not set";
        }
        $('#menubarEditor').find('.menuType > a.dropdown-toggle').html('Type (' + rootnodetype + ') <b class="caret"></b>');
        return true;
    }

    //Display outline info
    function updateOutlineInfo(url, data, redirect) {
        var elOutlineinfo = $('#menubarEditor').find('.outlineinfo');

        elOutlineinfo.html('');
        if (url != "") {
            elOutlineinfo.html('<li><a target="_blank" title="Link to opml source of this outline." href="' + url + '">OPML</a></li>');
            if (data.html == "" || data.html === undefined) {
                htmlurl = url.replace("/opml/", "/html/");
                htmlurl = htmlurl.substr(0, htmlurl.lastIndexOf(".")) + ".html";
            } else {
                htmlurl = data.html;
            }
            if (data.json == "" || data.json === undefined) {
                jsurl = url.replace("/opml/", "/json/");
                jsurl = jsurl.substr(0, jsurl.lastIndexOf(".")) + ".json";
            } else {
                jsurl = data.json;
            }
            if (data.rss == "" || data.rss === undefined) {
                rssurl = url.replace("/opml/", "/rss/");
                rssurl = rssurl.substr(0, rssurl.lastIndexOf(".")) + ".xml";
            } else {
                rssurl = data.rss;
            }
            elOutlineinfo.append('<li><a target="_blank" title="Link to rendered html version of this outline." href="' + htmlurl + '">HTML</a></li>');
            if (type === 1) {
                elOutlineinfo.append('<li><a target="_blank" title="Link to the RSS feed xml of this outline." href="' + rssurl + '">RSS</a></li>');
            } else {
                elOutlineinfo.append('<li><a target="_blank" title="Link to myword.io rendering of this outline." href="http://myword.io/?url=' + jsurl + '">MW</a></li>');
            }
        }

        if (aid !== false) {
            menubar.find('.menuSaveArticle').show();
        } else {
            menubar.find('.menuSaveArticle').hide();
        }

        if (redirect != "") {
            if (redirectHits > 0) {
                $('#menubarEditor').find('.menuRedirect').html("Redirect: " + redirect + " (" + redirectHits + ")");
            } else {
                $('#menubarEditor').find('.menuRedirect').html("Redirect: " + redirect);
            }
            $('#menubarEditor').find('.menuRedirect').parent().addClass('active');
        } else {
            $('#menubarEditor').find('.menuRedirect').html("Redirect");
            $('#menubarEditor').find('.menuRedirect').parent().removeClass('active');
        }
        return true;
    }

    //When a new node is clicked on, this callback fires
    function opCursorMovedCallback(op) {
        var nodetype = op.attributes.getOne('type');
        var nodecreated = op.attributes.getOne('created');
        var hasUrl = false;
        var hasXmlUrl = false;

        //Does the node have a url attribute?
        if(op.attributes.getOne('url') != "undefined" && !isBlank(op.attributes.getOne('url'))) {
            hasUrl = true;
        }
        if(op.attributes.getOne('xmlUrl') != "undefined" && !isBlank(op.attributes.getOne('xmlUrl'))) {
            hasXmlUrl = true;
        }

        if (typeof(nodetype) == "undefined") {
            nodetype = "not set";
        }
        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + nodetype + ') <b class="caret"></b>');

        //If a timestamp exists, show it
        if(nodecreated) {
            menubar.find('.menubar a.menuChangeTimestamp').attr('title', 'Change timestamp for this node. Current: ' + nodecreated);
        }

        //Add a link-out option for include nodes
        menubar.find('.menubar li.extLink').remove();
        menubar.find('.menubar li.extEditLink').remove();
        if (nodetype == "include" || nodetype == "import") {
            if(hasUrl) {
                menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('url') + '"><i class="fa fa-external-link" style="color:#090;"></i></a></li>');
                menubar.find('.menubar').append('<li class="extEditLink"><a target="_blank" href="/editor?url=' + op.attributes.getOne('url') + '"><i class="fa fa-edit" style="color:#090;"></i></a></li>');
            }
            return true;
        }
        if (nodetype == "link" || nodetype == "item" || nodetype == "enclosure" || nodetype == "image") {
            if(hasUrl) {
                menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('url') + '"><i class="fa fa-external-link" style="color:#090;"></i></a></li>');
            }

            return true;
        }
        if (nodetype == "rss") {
            if(hasXmlUrl) {
                menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('xmlUrl') + '"><i class="fa fa-external-link" style="color:#090;"></i></a></li>');
            }
            return true;
        }

        return true;
    }

    //Handle expansion requests for nodes
    function opExpandCallback(op) {
        var nodetype = op.attributes.getOne('type');
        if (nodetype == "link" || nodetype == "redirect") {
            gotourl = op.attributes.getOne('url');
            window.open(gotourl);
        } else if (nodetype == "import" || nodetype == "include") {
            var geturl = op.attributes.getOne('url');
            //Now call out and get the outline data only if the inclusion node doesn't have any children
            if ((nodetype == "import" && !opHasSubs()) || nodetype == "include") {
                $.ajax({
                    type: 'POST',
                    url: '/cgi/out/get.url.json?url=' + geturl,
                    dataType: "json",
                    beforeSend: function () {
                        loading.show();
                        opDeleteSubs();
                    },
                    success: function (data) {
                        opInsertXml(data.data, right);
                        loading.hide();
                    },
                    error: function () {
                        showMessage("Error refreshing inclusion content.", false, 5);
                        loading.hide();
                    }
                });
            }
        }
        return true;
    }

    //Handle insertions in outline
    function opInsertCallback(op) {
        //Put a timestamp on each new node
        op.attributes.setOne("created", new Date().toUTCString());
        //alert(op.attributes.getOne("created"));
    }

    //Handle collapse requests for nodes
    function opCollapseCallback(op) {
        var nodetype = op.attributes.getOne('type');
        //If we collapse an include node, delete it's sub-nodes
        if (nodetype == "include") {
            op.deleteSubs();
        }
        return true;
    }

    //Handle some special key stroke stuff
    function opKeystrokeCallback(event) {
        console.log(event)

        //CTRL+SHIFT+Enter should duplicate this line above the current line
        if (event.which == 13 && event.shiftKey && (event.ctrlKey || event.metaKey)) {
            var dupline = opCursorToXml();
            opInsertXml(dupline, 'up');
            opSetTextMode(false);
            opGo(up, 1);
            opSetOneAtt("created", new Date().toUTCString());
            outliner.op.deleteLine();
            return false;
        }
        //CTRL+Enter should duplicate this line below the current line
        if (event.which == 13 && (event.ctrlKey || event.metaKey)) {
            var dupline = opCursorToXml();
            opInsertXml(dupline, 'down');
            opSetTextMode(false);
            opGo(down, 1);
            opSetOneAtt("created", new Date().toUTCString());
            outliner.op.deleteLine();
            return false;
        }
        //SHIFT+Enter should create a new blank line above the current line
        if (event.which == 13 && event.shiftKey) {
            opInsert("", 'up');
            opSetTextMode(false);
            outliner.op.deleteLine();
            return false;
        }
        //Enter key
        //if (event.which == 13) {
        //    opSetOneAtt("created", new Date().toUTCString());
        //    return false;
        //}
        //Stop concord from responding to these key combos
        if (event.ctrlKey || event.metaKey) {
            switch (String.fromCharCode(event.which).toLowerCase()) {
                case 'l':
                    event.which = 0;
                    break;
            }
        }
    }

    //Move cursors within a content editable element
    //___via: http://stackoverflow.com/questions/1125292/how-to-move-cursor-to-end-of-contenteditable-entity
    function moveCursorToStart(contentEditableElement) {
        var range, selection;
        if (document.createRange)//Firefox, Chrome, Opera, Safari, IE 9+
        {
            range = document.createRange();//Create a range (a range is a like the selection but invisible)
            range.selectNodeContents(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(true);//collapse the range to the end point. false means collapse to end rather than the start
            selection = window.getSelection();//get the selection object (allows you to change selection)
            selection.removeAllRanges();//remove any selections already made
            selection.addRange(range);//make the range you have just created the visible selection
        }
        else if (document.selection)//IE 8 and lower
        {
            range = document.body.createTextRange();//Create a range (a range is a like the selection but invisible)
            range.moveToElementText(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
            range.select();//Select the range (make it the visible selection
        }
    }

    function moveCursorToEnd(contentEditableElement) {
        var range, selection;
        if (document.createRange)//Firefox, Chrome, Opera, Safari, IE 9+
        {
            range = document.createRange();//Create a range (a range is a like the selection but invisible)
            range.selectNodeContents(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
            selection = window.getSelection();//get the selection object (allows you to change selection)
            selection.removeAllRanges();//remove any selections already made
            selection.addRange(range);//make the range you have just created the visible selection
        }
        else if (document.selection)//IE 8 and lower
        {
            range = document.body.createTextRange();//Create a range (a range is a like the selection but invisible)
            range.moveToElementText(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
            range.select();//Select the range (make it the visible selection
        }
    }

    //Uploadifive handler
    if (gDevice != "android") {
        $(function () {
            $('#editor_upload').uploadifive({
                'auto': true,
                'method': 'post',
                'dnd': true,
                'queueID': 'editor_queue',
                'uploadScript': '/cgi/in/upload',
                'simUploadLimit': 1,
                'removeCompleted': true,
                'formData': {
                    'datestamp': gDatestamp
                },
                'onSelect': function (queue) {
                    $('#divEditorEnclosures').show();
                },
                'onClearQueue': function (queue) {
                    $('input.postenclosure').remove();
                },
                'onCancel': function (file) {
                    $("input.postenclosure[data-filename='" + file.name + "']").remove();
                },
                'onQueueComplete': function () {
                    hideEditorFileDropZone();
                    $('#editor_upload').uploadifive('clearQueue');
                },
                'onUploadComplete': function (file, data) {
                    var jdata = $.parseJSON(data);
                    $('.complete .filename:contains("' + file.name + '")').each(function (index) {
                        if (isImage(jdata.url)) {
                            opInsertImage(jdata.url);
                            opSetOneAtt('type', 'image');
                            opSetOneAtt('icon', 'image');
                            opSetOneAtt('url', jdata.url);
                        } else {
                            if(type === 1) {
                                opInsert(jdata.url, down);
                                if(isAudio(jdata.url)) {
                                    opSetOneAtt('type', 'enclosure');
                                    opSetOneAtt('icon', 'file-audio-o');
                                } else {
                                    opSetOneAtt('type', 'link');
                                }
                                opSetOneAtt('url', jdata.url);
                            } else {
                                opInsert('<a href="' + jdata.url + '">' + file.name + '</a>', down);
                                opSetOneAtt('type', 'link');
                                opSetOneAtt('url', jdata.url);
                            }
                            opSetOneAtt('mimetype', jdata.type);
                            opSetOneAtt('length', jdata.length);
                        }
                    });
                }
            })
        });
    }

    //Show file drop zone
    function showEditorFileDropZone() {
        $('#divEditorEnclosures').width($('#divEditOutline').width() - 8);
        $('#divEditorEnclosures').height($('#divEditOutline').height() + 8);
        $('input[type=file]').hide();
        $('#divEditorEnclosures').show();
        key.unbind('escape');
        key('escape', function () {
            hideEditorFileDropZone();
        });
        return true;
    }

    //Hide the file drop zone
    function hideEditorFileDropZone() {
        $('#divEditorEnclosures').hide();
        key.unbind('escape');
        key('escape', function () {
            if (!opInTextMode()) {
                opSetTextMode(true);
            } else {
                opSetTextMode(false);
            }
        });
        return true;
    }

    //When a drag enters the screen, reveal the upload zone
    $('body').on('dragenter', function () {
        return showEditorFileDropZone();
    });

    // Create an oscillator and an amplifier.
    function initAudio() {
        // Use audioContext from webaudio_tools.js
        if (audioContext) {
            oscillator = audioContext.createOscillator();
            fixOscillator(oscillator);
            oscillator.frequency.value = 440;
            amp = audioContext.createGain();
            amp.gain.value = 0;

            // Connect oscillator to amp and amp to the mixer of the audioContext.
            // This is like connecting cables between jacks on a modular synth.
            oscillator.connect(amp);
            amp.connect(audioContext.destination);
            //writeMessageToID( "soundStatus", "<p>Audio initialized.</p>");
        }
    }

    // Set the frequency of the oscillator and start it running.
    function startTone(frequency) {
        audioOut = true;
        var now = audioContext.currentTime;

        oscillator.start(0);
        oscillator.frequency.setValueAtTime(frequency, now);

        audioAnimate = setInterval(function () {
            if (menubar.find('.menuGenerateMorse').find('i.fa').hasClass('fa-tty')) {
                menubar.find('.menuGenerateMorse').find('i.fa').removeClass('fa-tty').addClass('fa-volume-off');
            } else if (menubar.find('.menuGenerateMorse').find('i.fa').hasClass('fa-volume-off')) {
                menubar.find('.menuGenerateMorse').find('i.fa').removeClass('fa-volume-off').addClass('fa-volume-down');
            } else if (menubar.find('.menuGenerateMorse').find('i.fa').hasClass('fa-volume-down')) {
                menubar.find('.menuGenerateMorse').find('i.fa').removeClass('fa-volume-down').addClass('fa-volume-up');
            } else if (menubar.find('.menuGenerateMorse').find('i.fa').hasClass('fa-volume-up')) {
                menubar.find('.menuGenerateMorse').find('i.fa').removeClass('fa-volume-up').addClass('fa-volume-off');
            }
        }, 300);
        // Ramp up the gain so we can hear the sound.
        // We can ramp smoothly to the desired value.
        // First we should cancel any previous scheduled events that might interfere.
        amp.gain.cancelScheduledValues(now);
        // Anchor beginning of ramp at current value.
        amp.gain.setValueAtTime(0, now);
        //amp.gain.setValueAtTime(amp.gain.value, now);
        //amp.gain.linearRampToValueAtTime(0.5, audioContext.currentTime + 0.1);

        //writeMessageToID( "soundStatus", "<p>Play tone at frequency = " + frequency  + "</p>");

        return now + 1;
    }

    function stopTone(atTime) {
        var now = audioContext.currentTime;
        if (typeof atTime !== "undefined") {
            now = atTime;
        }
        oscillator.onended = function () {
            audioOut = false;
            clearInterval(audioAnimate);
            menubar.find('.menuGenerateMorse').find('i.fa').removeClass('fa-volume-up').removeClass('fa-volume-down').removeClass('fa-volume-off').addClass('fa-tty');
        };
        amp.gain.cancelScheduledValues(now);
        amp.gain.setValueAtTime(amp.gain.value, now);
        amp.gain.linearRampToValueAtTime(0.0, audioContext.currentTime + 1.0);
        //writeMessageToID( "soundStatus", "<p>Stop tone.</p>");
        oscillator.stop(now);

        return 0;
    }

    function convertLetterToMorseData(letter) {
        var dash = cDash;
        var dot = cDot;
        var letters = {
            "a": function () {
                return [dot, dash];
            },
            "b": function () {
                return [dash, dot, dot, dot];
            },
            "c": function () {
                return [dash, dot, dash, dot];
            },
            "d": function () {
                return [dash, dot, dot];
            },
            "e": function () {
                return [dot];
            },
            "f": function () {
                return [dot, dot, dash, dot];
            },
            "g": function () {
                return [dash, dash, dot];
            },
            "h": function () {
                return [dot, dot, dot, dot];
            },
            "i": function () {
                return [dot, dot];
            },
            "j": function () {
                return [dot, dash, dash];
            },
            "k": function () {
                return [dash, dot, dash];
            },
            "l": function () {
                return [dot, dash, dot, dot];
            },
            "m": function () {
                return [dash, dash];
            },
            "n": function () {
                return [dash, dot];
            },
            "o": function () {
                return [dash, dash, dash];
            },
            "p": function () {
                return [dot, dash, dash, dot];
            },
            "q": function () {
                return [dash, dash, dot, dash];
            },
            "r": function () {
                return [dot, dash, dot];
            },
            "s": function () {
                return [dot, dot, dot];
            },
            "t": function () {
                return [dash];
            },
            "u": function () {
                return [dot, dot, dash];
            },
            "v": function () {
                return [dot, dot, dot, dash];
            },
            "w": function () {
                return [dot, dash, dash];
            },
            "x": function () {
                return [dash, dot, dot, dash];
            },
            "y": function () {
                return [dash, dot, dash, dash];
            },
            "z": function () {
                return [dash, dash, dot, dot];
            },
            "0": function () {
                return [dash, dash, dash, dash, dash];
            },
            "1": function () {
                return [dot, dash, dash, dash, dash];
            },
            "2": function () {
                return [dot, dot, dash, dash, dash];
            },
            "3": function () {
                return [dot, dot, dot, dash, dash];
            },
            "4": function () {
                return [dot, dot, dot, dot, dash];
            },
            "5": function () {
                return [dot, dot, dot, dot, dot];
            },
            "6": function () {
                return [dot, dot, dot, dash, dash];
            },
            "7": function () {
                return [dash, dash, dot, dot, dot];
            },
            "8": function () {
                return [dash, dash, dash, dot, dot];
            },
            "9": function () {
                return [dash, dash, dash, dash, dot];
            },
            ".": function () {
                return [dot, dash, dot, dash, dot, dash];
            },
            "?": function () {
                return [dot, dot, dash, dash, dot, dot];
            }
        };
        console.log("Letter: [" + letter + "] values: " + letters[letter]());
        return letters[letter]();
    }

    function outputLetter(letter, nextStep) {

        letter.forEach(function (value) {
            amp.gain.setValueAtTime(0.5, nextStep);
            nextStep += value;
            amp.gain.setValueAtTime(0, nextStep);
            nextStep += cDit;
        });

        return nextStep;
    }

    //Takes an array of objects and builds the node type menu from it
    function buildNodeTypeMenu(menuArray) {
        nodeTypeSelector.empty();

        menuArray.map(function (item) {
            nodeTypeSelector.append('<li><a href="#" class="menuTypeSelection ' + item.class + '" title="' + item.title + '" data-type="' + item.type + '">' + item.text + '</a></li>');
        });
    }
});