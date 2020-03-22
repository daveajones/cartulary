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
    var chkTogglePrivate = $('.menuPrivateToggle');
    var menubar = $('#menubarEditor');
    var nodeTypeSelector = menubar.find('.menuType ul.dropdown-menu');
    var nodeVersionHistory = menubar.find('.menuVersionHistory ul.dropdown-menu');
    var elTitle = $('.divOutlineTitle input.title');
    var oscillator;
    var amp;
    var nextStep = 0;
    var speed = 0.9;
    var dit = 60;
    var audioOut = false;
    var audioAnimate;
    var fileMultiDrop = false;
    var uploaddatestamp = new Date().valueOf() / 1000;
    var filterArticleImport = 'menuArticleRangeSinceLastImport';


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
        {class: "menuTypePubDate", title: "The pubDate of the feed or item.", type: "pubdate", text: "pubDate"}
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
        title = elTitle.val();

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

                //Get a new filename
                filename = getFileName(result);

                //This is a new file so clear the token to make the cgi gen a new one
                privtoken = "";

                //Save the file
                saveFile(
                    title,
                    filename,
                    type,
                    redirect,
                    includeDisqus,
                    wysiwygOn,
                    watchedOutline,
                    lockedOutline,
                    privateOutline,
                    privtoken,
                    opOutlineToXml(ownerName, ownerEmail),
                    undefined,
                    undefined,
                    true,
                    templatename
                );
            }
        });

        //Put an existing value in the input box
        console.log(title);
        if (title.toLowerCase() != "untitled" && title != "") {
            setTimeout(function () {
                $('input.bootbox-input').val(title);
            }, 500);
        }

        $('#dropdownSave').dropdown('hide');
        return false;
    });
    menubar.find('.menuSave').click(function () {
        //Grab the current title
        title = elTitle.val();

        //Set a title
        opSetTitle(title);

        //Check for conditions that would force a "save as..."
        if ((lockedOutline && wasLocked) || title.toLowerCase() == "untitled" || title == "") {
            menubar.find('.menuSaveAs').trigger('click');
            $('#dropdownSave').dropdown('hide');
            return false;
        }

        //Check for conditions that would trigger a "save as template..."
        if (type === 6 && (templatename === "undefined" || isEmpty(templatename))) {
            menubar.find('.menuSaveAsTemplate').trigger('click');
            $('#dropdownSave').dropdown('hide');
            return false;
        }


        //If the title of the outline changed prompt if the user wants to save
        //this as a new file or not
        if (title != lasttitle) {
            bootbox.dialog({
                message: "You changed the title of this outline. Do you want to save it as a new file or overwrite the current file?",
                buttons: {
                    confirm: {
                        label: 'Save as New',
                        callback: function () {
                            console.log("confirm callback");
                            //Check for conditions that would trigger a "save as template..."
                            if (type === 6) {
                                setTimeout(function () {
                                    menubar.find('.menuSaveAsTemplate').trigger('click');
                                    $('#dropdownSave').dropdown('hide');
                                }, 500);
                            } else {
                                //Get a file name
                                oldfilename = "";
                                filename = getFileName(title);
                                privtoken = "";
                                saveFile(title, filename, type, redirect, includeDisqus, wysiwygOn, watchedOutline, lockedOutline, privateOutline, privtoken, opOutlineToXml(ownerName, ownerEmail), undefined, undefined, false, templatename);
                                lasttitle = title;
                            }
                        }
                    },
                    cancel: {
                        label: 'Overwrite',
                        callback: function () {
                            console.log("cancel callback");
                            saveFile(title, filename, type, redirect, includeDisqus, wysiwygOn, watchedOutline, lockedOutline, privateOutline, privtoken, opOutlineToXml(ownerName, ownerEmail), undefined, undefined, false, templatename);
                            lasttitle = title;
                        }
                    }
                }
            });
        } else {
            if (filename == "") {
                filename = getFileName(title);
                oldfilename = filename;
            }
            saveFile(title, filename, type, redirect, includeDisqus, wysiwygOn, watchedOutline, lockedOutline, privateOutline, privtoken, opOutlineToXml(ownerName, ownerEmail), undefined, undefined, false, templatename);
        }

        $('#dropdownSave').dropdown('hide');

        return false;
    });
    menubar.find('.menuSaveAsTemplate').click(function () {
        title = elTitle.val();

        //Is there an existing template name?
        if (isEmpty(templatename)) {
            templatename = title;
        }

        if (lockedOutline) {
            bbCaption = "This outline is locked. Type a new file name.";
        } else {
            bbCaption = "What do you want to name this template?";
        }
        bootbox.prompt(bbCaption, function (result) {
            if (result !== null) {
                //Grab the current title
                title = elTitle.val();

                //Set a title
                opSetTitle(title);
                lasttitle = title;

                //Get a new filename
                filename = getFileName(result);

                if (!isEmpty(result)) {
                    templatename = result;
                }

                //This is a new file so clear the token to make the cgi gen a new one
                privtoken = "";

                //Set the type to template
                type = 6;

                //Save the file
                saveFile(
                    title,
                    filename,
                    type,
                    redirect,
                    includeDisqus,
                    wysiwygOn,
                    watchedOutline,
                    lockedOutline,
                    privateOutline,
                    privtoken,
                    opOutlineToXml(ownerName, ownerEmail),
                    undefined,
                    undefined,
                    true,
                    templatename
                );
            }
        });

        //Put an existing value in the input box
        console.log("Templatename: " + templatename);
        if (templatename.toLowerCase() != "untitled" && templatename != "") {
            setTimeout(function () {
                $('input.bootbox-input').val(templatename);
            }, 500);
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
            saveFile(title, filename, type, redirect, includeDisqus, wysiwygOn, watchedOutline, lockedOutline, privateOutline, privtoken, opOutlineToXml(ownerName, ownerEmail), undefined, true);
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

    //Generate button
    menubar.find('.menuGenerate').click(function () {
        if (opHasChanged()) {
            bootbox.alert("You have unsaved template changes. Please save first before generating.");
            return false;
        }

        var variables = getTemplateVariables();
        var varvals = [];
        var ttitle = opGetTitle();

        var dialog = bootbox.dialog({
            title: "Fill in template values...",
            message: "...",
            buttons: {
                confirm: {
                    label: 'Generate',
                    className: 'btn-success',
                    callback: function (result) {
                        $('div.bootbox-body div.generate-dialog input.generateVariableInput').each(function (index, element) {
                            var rawvar = $(this).data('variable');
                            var replacement = $(this).val();

                            //Merge the values from the form into the variables list from the outline
                            variables.list.forEach(function(variable) {
                                if( variable.name == rawvar ) {
                                    variable.replacement = replacement;
                                    if(variable.operator == '++') {
                                        variable.replacement++;
                                    } else
                                    if(variable.operator == '--') {
                                        variable.replacement--;
                                    } else
                                    if(variable.operator == 'jpg' || variable.operator == 'png' || variable.operator == 'gif' || variable.operator == 'bmp') {
                                        variable.replacement = replacement + '_' + variable.data + '.' + variable.operator;
                                    }
                                }
                            });

                            //Keep an array of variable names and their replacement values for sending to the server
                            //to save
                            varvals.push({
                                name: rawvar,
                                value: replacement
                            });

                        });

                        //Make the
                        var xmltochange = opOutlineToXml(ownerName, ownerEmail, ownerId);
                        console.log(variables.list);
                        //Iterate the variable details list and go to each node and do the proper replacement
                        variables.list.forEach(function(variable) {
                            //Set up the regular expression for replacing the var with a real value depending
                            //on the type of operator we're dealing with
                            var regexvar = escapeRegExp('[[$' + variable.name + ']]');
                            if(variable.operator == '++') {
                                regexvar = escapeRegExp('[[$' + variable.name + ']++]');
                            } else
                            if(variable.operator == '--') {
                                regexvar = escapeRegExp('[[$' + variable.name + ']--]');
                            } else
                            if(variable.operator == 'jpg' || variable.operator == 'png' || variable.operator == 'gif' || variable.operator == 'bmp') {
                                regexvar = escapeRegExp('[[$' + variable.name + ']' + variable.operator + variable.data + ']');
                            }

                            //Do the replacement
                            var r = new RegExp(regexvar, 'gm');
                            xmltochange = xmltochange.replace(r, variable.replacement);
                            console.log(regexvar + "|" + variable.replacement);
                        });

                        //console.log(xmltochange);

                        //Replace the outline
                        opXmlToOutline(xmltochange);

                        //Set the new title
                        title = opGetTitle();
                        elTitle.val(title);

                        //Save the new file
                        saveGeneratedFile(opGetTitle(), getFileName(opGetTitle()), 0, "", includeDisqus, wysiwygOn, opOutlineToXml(), varvals, templateid);
                    }
                },
                cancel: {
                    label: 'Cancel'
                }
            }
        });
        dialog.init(function () {
            var bbBody = $('div.bootbox-body');
            bbBody.empty().append('<div class="generate-dialog"></div>');
            var bbContent = bbBody.find('div.generate-dialog');
            var counter = 0;
            variables.content.forEach(function (variable) {
                var oldval = "";
                let obj = templatevariables.find(o => o.name === variable);
                if (typeof obj !== "undefined") {
                    oldval = obj.value;
                }
                bbContent.append('<input class="generateVariableInput" tabindex="'+(counter+1)+'" placeholder="' + variable + '" data-order="'+counter+'" data-variable="' + variable + '" type="text" value="' + oldval + '" title="' + variable + '"/> ' +
                    '<input class="generateVariableUpload" data-variable="' + variable + '" type="file" tabindex="-1" data-order="'+counter+'" > ' +
                    '<div id="generateVariableQueue'+counter+'"></div>');

                //Template generate dialog

                $('input.generateVariableUpload[data-order=' + counter + ']').uploadifive({
                    'auto': true,
                    'method': 'post',
                    'dnd': true,
                    'multi': false,
                    'queueID': 'generateVariableQueue'+counter,
                    'buttonText': '',
                    'uploadScript': '/cgi/in/upload',
                    'simUploadLimit': 1,
                    'removeCompleted': true,
                    'hideButton': true,
                    'height': 30,
                    'width': 30,
                    'buttonClass': 'icon-attach-small',
                    'onAddQueueItem': function (file) {
                        this.data('uploadifive').settings.formData = {
                            'datestamp': (new Date().valueOf() / 1000),
                            'element': this.data('order')
                        };
                        //Look for this variable name and see what sizes we need to generate
                        var sizes = [];
                        var varname = this.data('variable');
                        for( const imgvar of variables.list ) {
                            if( imgvar.name == varname ) {
                                sizes.push({ type: imgvar.operator, size: imgvar.data });
                            }
                        }
                        if(sizes.length > 0) {
                            this.data('uploadifive').settings.formData.sizes = JSON.stringify(sizes);
                        }
                    },
                    'onQueueComplete': function () {
                        //$('#editor_upload').uploadifive('clearQueue');
                        //fileMultiDrop = false;
                    },
                    'onUploadComplete': function (file, data) {
                        var jdata = $.parseJSON(data);
                        $('input.generateVariableInput[data-order=' + jdata.element + ']').val(jdata.url);
                    }
                });

                counter++;
            });
            if (variables.length < 1) {
                bbContent.append('<h2>No variables present.</h2></h1><br>');
            } else {
                setTimeout(function() {
                    $('input.generateVariableInput:first-child').focus();
                }, 1000);
            }
        });
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
                        rfType = ' <i class="fa fa-rss"></i> ';
                    } else if (item.type === 6) {
                        rfType = ' <i class="fa fa-file-code-o"></i> ';
                    }

                    if (item.type === 6) {
                        newtitle = item.templatename.replace(re, "").toLowerCase();
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
            if (isBlank(opGetLineText())) opSetLineText(new Date().toUTCString());
        }


        //Category type node
        if (thistype == 'category') {
            opSetOneAtt('icon', 'tags');
            if (isBlank(opGetLineText())) opSetLineText("Category");
        }

        //Explicit type node
        if (thistype == 'explicit') {
            opSetOneAtt('icon', 'exclamation-triangle');
            opSetLineText("Yes")
        }

        //Keyword type node
        if (thistype == 'keyword') {
            opSetOneAtt('icon', 'info-circle');
            if (isBlank(opGetLineText())) opSetLineText("Keyword");
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

    //Toggle private outline
    chkTogglePrivate.click(function () {
        if ($(this).parent().hasClass('active')) {
            $(this).parent().removeClass('active');
            privateOutline = false;
        } else {
            $(this).parent().addClass('active');
            privateOutline = true;
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
    menubar.find('.menuImportArticles').click(function () {
        var dialog = bootbox.dialog({
            title: "Choose articles to import...",
            message: "<i class=\"fa fa-spinner fa-spin\"></i> Getting article list...",
            buttons: {
                confirm: {
                    label: 'Import',
                    className: 'btn-success',
                    callback: function (result) {
                        var bbForm = $('div.bootbox-body div.import-articles form');
                        //Ajaxify the article list form
                        $('div.bootbox-body div.import-articles form').ajaxForm({
                            dataType: 'json',
                            cache: false,
                            clearForm: true,
                            resetForm: true,
                            timeout: 60000,
                            beforeSubmit: function () {
                                showMessage("<i class='fa fa-spinner fa-spin'></i> Retrieving articles. Please wait...", "warning", 99);
                                loading.show();
                            },
                            success: function (data) {
                                if (data.status == "false") {
                                    showMessage(data.description, data.status, 5);
                                } else {
                                    showMessage("Import Successful!", data.status, 5);
                                    lastArticleImportTime = data.time;
                                    opInsertXml(data.opml, down);
                                    opCut();
                                    loading.hide();
                                }
                            },
                            error: function (x, t, m) {
                                showMessage("Error importing articles.", false, 5);
                                loading.hide();
                            }
                        });
                        bbForm.submit();
                    }
                },
                cancel: {
                    label: 'Cancel'
                }
            }
        });

        //Set up the time range filter and put in the url
        var epoch = Math.round((new Date()).getTime() / 1000) - 604800;
        var epoch1 = Math.round((new Date()).getTime() / 1000) - 604800;
        var epoch2 = Math.round((new Date()).getTime() / 1000) - 86400;
        var epoch3 = Math.round((new Date()).getTime() / 1000) - 3600;
        var listarticlesurl = '/cgi/out/list.articles';
        if(filterArticleImport == 'menuArticleRangeSinceLastImport') {
            if(lastArticleImportTime > 0) {
                epoch = lastArticleImportTime;
                listarticlesurl = '/cgi/out/list.articles?since='+epoch;
            } else {
                epoch = Math.round((new Date()).getTime() / 1000) - 604800;
                listarticlesurl = '/cgi/out/list.articles?since='+epoch;
            }
        }
        if(filterArticleImport == 'menuArticleRangePastWeek') {
            listarticlesurl = '/cgi/out/list.articles?since='+epoch1;
        }
        if(filterArticleImport == 'menuArticleRangePastDay') {
            listarticlesurl = '/cgi/out/list.articles?since='+epoch2;
        }
        if(filterArticleImport == 'menuArticleRangePastHour') {
            listarticlesurl = '/cgi/out/list.articles?since='+epoch3;
        }
        console.log(listarticlesurl);

        //Make the ajax call to get the recent file list
        $.ajax({
            type: 'POST',
            url: listarticlesurl,
            dataType: "json",
            beforeSend: function() {

            },
            success: function (dialog, data) {
                var articles = data.articles;

                dialog.init(function () {
                    var bbBody = $('div.bootbox-body');
                    bbBody.empty().append('<div style="clear:both" class="import-articles"><form name="import-articles" method="POST" action="/cgi/out/get.articles"><ul class="article-list"></ul></form></div>');
                    var bbContent = bbBody.find('div.import-articles');
                    var bbArticleList = bbContent.find('ul');
                    articles.forEach(function (article) {
                        bbArticleList.append('<li><input name="articles[]" type="checkbox" value="'+article.id+'"> '+article.title+'</li><br>');
                    });
                    if (articles.length < 1) {
                        bbContent.append('<h3>No articles found for selected period.</h3></h1><br>');
                    }
                    $('div.bootbox div.modal-header .modal-title').after('<input title="Select All" class="selectall" type="checkbox">');
                    $('div.bootbox div.modal-header .modal-title').css("display", "inline-block");
                    $('div.bootbox div.modal-header input.selectall').css("display", "inline-block").css("margin-top", "-7px").css("margin-left", "20px");
                    //Set up time range filter selectors
                    $('div.bootbox div.modal-footer').prepend('' +
                        '                    <div class="dropup">' +
                        '                        <a id="dropdownArticleRange" role="button" class="dropdown-toggle pull-left btn" data-toggle="dropdown"></a>' +
                        '                        <ul class="dropdown-menu" role="menu">' +
                        '                          <li><a class="menuArticleRangeSinceLastImport" title="'+friendlyDateTime(epoch)+'">Since the last import</a></li>' +
                        '                          <li><a class="menuArticleRangePastWeek" title="'+friendlyDateTime(epoch1)+'">The last 7 days</a></li>' +
                        '                          <li><a class="menuArticleRangePastDay" title="'+friendlyDateTime(epoch2)+'">The last 24 hours</a></li>' +
                        '                          <li><a class="menuArticleRangePastHour" title="'+friendlyDateTime(epoch3)+'">The last hour</a></li>' +
                        '                        </ul>' +
                        '                    </div>');
                    $('div.bootbox div.modal-footer a#dropdownArticleRange').html( $('div.bootbox div.modal-footer a.'+filterArticleImport).html() + "\n" + ' <b style="border-bottom:4px solid white" class="caret"></b>' );
                    $('div.bootbox div.modal-footer a#dropdownArticleRange').attr('title', $('div.bootbox div.modal-footer a.'+filterArticleImport).attr('title'));
                    //Set up a handler for shift-selecting ranges of checkboxes
                    var lastChecked = null;
                    var $chkboxes = bbArticleList.find('input');
                    $chkboxes.click(function (event) {
                        if (!lastChecked) {
                            lastChecked = this;
                            return;
                        }

                        if (event.shiftKey) {
                            var start = $chkboxes.index(this);
                            var end = $chkboxes.index(lastChecked);

                            $chkboxes.slice(Math.min(start, end), Math.max(start, end) + 1).attr('checked', lastChecked.checked);
                        }

                        lastChecked = this;
                    });
                    $(document).on('change', 'div.bootbox div.modal-header input.selectall', function() {
                        $('div.bootbox  ul li input').prop('checked', $(this).prop('checked'));
                        return false;
                    });
                });
            }.bind(window, dialog)
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

        var xmlArchive = outliner.concord().op.cursorToXmlSubsOnly();
        if (!multiarc && hadsubs) {
            opGo(right, 1);
            var xmlArchive = outliner.concord().op.cursorToXmlSubsOnly();
            opGo(left, 1);
        }
        opDeleteSubs();
        opCollapse();

        opSetOneAtt("type", "include");
        opSetOneAtt("url", arcplaceholder);

        //Save the file
        saveArchive(atitle, afilename, type, '', false, false, xmlArchive, arcplaceholder);
    });
    menubar.find('.menuUnarchiveNodes').click(function () {
        //Iterate over every node and check if its an include node
        //if it is, import the outline referenced and remove type=include
        var selnodes = opGetSelectedNodes();

        console.log(selnodes);

        for (var i = 0; i < selnodes.length; i++) {
            var ntype = selnodes[i].attributes.getOne('type');
            var nurl = selnodes[i].attributes.getOne('url');

            if (typeof ntype !== "undefined" && typeof nurl !== "undefined") {
                (function () {
                    var nodeforinsert = selnodes[i];
                    $.ajax({
                        type: 'POST',
                        url: '/cgi/out/get.url.json?url=' + nurl,
                        dataType: "json",
                        beforeSend: function () {
                            nodeforinsert.setCursorContext();
                            loading.show();
                        },
                        success: function (data) {
                            nodeforinsert.deleteSubs();
                            nodeforinsert.insertXml(data.data, right);
                            loading.hide();
                            nodeforinsert.attributes.removeOne('icon');
                            nodeforinsert.attributes.setOne('icon', 'caret-right');
                            nodeforinsert.attributes.removeOne('type');
                            nodeforinsert.attributes.removeOne('url');
                        },
                        error: function () {
                            showMessage("Error loading import url.", false, 5);
                            loading.hide();
                        }
                    });
                })();

            }
        }

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
            }
        });
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
            //console.log(op);
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

    //Article Import filtering functions
    $(document).on('click', 'a.menuArticleRangeSinceLastImport', function () {
        bootbox.hideAll();
        filterArticleImport = 'menuArticleRangeSinceLastImport';
        menubar.find('.menuImportArticles').trigger('click');
    });
    $(document).on('click', 'a.menuArticleRangePastWeek', function () {
        bootbox.hideAll();
        filterArticleImport = 'menuArticleRangePastWeek';
        menubar.find('.menuImportArticles').trigger('click');
    });
    $(document).on('click', 'a.menuArticleRangePastDay', function () {
        bootbox.hideAll();
        filterArticleImport = 'menuArticleRangePastDay';
        menubar.find('.menuImportArticles').trigger('click');
    });
    $(document).on('click', 'a.menuArticleRangePastHour', function () {
        bootbox.hideAll();
        filterArticleImport = 'menuArticleRangePastHour';
        menubar.find('.menuImportArticles').trigger('click');
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
    if (!isBlank(url) && type != 2 && !versionRequest && !privateOutline) {
        if (url.indexOf("ipfs://") == 0) {
            actionurl = '/cgi/out/get.ipfs.json';
        } else {
            actionurl = '/cgi/out/get.url.json';
        }

        $.ajax({
            type: 'POST',
            url: actionurl,
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
                if (data.type == 'json') {
                    console.log("DEBUG: calling opJsonToOutline()");
                    opJsonToOutline(data.data);
                } else {
                    console.log("DEBUG: calling opXmlToOutline()");
                    opXmlToOutline(data.data);
                }
                title = opGetTitle();

                //Set a title
                if (title == "Untitled") {
                    elTitle.val('');
                } else {
                    elTitle.val(title);
                }
                //Set the title of the html document
                document.title = title + " - FC";
                lasttitle = title;

                //Set up the root node type correctly
                getRootNodeType();

                setToggleStates();

                opClearChanged();

                loading.hide();
            }
        });
    } else {
        if (type == 1) {
            opXmlToOutline(initialRssOpmlText);
        } else {
            opXmlToOutline(initialOpmlText);
        }
        title = opGetTitle();
        elTitle.val(title);
        //Set the title of the html document
        document.title = title + " - FC";
        lasttitle = title;

        setToggleStates();

        opClearChanged();
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
    menubar.find('.menuGenerate').hide();
    if (type === 6) {
        menubar.find('.menuGenerate').show();
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
                case ',':
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
    function saveFile(ftitle, fname, ftype, fredirect, fdisqus, fwysiwyg, fwatched, flocked, fprivate, fprivtoken, fopml, foldname, asarticle, jumptourl, ftemplatename) {
        var _foldname = (typeof foldname === "undefined") ? "" : foldname;
        var _asarticle = (typeof asarticle === "undefined") ? false : asarticle;
        var _jumptourl = (typeof jumptourl === "undefined") ? false : jumptourl;
        var _privtoken = (typeof fprivtoken === "undefined") ? "" : fprivtoken;
        var menubar = $('#menubarEditor');

        //Render the title and byline?
        var rendertitle = true;
        if (!$('.rendertitle').is(':checked')) {
            rendertitle = false;
        }

        //If outline is not private clear the token
        if (!privateOutline) {
            privtoken = "";
            _privtoken = "";
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
                "private": fprivate,
                "privtoken": _privtoken,
                "title": ftitle,
                "rendertitle": rendertitle,
                "aid": aid,
                "articleoverwrite": _asarticle,
                "templatename": ftemplatename
            },
            dataType: "json",
            beforeSend: function () {
                //Disable the save button and show a spinner
                menubar.find('.saves').attr('disabled', true);
                menubar.find('#dropdownSave').html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                //Reset the global change state to false
                opClearChanged();
            },
            success: function (data) {
                //Show returned info and re-enable the save button
                url = data.url;
                htmlurl = data.html;

                privtoken = data.privtoken;

                templateid = data.templateid;

                if (data.status === "true") {
                    updateOutlineInfo(url, data, redirect);
                    showMessage(data.description + ' ' + '<a href="' + data.url + '">Link</a>', data.status, 2);
                } else {
                    showMessage(data.description, data.status, 8);
                    //If save failed keep the outline in a changed state
                    opMarkChanged();
                }
                menubar.find('#dropdownSave').html('Save');
                menubar.find('.saves').attr('disabled', false);
                wasLocked = flocked;

                //Set the title of the html document
                document.title = ftitle + " - FC";

                if (_jumptourl) {
                    //alert(_jumptourl + " | " + url);
                    window.location = '/editor?url=' + url;
                }
            }
        });

        return true;
    }

    //Save an archive
    //TODO: fail on trying to archive a private OPML file
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

    //Save a file generated from a template
    function saveGeneratedFile(ftitle, fname, ftype, fredirect, fdisqus, fwysiwyg, fopml, variables, templateid) {
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
                "rendertitle": true,
                "variables": variables,
                "templateid": templateid
            },
            dataType: "json",
            beforeSend: function () {
                menubar.find('a.menuGenerate').html('<i class="fa fa-spinner fa-spin"></i> Generating...');
            },
            success: function (data) {
                menubar.find('a.menuGenerate').html('Generate');

                //Open the new file in a new tab
                window.location = "/editor?url="+data.url;
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
                //console.log(seltxt);
                urltolink = seltxt;
            } else {
                bootbox.prompt({
                    title: "Type the target link.",
                    value: urltolink,
                    callback: function (result) {
                        if (result !== null) {
                            //console.log(result);
                            urltolink = result;
                            if (urltolink != "" && /^http[s]?\:\/\//.test(urltolink)) {
                                opSetOneAtt('type', 'link');
                                opSetOneAtt('url', urltolink);
                            }
                        }
                    }
                });
            }
            if (urltolink != "" && /^http[s]?\:\/\//.test(urltolink)) {
                opSetOneAtt('type', 'link');
                opSetOneAtt('url', urltolink);
            }
        } else {
            seltxt = getSelected();
            seltxt = new String(seltxt);
            if (/^http[s]?\:\/\//.test(seltxt)) {
                //console.log(seltxt);
                urltolink = seltxt;
            } else {
                bootbox.prompt("Type the target link.", function (result) {
                    if (result !== null) {
                        //console.log(result);
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
            if (!privateOutline) {
                elOutlineinfo.html('<li><a target="_blank" title="Link to opml source of this outline." href="' + url + '">OPML</a></li>');
            }
            if (privateOutline && !isEmpty(privtoken)) {
                htmlurl = "/evex?pt=" + privtoken;
            } else if (data.html == "" || data.html === undefined) {
                htmlurl = url.replace("/opml/", "/html/");
                htmlurl = htmlurl.substr(0, htmlurl.lastIndexOf(".")) + ".html";
            } else {
                htmlurl = data.html;
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
            }
            if (typeof(data.ipfs) !== "undefined") {
                elOutlineinfo.append('<li><a title="Show the ipfs hashes of this outline." href="#" onclick="javascript:alert(\'OPML: ' + data.ipfs.opml + '\\nHTML: ' + data.ipfs.html + '\');return false;">IPFS</a></li>')
            } else if (typeof(ipfsHash) !== "undefined" && ipfsHash !== "") {
                elOutlineinfo.append('<li><a title="Show the ipfs hash of this outline." href="#" onclick="javascript:alert(\'' + ipfsHash + '\');return false;">IPFS</a></li>')
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

        buildNodeVersionHistory(url);

        return true;
    }

    //When a new node is clicked on, this callback fires
    function opCursorMovedCallback(op) {
        var nodetype = op.attributes.getOne('type');
        var nodecreated = op.attributes.getOne('created');
        var hasUrl = false;
        var hasXmlUrl = false;

        //Does the node have a url attribute?
        if (op.attributes.getOne('url') != "undefined" && !isBlank(op.attributes.getOne('url'))) {
            hasUrl = true;
        }
        if (op.attributes.getOne('xmlUrl') != "undefined" && !isBlank(op.attributes.getOne('xmlUrl'))) {
            hasXmlUrl = true;
        }

        if (typeof(nodetype) == "undefined") {
            nodetype = "not set";
        }
        menubar.find('.menuType > a.dropdown-toggle').html('Type (' + nodetype + ') <b class="caret"></b>');

        //If a timestamp exists, show it
        if (nodecreated) {
            menubar.find('.menubar a.menuChangeTimestamp').attr('title', 'Change timestamp for this node. Current: ' + nodecreated);
        }

        //Add a link-out option for include nodes
        menubar.find('.menubar li.extLink').remove();
        menubar.find('.menubar li.extEditLink').remove();
        if (nodetype == "include" || nodetype == "import") {
            if (hasUrl) {
                menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('url') + '"><i class="fa fa-external-link" style="color:#090;"></i></a></li>');
                menubar.find('.menubar').append('<li class="extEditLink"><a target="_blank" href="/editor?url=' + op.attributes.getOne('url') + '"><i class="fa fa-edit" style="color:#090;"></i></a></li>');
            }
            return true;
        }
        if (nodetype == "link" || nodetype == "item" || nodetype == "enclosure" || nodetype == "image") {
            if (hasUrl) {
                menubar.find('.menubar').append('<li class="extLink"><a target="_blank" href="' + op.attributes.getOne('url') + '"><i class="fa fa-external-link" style="color:#090;"></i></a></li>');
            }

            return true;
        }
        if (nodetype == "rss") {
            if (hasXmlUrl) {
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
        //console.log(event)

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
        if (event.which == 91 || event.which == 92) {
            event.which = 0;
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

    //Uploadifive handlers
    if (gDevice != "android") {
        //Main outline upload
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
                    'datestamp': uploaddatestamp
                },
                'onAddQueueItem': function (file) {
                    this.data('uploadifive').settings.formData = {'datestamp': (new Date().valueOf() / 1000)};
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
                    fileMultiDrop = false;
                },
                'onDrop': function (file, fileDropCount) {
                    if (fileDropCount > 1) {
                        fileMultiDrop = true;
                    } else {
                        fileMultiDrop = false;
                    }
                },
                'onUploadComplete': function (file, data) {
                    var jdata = $.parseJSON(data);
                    $('.complete .filename:contains("' + file.name + '")').each(function (index) {
                        if (isImage(jdata.url)) {
                            opInsert('<img src="' + jdata.url + '" style="width:600px;">', down);
                            opSetOneAtt('type', 'image');
                            opSetOneAtt('icon', 'image');
                            opSetOneAtt('url', jdata.url);
                        } else if (isVideo(jdata.url) && !fileMultiDrop) {
                            opInsert('<video style="width:95%;margin:0 auto;display:block;" controls="true" preload="none"><source src="' + jdata.url + '" type="' + jdata.type + '"></video>', down);
                            opSetOneAtt('type', 'video');
                            opSetOneAtt('icon', 'video-camera');
                            opSetOneAtt('url', jdata.url);
                        } else if (isAudio(jdata.url)) {
                            opInsert('<audio style="width:400px; vertical-align:middle;" controls="true" preload="none"><source src="' + jdata.url + '" type="' + jdata.type + '"></audio> <a class="audiocaption" style="vertical-align:middle;" href="' + jdata.url + '">' + file.name + '</a>', down);
                            opSetOneAtt('type', 'audio');
                            opSetOneAtt('icon', 'volume-up');
                            opSetOneAtt('url', jdata.url);
                        } else {
                            if (type === 1) {
                                opInsert(jdata.url, down);
                                if (isAudio(jdata.url)) {
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
        uploaddatestamp = new Date().valueOf() / 1000;
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
        //Don't show drop screen when bootbox is open
        if( $('div.bootbox-body').length == 0 ) {
            return showEditorFileDropZone();
        }
    });

    //Takes an array of objects and builds the node type menu from it
    function buildNodeTypeMenu(menuArray) {
        nodeTypeSelector.empty();

        menuArray.map(function (item) {
            nodeTypeSelector.append('<li><a href="#" class="menuTypeSelection ' + item.class + '" title="' + item.title + '" data-type="' + item.type + '">' + item.text + '</a></li>');
        });
    }

    //Calls for version history and builds the version drop-down from it
    function buildNodeVersionHistory(fileurl) {

        //Make the ajax call
        $.ajax({
            type: 'GET',
            url: '/cgi/out/get.recentfile.versions?url=' + fileurl,
            dataType: "json",
            beforeSend: function () {
                //Clear the table for new data
                nodeVersionHistory.empty();
                //nodeVersionHistory.append('<li><a>Version History</a></li><li role="separator" class="divider"></li>');
            },
            success: function (data) {
                //Iterate
                if (typeof data.versions !== "undefined") {
                    data.versions.map(function (item) {
                        var df = new Date(0);
                        df.setUTCSeconds(item.time);
                        nodeVersionHistory.append('<li><a href="/editor?url=' + item.url + '&versionid=' + item.id + '" class="menuTypeSelection version" title="' + item.title + '" data-type="' + item.type + '">' + dateFormat(df, "m/d/yy @ h:MM:ss TT") + ' (' + bytesToSize(item.size, 2) + ')</a></li>');
                    });
                    if (data.versions.length < 1) {
                        nodeVersionHistory.append('<li><a href="#" class="menuTypeSelection message">No history...</a></li>');
                    }
                } else {
                    nodeVersionHistory.append('<li><a href="#" class="menuTypeSelection message">No history...</a></li>');
                }
            },
            error: function (data) {
                nodeVersionHistory.append('<li><a href="#" class="menuTypeSelection message">Error getting history.</a></li>');
            }
        });
    }

    //Generate a random string
    //__via: https://stackoverflow.com/questions/1349404/generate-random-string-characters-in-javascript
    function makeid(len) {
        var text = "";
        var possible = "BCDFGHJKLMNPQRSTVWXZbcdfghjklmnpqrstvwxz0123456789";

        for (var i = 0; i < len; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }

        return text;
    }

    //Hook the page exit event if the outline is changed
    window.onbeforeunload = function (e) {
        var message = "Your outline has changed. Are you sure you want to leave the page without saving?",
            e = e || window.event;

        if (opHasChanged()) {
            // For IE and Firefox
            if (e) {
                e.returnValue = message;
            }

            // For Safari
            return message;

        } else {
            return null;
        }
    };

    function setToggleStates() {
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
        if (privateOutline) {
            chkTogglePrivate.parent().addClass('active');
        }

        return true;
    }

    function getFileName(ouTitle) {

        if (ouTitle != "") {
            newFilename = ouTitle.replace(/\W/g, '').substring(0, 20) + '-' + makeid(30) + '.opml';
        } else {
            newFilename = bufilename;
        }

        return newFilename;
    }

});

    function getTemplateVariables() {
        var content = [];
        var list = [];
        var re = /\[\[\$(((?!\+\+)(?!\-\-)[^\]])*)](\+\+|\-\-|jpg|png|gif|bmp)?([0-9]*)]/g;
        var s = opOutlineToXml();
        var m;

        do {
            m = re.exec(s);
            if (m) {
                console.log(m[1]);
                if (content.indexOf(m[1]) < 0) {
                    content.push(m[1]);
                }
                list.push({
                    name: m[1],
                    operator: m[3] || "",
                    data: m[4]
                });
            }
        } while (m);

        return { content, list };
    }

    function escapeRegExp(s) {
        return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    }

    function friendlyDateTime(epoch) {
        return dateFormat(epoch * 1000, 'friendly');
    }
