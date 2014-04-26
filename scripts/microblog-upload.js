if ( gDevice != "android" ) {
    var enclist = new Array();
    $(function () {
        $('#file_upload').uploadifive({
            'auto': true,
            'method': 'post',
            'dnd': true,
            'queueID': 'queue',
            'uploadScript': '/cgi/in/upload',
            'simUploadLimit': 1,
            'formData': {
                'datestamp': gDatestamp
            },
            'onSelect': function (queue) {
                $('#divEnclosures').show();
            },
            'onAddQueueItem': function (file) {
                console.log(file);
                if ( $.inArray(file.name, enclist) > -1 ) {
                    $('#file_upload').uploadifive('cancel', file, true);
                }
            },
            'onClearQueue': function (queue) {
                enclist = [];
                $('input.postenclosure').remove();
            },
            'onCancel': function (file) {
                $("input.postenclosure[data-filename='" + file.name + "'").remove();
                enclist.splice($.inArray(file.name, enclist), 1);
            },
            'onUploadComplete': function (file, data) {
                var jdata = $.parseJSON(data);
                $('.complete .filename:contains("' + file.name + '")').each(function (index) {
                    if ( isImage(jdata.url) ) {
                        $(this).parent().append('<a href="' + jdata.url + '" title="Click to embiggen."><img class="imgenclosure" src="' + jdata.url + '" alt="" /></a>');
                        $(this).parent().children('span.fileinfo').hide();
                    }
                    $(this).html('Link: <a href="' + jdata.url + '" title="Click to embiggen.">' + jdata.url + '</a>');
                    $(this).parent().append('<input type="hidden" class="postenclosure" data-filename="' + file.name + '" name="enclosure[]" />');
                    $(this).parent().children('input').val(data);

                    enclist.push(file.name);
                });
            }
        })
    });
}
