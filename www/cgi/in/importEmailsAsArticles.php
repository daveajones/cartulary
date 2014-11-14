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
        loggit(3, "IMAP: ".print_r($overview, TRUE));
        $message = imap_fetchbody($inbox,$email_number,2);

        //output the email body */
        $content = $message;

        //Do textual analysis and save it in the database
        $analysis = implode(",", array_unique(str_word_count(strip_tags($content), 1)));

        //Reduce all that whitespace
        $slimcontent = clean_article_content(preg_replace('~>\s+<~', '><', $content), 0, FALSE, FALSE);

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
