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
    var menubar = $('#menubarEditor');
    var elTitle = $('.divOutlineTitle input.title');


    //New button
    menubar.find('.menuNew').click(function () {
        window.location = "/editor";
    });

    //Save buttons
    menubar.find('.menuSaveAs').click(function () {
        bootbox.prompt("What file name do you want to use?", function (result) {
            if ( result !== null ) {
                //Grab the current title
                title = elTitle.val();

                //Set a title
                opSetTitle(title);
                lasttitle = title;

                //Get the filenames sorted
                //oldfilename = filename;
                filename = result.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';

                //Save the file
                saveFile(title, filename, mode, redirect, includeDisqus, wysiwygOn, opOutlineToXml(ownerName, ownerEmail));
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
        if ( filename == "" ) {
            if ( title != "" ) {
                filename = title.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';
            } else {
                filename = bufilename;
            }
        }

        //Set a title
        opSetTitle(title);
        lasttitle = title;

        //Save the file
        saveFile(title, filename, mode, redirect, includeDisqus, wysiwygOn, opOutlineToXml(ownerName, ownerEmail));
        $('#dropdownSave').dropdown('hide');
        return false;
    });

    //Publish button
    menubar.find('.menuPublish').click(function () {
        if ( isEmpty(url) ) {
            showMessage("You must save the document first.", false, 3);
            return(false);
        }
        $('#divEditOutline .ouitem').remove();
        $('#divEditOutline').append('<div class="ouitem hide"><div class="header"><a class="articlelink" href="' + htmlurl + '">' + title + '</a></div></div>');
        $('#divEditOutline .ouitem').append('<div class="footer"><span class="source"><a class="articlelink" href=""></a></span></div>');
        $('#divEditOutline .ouitem .footer').append('<span class="origin">' + url + '</span>');
        newMicroblogPostWindow('#divEditOutline .ouitem');
        return(false);
    });

    //Open button
    menubar.find('.menuOpen').click(function () {

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
                    $('.recentfilesopen').append('<li><a href="/editor?url=' + item.url + '">' + newtitle + '</a> ' + prettyDate(item.time * 1000).toLowerCase() + '.</li>');
                });

                //Open the dropdown sheet
                sheetopen.toggleClass('open');
            }
        });

        return false;
    });

    //Redirect button
    menubar.find('.menuRedirect').click(function () {
        bootbox.prompt("What url should redirect to this document?", function (result) {
            if ( result !== null ) {
                //Store the xml data
                var opml = opOutlineToXml();
                redirect = result;
                updateOutlineInfo('', '', result);
            }
        });

        //Put an existing value in the input box
        setTimeout(function () {
            $('input.bootbox-input').val(redirect);
        }, 500);
    });

    //Close sheet button
    $('div.sheet a.sheetclose').click(function () {
        $('div.sheet').removeClass('open');
    });

    //Open file by url button
    sheetopen.find('a.openbyurl').click(function () {
        bootbox.prompt("What url to open?", function (result) {
            if ( result !== null ) {
                window.location = "/editor?url=" + result;
            }
        });
    });

    //Import by url button
    sheetimport.find('a.openbyurl').click(function () {
        bootbox.prompt("Url of outline to import?", function (geturl) {
            if ( geturl !== null ) {
                //Set the node's attributes
                opSetOneAtt('type', 'import');
                opSetOneAtt('url', geturl);
                //Now call out and get the outline data
                if ( !opHasSubs() ) {
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
            if ( geturl !== null ) {
                //Set the node's attributes
                opSetOneAtt('type', 'include');
                opSetOneAtt('url', geturl);
                //Now call out and get the outline data
                if ( !opHasSubs() ) {
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
    menubar.find('.menuType .menuTypeSelection').click(function () {
        var thistype = $(this).attr('data-type');

        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + $(this).html() + ') <b class="caret"></b>');
        if ( thistype == 'link' || thistype == 'redirect' ) {
            bootbox.prompt("What url to point to?", function (result) {
                if ( result !== null ) {
                    opSetOneAtt('type', thistype);
                    opSetOneAtt('url', result);
                }
            });
        } else if ( thistype == 'include' ) {
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

        //Collaboration node
        if( thistype == 'collaborate' ) {
            opSetLineText("[Collaboration node - do not delete.]");
        }

        return true;
    });

    //Toggle render mode
    chkToggleRender.click(function () {
        if ( $(this).parent().hasClass('active') ) {
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
        if ( $(this).parent().hasClass('active') ) {
            $(this).parent().removeClass('active');
            includeDisqus = false;
        } else {
            $(this).parent().addClass('active');
            includeDisqus = true;
        }
    });

    //Toolbox buttons
    menubar.find('.menuAddLink').click(function () {
        editorToolAddLink();
        return false;
    });
    menubar.find('.menuSearchReplace').click(function () {
        srmodal.modal('show');
    });
    menubar.find('.menuImportOutline').click(function () {
        console.log("test");
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
    menubar.find('.menuInsertFiles').click(function() {
       showEditorFileDropZone();
       $('#uploadifive-editor_upload > input[type=file]:last-child').trigger('click');
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
            if ( ltext.indexOf(search) != -1 ) {
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
            if ( !$('.divOutlineTitle input.title').is(':focus') ) {
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
            "opKeystroke": opKeystrokeCallback
        },
        "prefs": {
            "outlineFont": "Calibri",
            "outlineFontSize": 18,
            "outlineLineHeight": 24,
            "renderMode": chkToggleRender.parent().hasClass('active'),
            "readonly": false,
            "typeIcons": appTypeIcons
        },
    });

    //Load the outline content
    if ( !isBlank(url) && mode != "article" ) {
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
                if ( title == "Untitled" ) {
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
                if ( includeDisqus ) {
                    chkDisqusInclude.parent().addClass('active');
                }
                if ( wysiwygOn ) {
                    outliner.concord().op.setRenderMode(true);
                    chkToggleRender.parent().addClass('active');
                }
                loading.hide();
            }
        });
    } else {
        opXmlToOutline(initialOpmlText);
        title = opGetTitle();
        elTitle.val(title);
        //Set the title of the html document
        document.title = title + " - FC";

    }
    title = opGetTitle();


    //Refresh the outliner info pane
    updateOutlineInfo(url, "", redirect);
    if ( badurl == true ) {
        showMessage('Parse error. Please check the url.', false, 5);
    }

    //Hot keys
    $(window).bind('keydown keyup keypress', function (event) {
        if ( event.ctrlKey || event.metaKey ) {
            switch ( String.fromCharCode(event.which).toLowerCase() ) {
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
    key('ctrl+l,command+l', function () {
        menubar.find('.menuAddLink').trigger('click');
        return false;
    });
    key('ctrl+f,command+f', function () {
        showEditorFileDropZone();
        $('#uploadifive-editor_upload > input[type=file]:last-child').trigger('click');
        return false;
    });
    key('ctrl+left,command+left', function () {
        if ( !opInTextMode() ) {
            opFirstSummit();
            window.scrollTo(0, 0);
        } else {
            var box = $('#outliner').find('.concord-cursor .concord-wrapper .concord-text')[0];
            moveCursorToStart(box);
        }
        return false;
    });
    key('ctrl+right,command+right', function () {
        if ( !opInTextMode() ) {
            opGo(left, 32767);
            opGo(down, 32767);
            window.scrollTo(0, 999999);
            while ( opHasSubs() && opSubsExpanded() ) {
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
        if ( !opInTextMode() ) {
            opSetTextMode(true);
        } else {
            opSetTextMode(false);
        }
    });

    //Position the drop zone
    $('#divEditorEnclosures').offset({top:0,left:0}).offset( $('#divEditOutline').offset() );

    //Save a file
    function saveFile(ftitle, fname, fmode, fredirect, fdisqus, fwysiwyg, fopml, foldname) {
        var _foldname = (typeof foldname === "undefined") ? "" : foldname;
        var menubar = $('#menubarEditor');

        //Render the title and byline?
        var rendertitle = true;
        if ( !$('.rendertitle').is(':checked') ) {
            rendertitle = false;
        }

        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/in/save.opml',
            data: {
                "opml": fopml,
                "mode": fmode,
                "oldfilename": _foldname,
                "filename": fname,
                "redirect": fredirect,
                "disqus": fdisqus,
                "wysiwyg": fwysiwyg,
                "title": ftitle,
                "rendertitle": rendertitle
            },
            dataType: "json",
            beforeSend: function () {
                //Disable the save button and show a spinner
                menubar.find('.saves').attr('disabled', true);
                menubar.find('#dropdownSave').html('<i class="icon-spinner"></i> Saving...');
            },
            success: function (data) {
                //Show returned info and re-enable the save button
                url = data.url;
                htmlurl = data.html;
                updateOutlineInfo(url, data.html, redirect);

                showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                menubar.find('#dropdownSave').html('Save');
                menubar.find('.saves').attr('disabled', false);

                //Set the title of the html document
                document.title = ftitle + " - FC";
            }
        });

        return true;
    }

    //Linkify some text in the outline
    function editorToolAddLink() {
        var outliner = $('#outliner');
        var urltolink = "";
        var seltxt = "";

        //Check if the user is in text mode.  If not, we need to turn the whole node
        //into a 'link' node type
        if ( !opInTextMode() ) {
            seltxt = opGetLineText();
            if( /^http[s]?\:\/\//.test(seltxt) ) {
                console.log(seltxt);
                urltolink = seltxt;
            } else {
                bootbox.prompt("Type the target link.", function (result) {
                    if ( result !== null ) {
                        console.log(result);
                        urltolink = result;
                        if( urltolink != "" && /^http[s]?\:\/\//.test(urltolink) ) {
                            opSetOneAtt('type','link');
                            opSetOneAtt('url', urltolink);
                        }
                    }
                });
            }
            if( urltolink != "" && /^http[s]?\:\/\//.test(urltolink) ) {
                opSetOneAtt('type','link');
                opSetOneAtt('url', urltolink);
            }
        } else {
            seltxt = getSelected();
            seltxt = new String(seltxt);
            if( /^http[s]?\:\/\//.test(seltxt) ) {
                console.log(seltxt);
                urltolink = seltxt;
            } else {
                bootbox.prompt("Type the target link.", function (result) {
                    if ( result !== null ) {
                        console.log(result);
                        urltolink = result;
                        if( urltolink != "" && /^http[s]?\:\/\//.test(urltolink) ) {
                            outliner.concord().op.link(urltolink);
                        }
                    }
                });
            }
            if( urltolink != "" && /^http[s]?\:\/\//.test(urltolink) ) {
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
                    if ( op.attributes.getOne('type') == "replace" ) {
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
                                if ( op.getLineText() == "[##ORIGINAL##]" ) {
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
        if ( typeof(rootnodetype) == "undefined" ) {
            rootnodetype = "not set";
        }
        $('#menubarEditor').find('.menuType > a.dropdown-toggle').html('Type (' + rootnodetype + ') <b class="caret"></b>');
        return true;
    }

    //Display outline info
    function updateOutlineInfo(url, html, redirect) {
        var elOutlineinfo = $('#menubarEditor').find('.outlineinfo');

        elOutlineinfo.html('');
        if ( url != "" ) {
            elOutlineinfo.html('<li><a target="_blank" title="Link to opml source of this outline." href="' + url + '">OPML</a></li>');
            if ( html == "" ) {
                htmlurl = url.replace("/opml/", "/html/");
                htmlurl = htmlurl.substr(0, htmlurl.lastIndexOf(".")) + ".html";
            }
            elOutlineinfo.append('<li><a target="_blank" title="Link to rendered html version of this outline." href="' + htmlurl + '">HTML</a></li>');
        }

        if ( redirect != "" ) {
            $('#menubarEditor').find('.menuRedirect').html("Redirect: " + redirect);
            $('#menubarEditor').find('.menuRedirect').parent().addClass('active');
        }
        return true;
    }

    //When a new node is clicked on, this callback fires
    function opCursorMovedCallback(op) {
        var nodetype = op.attributes.getOne('type');

        if ( typeof(nodetype) == "undefined" ) {
            nodetype = "not set";
        }
        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + nodetype + ') <b class="caret"></b>');

        //Add a link-out option for include nodes
        menubar.find('.menubar li.extLink').remove();
        menubar.find('.menubar li.extEditLink').remove();
        if ( nodetype == "include" || nodetype == "import" ) {
            menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('url') + '"><i class="icon-external-link" style="color:#090;"></i></a></li>');
            menubar.find('.menubar').append('<li class="extEditLink"><a target="_blank" href="/editor?url=' + op.attributes.getOne('url') + '"><i class="icon-edit-sign" style="color:#090;"></i></a></li>');
            return true;
        }
        if ( nodetype == "link" ) {
            menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('url') + '"><i class="icon-external-link" style="color:#090;"></i></a></li>');
            return true;
        }
        if ( nodetype == "rss" ) {
            menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('xmlUrl') + '"><i class="icon-external-link" style="color:#090;"></i></a></li>');
            return true;
        }


        return true;
    }

    //Handle expansion requests for nodes
    function opExpandCallback(op) {
        var nodetype = op.attributes.getOne('type');
        if ( nodetype == "link" || nodetype == "redirect" ) {
            gotourl = op.attributes.getOne('url');
            window.open(gotourl);
        } else if ( nodetype == "import" || nodetype == "include" ) {
            var geturl = op.attributes.getOne('url');
            //Now call out and get the outline data only if the inclusion node doesn't have any children
            if ( (nodetype == "import" && !opHasSubs()) || nodetype == "include" ) {
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

    //Handle collapse requests for nodes
    function opCollapseCallback(op) {
        var nodetype = op.attributes.getOne('type');
        //If we collapse an include node, delete it's sub-nodes
        if ( nodetype == "include" ) {
            op.deleteSubs();
        }
        return true;
    }

    //Handle some special key stroke stuff
    function opKeystrokeCallback(event) {
        //SHIFT+Enter should create a new blank link above the current line
        if ( event.which == 13 && event.shiftKey ) {
            opInsert("", 'up');
            opSetTextMode(false);
            outliner.op.deleteLine();
            return false;
        }
        //Stop concord from responding to these key combos
        if ( event.ctrlKey || event.metaKey ) {
            switch ( String.fromCharCode(event.which).toLowerCase() ) {
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
        if ( document.createRange )//Firefox, Chrome, Opera, Safari, IE 9+
        {
            range = document.createRange();//Create a range (a range is a like the selection but invisible)
            range.selectNodeContents(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(true);//collapse the range to the end point. false means collapse to end rather than the start
            selection = window.getSelection();//get the selection object (allows you to change selection)
            selection.removeAllRanges();//remove any selections already made
            selection.addRange(range);//make the range you have just created the visible selection
        }
        else if ( document.selection )//IE 8 and lower
        {
            range = document.body.createTextRange();//Create a range (a range is a like the selection but invisible)
            range.moveToElementText(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
            range.select();//Select the range (make it the visible selection
        }
    }
    function moveCursorToEnd(contentEditableElement) {
        var range, selection;
        if ( document.createRange )//Firefox, Chrome, Opera, Safari, IE 9+
        {
            range = document.createRange();//Create a range (a range is a like the selection but invisible)
            range.selectNodeContents(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
            selection = window.getSelection();//get the selection object (allows you to change selection)
            selection.removeAllRanges();//remove any selections already made
            selection.addRange(range);//make the range you have just created the visible selection
        }
        else if ( document.selection )//IE 8 and lower
        {
            range = document.body.createTextRange();//Create a range (a range is a like the selection but invisible)
            range.moveToElementText(contentEditableElement);//Select the entire contents of the element with the range
            range.collapse(false);//collapse the range to the end point. false means collapse to end rather than the start
            range.select();//Select the range (make it the visible selection
        }
    }

    //Uploadifive handler
    if ( gDevice != "android" ) {
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
                    $("input.postenclosure[data-filename='" + file.name + "'").remove();
                },
                'onQueueComplete': function () {
                        hideEditorFileDropZone();
                        $('#editor_upload').uploadifive('clearQueue');
                },
                'onUploadComplete': function (file, data) {
                    var jdata = $.parseJSON(data);
                    $('.complete .filename:contains("' + file.name + '")').each(function (index) {
                            if ( isImage(jdata.url) ) {
                                opInsertImage(jdata.url);
                            } else {
                                opInsert('<a href="' + jdata.url + '">' + file.name + '</a>', down);
                                opSetOneAtt('type', 'link');
                                opSetOneAtt('url', jdata.url);
                            }
                    });
                }
            })
        });
    }

    //Show file drop zone
    function showEditorFileDropZone() {
        $('#divEditorEnclosures').width( $('#divEditOutline').width() - 8 );
        $('#divEditorEnclosures').height( $('#divEditOutline').height() + 8 );
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
            if ( !opInTextMode() ) {
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
});