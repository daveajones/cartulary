<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

$jsondata = array();

/* connect to imap */
$constring = ":143/imap/notls/norsh";
if( $g_prefs['imap_secure'] == 1 ) {
    $constring = ":993/imap/ssl/norsh/novalidate-cert";
}
$hostname = '{'.$g_prefs['imap_server'].$constring.'}'.$g_prefs['imap_folder'];
$username = $g_prefs['imap_username'];
$password = $g_prefs['imap_password'];


/* try to connect */
imap_timeout(IMAP_OPENTIMEOUT, 5);
$inbox = imap_open($hostname,$username,$password);
if(!$inbox) {
    $imaperr = imap_last_error();
    loggit(2,"IMAP: [$g_uid] can't connect to imap server: [$imaperr].");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Error: $imaperr";
    echo json_encode($jsondata);
    return(0);
}


/* grab emails */
$emails = imap_search($inbox,'ALL');

/* if emails are returned, cycle through each... */
$count = 0;
if($emails) {

    /* begin output var */
    $output = '';

    /* put the newest emails on top */
    rsort($emails);

    /* for every email... */
    foreach($emails as $email_number) {

        /* get information specific to this email */
        $overview = imap_fetch_overview($inbox,$email_number,0);
        //loggit(3, "IMAP: ".print_r($overview, TRUE));


        $msgstruct = imap_fetchstructure($inbox, $email_number);
        $flattenedParts = flattenParts($msgstruct->parts);

        //Look first for an html or plain subtype to get as if this is a multipart
        $content = "";
        foreach($flattenedParts as $partNumber => $part) {
            switch($part->type) {
                case 0:
                    // the HTML or plain text part of the email
                    $content = getPart($inbox, $email_number, $partNumber, $part->encoding);
                    // now do something with the message, e.g. render it
                    break;

                case 1:
                    // multi-part headers, can ignore

                    break;
                case 2:
                    // attached message headers, can ignore
                    break;

                case 3: // application
                case 4: // audio
                case 5: // image
                case 6: // video
                case 7: // other
                    $filename = getFilenameFromPart($part);
                    if($filename) {
                        // it's an attachment
                        $attachment = getPart($inbox, $email_number, $partNumber, $part->encoding);
                        // now do something with the attachment, e.g. save it somewhere
                    }
                    else {
                        // don't know what it is
                    }
                    break;
            }
        }

        //loggit(3, "IMAP: ".print_r($msgstruct, TRUE));


        //Do textual analysis and save it in the database
        $analysis = implode(",", array_unique(str_word_count(strip_tags($content), 1)));

        //Reduce all that whitespace
        //$slimcontent = clean_article_content(preg_replace('~>\s+<~', '><', $content), 0, FALSE, FALSE);
        $slimcontent = clean_email_html($content);

        //add to articles repo
        $title = 'EMAIL: '.$overview[0]->subject;
        $url = 'mailto:'.$g_myemail.'?subject='.urlencode($overview[0]->subject).'&from='.urlencode($overview[0]->from).'&udate='.urlencode($overview[0]->udate).'&msgid='.urlencode($overview[0]->message_id);
        $aid = "";
        if( !article_exists($url) ) {
            $aid = add_article($url, $title, $slimcontent, $analysis, $g_uid);
        }
        if( !empty($aid) ) {
            //echo $title." --> [$aid]\n";
            $count++;
        }

        //debug
        break;
    }
}

/* close the connection */
imap_close($inbox);

//Log it
loggit(1,"User: [$uid] imported emails from imap.");

//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['count'] = $count;
$jsondata['description'] = "Emails imported.";
echo json_encode($jsondata);

return(0);
