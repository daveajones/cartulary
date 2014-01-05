$(document).ready(function () {
    var hoverTimer = null;
    var outliner = $('#outliner');
    var sheetopen = $('#divEditSheetOpen');
    var sheettemplate = $('#divEditSheetTemplate');
    var chkToggleRender = $('.rendertoggle');
    var chkDisqusInclude = $('.menuDisqusToggle');
    var includeDisqus = false;
    var menubar = $('#menubarEditor');
    var elTitle = $('.divOutlineTitle input.title');


    //New button
    menubar.find('.menuNew').click(function() {
        window.location = "/editor";
    });

    //Save buttons
    menubar.find('.menuSaveAs').click(function () {
        bootbox.prompt("What file name do you want to use?", function(result) {
            if (result !== null) {
                filename = result.replace(/\W/g, '').substring(0, 20) + '-' + Math.round((new Date()).getTime() / 1000) + '.opml';
                menubar.find('.menuSave').trigger('click');
            }
        });
    });
    menubar.find('.menuSave').click(function () {
        //Grab the current title
        title = elTitle.val();

        //Get a file name
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

        //Store the xml data
        var opml = opOutlineToXml();

        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/in/save.opml',
            data: {
                "opml": opml,
                "mode" : mode,
                "filename": filename,
                "redirect" : redirect,
                "disqus" : includeDisqus,
                "title": title
            },
            dataType: "json",
            beforeSend: function () {
                //Disable the save button and show a spinner
                menubar.find('.menuSave').attr('disabled', true);
                menubar.find('.menuSave').html('<i class="icon-spinner"></i> Saving...');
            },
            success: function (data) {
                //Show returned info and re-enable the save button
                url = data.url;
                htmlurl = data.html;
                updateOutlineInfo(url, data.html, redirect);

                showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                menubar.find('.menuSave').html('Save');
                menubar.find('.menuSave').attr('disabled', false);
            }
        });

        return false;
    });

    //Publish button
    menubar.find('.menuPublish').click(function () {
       if( isEmpty(url) ) {
           showMessage("You must save the document first.", false, 3);
           return(false);
       }
       $('#divEditOutline .ouitem').remove();
       $('#divEditOutline').append('<div class="ouitem hide"><div class="header"><a class="articlelink" href="' + htmlurl + '">' + title + '</a></div></div>');
       $('#divEditOutline .ouitem').append('<div class="footer"><span class="source"><a class="articlelink" href=""></a></span></div>');
       $('#divEditOutline .ouitem .footer').append('<span class="origin">' + url + '</span>');
       newMicroblogPostWindow( '#divEditOutline .ouitem' );
       return(false);
    });

    //Open button
    menubar.find('.menuOpen').click(function() {

        //Make the ajax call
        $.ajax({
            type: 'POST',
            url: '/cgi/out/get.recentfiles',
            dataType: "json",
            success: function (data) {
                //Clear the table for new data
                $('.recentfilesopen').empty();

                //Iterate
                $.each(data.files, function(i, item) {
                    var re = /\.$/;
                    var newtitle = item.title.replace(re, "").toLowerCase();
                    $('.recentfilesopen').append('<li><a href="/editor?url='+ item.url +'">' + newtitle + '</a> ' + prettyDate(item.time * 1000).toLowerCase() + '.</li>');
                });

                //Open the dropdown sheet
                sheetopen.toggleClass('open');
            }
        });

        return false;
    });

    //Template button
    menubar.find('.menuTemplate').click(function() {
        //Make the ajax call to get the recent file list
        $.ajax({
            type: 'POST',
            url: '/cgi/out/get.recentfiles',
            dataType: "json",
            success: function (data) {
                //Clear the table for new data
                $('.templateopen').empty();

                //Iterate
                $.each(data.files, function(i, item) {
                    var re = /\.$/;
                    var newtitle = item.title.replace(re, "").toLowerCase();
                    //Add an entry for each url returned
                    $('.templateopen').append('<li><a href="#" data-url="'+ item.url +'">' + newtitle + '</a> ' + prettyDate(item.time * 1000).toLowerCase() + '.</li>');
                });

                //Link apply function to each file link
                $('.templateopen li a').click(function() {
                    var geturl = $(this).data('url');
                    sheettemplate.removeClass('open');
                    applyTemplate(geturl);
                    return false;
                });

                //Open the dropdown sheet
                sheettemplate.toggleClass('open');
            }
        });

        return false;
    });

    //Redirect button
    menubar.find('.menuRedirect').click(function() {
        bootbox.prompt("What url should redirect to this document?", function(result) {
            if (result !== null) {
                //Store the xml data
                var opml = opOutlineToXml();
                redirect = result;

                //Make the ajax call
                $.ajax({
                    type: 'POST',
                    url: '/cgi/in/save.opml',
                    data: {
                        "opml": opml,
                        "mode" : mode,
                        "filename": filename,
                        "disqus" : includeDisqus,
                        "redirect" : redirect,
                        "title": title
                    },
                    dataType: "json",
                    beforeSend: function () {
                        //Disable the save button and show a spinner
                        menubar.find('.menuSave').attr('disabled', true);
                        menubar.find('.menuSave').html('<i class="icon-spinner"></i> Saving...');
                    },
                    success: function (data) {
                        //Show returned info and re-enable the save button
                        url = data.url;
                        htmlurl = data.html;
                        updateOutlineInfo(url, data.html, result);

                        showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                        menubar.find('.menuSave').html('Save');
                        menubar.find('.menuSave').attr('disabled', false);
                    }
                });
            }
        });

        //Put an existing value in the input box
        setTimeout(function() {
            $('input.bootbox-input').val(redirect);
        }, 500);
    });

    //Close sheet button
    $('div.sheet a.sheetclose').click( function() {
        $('div.sheet').removeClass('open');
    });

    //Open file by url button
    sheetopen.find('a.openbyurl').click( function() {
         bootbox.prompt("What url to open?", function(result) {
            if (result !== null) {
                window.location = "/editor?url=" + result;
            }
         });
    });

    //Open template by url button
    sheettemplate.find('a.openbyurl').click( function() {
        bootbox.prompt("Url of the template to use?", function(result) {
            if (result !== null) {
                applyTemplate(result);
            }
        });
    });

    //Type dropdown button
    menubar.find('.menuType .menuTypeSelection').click(function() {
        var thistype = $(this).attr('data-type');

        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + $(this).html() + ') <b class="caret"></b>');
        if( thistype == 'link' || thistype == 'redirect' ) {
            bootbox.prompt("What url to point to?", function(result) {
                if (result !== null) {
                    opSetOneAtt('type', thistype);
                    opSetOneAtt('url', result);
                }
            });
        } else {
            opSetOneAtt('type', thistype);
        }
        return true;
    });

    //Toggle render mode
    chkToggleRender.click(function () {
        if ( $(this).parent().hasClass('active') ) {
            console.log('T: ' + outliner.concord().op.setRenderMode(false));
            $(this).parent().removeClass('active');
        } else {
            console.log('F: ' + outliner.concord().op.setRenderMode(true));
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
    menubar.find('.menuAddLink').click( function() {
       editorToolAddLink();
       return false;
    });

    //Handle opacity on focus change
    elTitle.on("focus", function() {
        clearTimeout(hoverTimer);
        $('.divOutlineTitle').removeClass('dim');
    });
    elTitle.on("blur", function() {
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(function() {
            $('.divOutlineTitle').addClass('dim');
        }, 3000);
    });

    //Full opacity on title hover
    $('.divOutlineTitle').hover(
        function() {
            clearTimeout(hoverTimer);
            $('.divOutlineTitle').removeClass('dim');
        },
        function() {
            if( !$('.divOutlineTitle input.title').is(':focus') ) {
                hoverTimer = setTimeout(function() {
                    $('.divOutlineTitle').addClass('dim');
                }, 3000);
            }
        }
    );

    //Load up the outline
    outliner.concord({
        "callbacks": {
            "opCursorMoved": opCursorMovedCallback,
            "opExpand": opExpandCallback
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
    opXmlToOutline(initialOpmlText);
    title = opGetTitle();


    //Refresh the outliner info pane
    updateOutlineInfo(url, "", redirect);
    if( badurl == true ) {
        showMessage('Parse error. Please check the url.', false, 5);
    }

    //Set up the root node type correctly
    getRootNodeType();

    //Dim the title area when not in use
    hoverTimer = setTimeout(function() {
        $('.divOutlineTitle').addClass('dim');
    }, 7000);


    //Set a title
    if( title == "Untitled") {
        elTitle.val('');
    } else {
        elTitle.val(title);
    }


    //Hot keys
    key('ctrl+y', function() {
        editorToolAddLink();
    })


});


//Linkify some text in the outline
function editorToolAddLink() {
    var outliner = $('#outliner');
    bootbox.prompt("Type the target link.", function(result) {
        if (result !== null) {
            console.log(result);
            outliner.concord().op.link(result);
        }
    });
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
            opVisitAll( function(op) {
                if( op.attributes.getOne('type') == "replace" ) {
                    var cursor = op.getCursor();

                    //Remove replace type
                    op.attributes.removeOne('type');

                    //Put the original xml back in
                    op.insertXml(original, "up");
                    op.setCursor(cursor);
                    op.deleteLine();

                    (function() {
                        opFirstSummit();
                        opVisitAll( function(op) {
                            if( op.getLineText() == "[##ORIGINAL##]" ) {
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
                    }) ();

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
    if( typeof(rootnodetype) == "undefined" )  {  rootnodetype = "not set";  }
    $('#menubarEditor').find('.menuType > a.dropdown-toggle').html('Type (' + rootnodetype + ') <b class="caret"></b>');
    return true;
}

//Display outline info
function updateOutlineInfo(url, html, redirect) {
    var elOutlineinfo = $('#menubarEditor').find('.outlineinfo');

    elOutlineinfo.html('');
    if (url != "") {
        elOutlineinfo.html('<li><a target="_blank" href="' + url + '">OPML</a></li>');
        if( html != "") {
            elOutlineinfo.append('<li><a target="_blank" href="' + html + '">HTML</a></li>');
        }
    }

    if ( redirect != "" ) {
        $('#menubarEditor').find('.menuRedirect').html("Redirect: " + redirect);
        $('#menubarEditor').find('.menuRedirect').parent().addClass('active');
    }
    return true;
}

//When a new node is clicked on, this callback fires
function opCursorMovedCallback (op) {
    var nodetype = op.attributes.getOne('type');

    if( typeof(nodetype) == "undefined" )  {  nodetype = "not set";  }
    $('#menubarEditor').find('.menuType > a.dropdown-toggle').html('Type (' + nodetype + ') <b class="caret"></b>');

    return true;

}

//Handle expansion requests for nodes
function opExpandCallback (op) {
    var nodetype = op.attributes.getOne('type');
    if(nodetype == "link" || nodetype == "redirect") {
        gotourl = op.attributes.getOne('url');
        window.open(gotourl);
    }
    return true;
}