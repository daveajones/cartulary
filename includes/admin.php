<?php
//########################################################################################
// API for managing server administrative tasks
//########################################################################################


//Create a new user in the user table
function create_user($email = NULL, $silent = NULL, $inside = NULL, $active = NULL, $name = "")
{
    //Check all the params
    if ($email == NULL) {
        loggit(2, "Didn't provide an email address: [$email]");
        return (FALSE);
    }

    //Get global system variables
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Check the alias table to see if this email is an existing alias of another email address
    if (get_email_from_alias($email, $aliasof) == TRUE) {
        $email = $aliasof;
    }

    //Look for the email address in the user table to see if it already exists
    $stmt = "SELECT id FROM $table_user WHERE email=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "1_" . $dbh->error);
    $sql->bind_param("s", $email) or loggit(2, "2_" . $dbh->error);
    $sql->execute() or loggit(2, "3_" . $dbh->error);
    $sql->store_result() or loggit(2, "4_" . $dbh->error);

    //If the result set is greater than zero then the user exists already
    if ($sql->num_rows() > 0) {
        loggit(2, "User creation error.  User already exists: [$email] using sql: [$stmt]($email)");
        $sql->bind_result($id) or loggit(2, "6_" . $dbh->error);
        $sql->fetch() or loggit(2, "7_" . $dbh->error);
        $sql->close() or loggit(2, "5_" . $dbh->error);
        return ($id);
    }
    $sql->close() or loggit(2, "8_" . $dbh->error);

    //Generate a temporary first time password
    $newuid = random_gen(60);
    $password = random_gen(8);

    //Generate a unique username on this system
    $emparts = explode("@", $email);
    $username = $emparts[0] . random_gen(4);
    $tuid = get_user_id_from_username($username);
    $tc = 0;
    while ($tuid != "none" && $tuid != FALSE) {
        $username = $emparts[0] . random_gen(4);
        $tuid = get_user_id_from_username($username);
        $tc++;
        if ($tc > 10) {
            loggit(2, "Tried 10 times to create a user but couldn't: [$email | $username].");
            return (FALSE);
        }
    }

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Insert the user into this table
    if ($inside != TRUE) {
        if ($active != TRUE) {
            $stmt = "INSERT INTO $table_user (name,id,email,password,username) VALUES(?,?,?,?,?)";
        } else {
            $stmt = "INSERT INTO $table_user (name,id,email,active,password,username) VALUES(?,?,?,1,?,?)";
        }
    } else {
        if ($active != TRUE) {
            $stmt = "INSERT INTO $table_user (name,id,email,password,inside,username) VALUES(?,?,?,?,1,?)";
        } else {
            $stmt = "INSERT INTO $table_user (name,id,email,active,password,inside,username) VALUES(?,?,?,1,?,1,?)";
        }
    }
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sssss", $name, $newuid, $email, $password, $username) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Hash the password
    set_password($newuid, $password);

    //Send the email
    if ($silent != TRUE) {
        //send_newuser_email($email, $password);
    }

    //Create an initial prefs entry
    init_user_prefs($newuid);

    //Log it
    loggit(1, "Created the new user: [$email|$newuid|$password|$username|$name]");

    return (TRUE);
}


//Delete a user by user id
function delete_user_by_user_id($uid = NULL)
{
    //If uid is zero then balk
    if ($uid == NULL) {
        loggit(2, "This user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Get global system variables
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_user WHERE id=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and leave
    loggit(1, "Deleted: [$delcount] user: [$uid].");
    return (TRUE);
}


//Add an post to the post repository
function add_admin_log_item($content = NULL, $title = "", $url = "", $enclosure = "", $source = FALSE)
{
    //Check parameters
    if (empty($content)) {
        loggit(2, "The log content is blank or corrupt: [$content]");
        return (FALSE);
    }
    if ($source == FALSE) {
        loggit(1, "The log source is blank or corrupt: [$source]. Setting it to blank.");
        $source = array();
        $source['url'] = "";
        $source['title'] = "";
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Timestamp
    $createdon = time();

    //Now that we have a good id, put the post into the database
    $stmt = "INSERT INTO $table_adminlog (url,content,createdon,title,enclosure,sourceurl,sourcetitle) VALUES (?,?,?,?,?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "SQL Error: [" . $dbh->error . "]");
    $sql->bind_param("sssssss", $url, $content, $createdon, $title, $enclosure, $source['url'], $source['title']) or loggit(2, "SQL Error: [" . $dbh->error . "]");
    //loggit(3,"Executing SQL: [".$stmt."]");
    $sql->execute() or loggit(2, "SQL Error: [" . $dbh->error . "]");
    $sql->close() or loggit(2, "SQL Error: [" . $dbh->error . "]");

    //Log and return
    loggit(1, "Added a new admin log: [$content | $title].");
    return (TRUE);
}


//Retrieve entries from the admin log
function get_admin_log_items($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $sqltxt = "SELECT id,title,url,createdon,content,enclosure,sourceurl,sourcetitle FROM $table_adminlog";

    $sqltxt .= " ORDER BY createdon DESC";

    if (!empty($max) && is_numeric($max)) {
        $sqltxt .= " LIMIT $max";
    }

    //loggit(3, "[$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any posts for this user
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No posts returned for: [$uid] with the given criteria.");
        return (FALSE);
    }

    $sql->bind_result($aid, $atitle, $aurl, $acreatedon, $acontent, $aenclosure, $asourceurl, $asourcetitle) or loggit(2, "MySql error: " . $dbh->error);

    $posts = array();
    $count = 0;
    while ($sql->fetch()) {
        $posts[$count] = array('id' => $aid,
            'title' => $atitle,
            'url' => $aurl,
            'createdon' => $acreatedon,
            'content' => $acontent,
            'enclosure' => $aenclosure,
            'sourceurl' => $asourceurl,
            'sourcetitle' => $asourcetitle);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning: [$count] log items in the admin log.");
    return ($posts);
}


//Build an rss feed for the system admin log
function build_admin_log_rss_feed($max = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Get a proper max value
    if ($max == NULL) {
        $max = $default_max_rss_items;
    }

    //Get the right timezone
    date_default_timezone_set('America/Chicago');

    //Get the lot items
    $posts = get_admin_log_items($max);

    //Make a valid pub date for the feed
    $pubdate = date("D, d M Y H:i:s O");
    if ($posts != FALSE) {
        $pubdate = date("D, d M Y H:i:s O", $posts[0]['createdon']);
    }

    //Make a title for the feed
    $title = "Server Event Log";

    //Get the correct link
    $feedlink = $system_url;

    //The feed string
    $rss = '<?xml version="1.0"?>' . "\n  <rss version=\"2.0\" xmlns:source=\"http://source.smallpict.com/2014/07/12/theSourceNamespace.html\" xmlns:sopml=\"$sopmlnamespaceurlv1\">\n    <channel>";

    $rss .= "\n
      <title>" . htmlspecialchars($title . " - " . $system_fqdn) . "</title>
      <link>" . htmlspecialchars($feedlink) . "</link>
      <description>$title</description>
      <language>en-us</language>
      <pubDate>$pubdate</pubDate>
      <lastBuildDate>" . date("D, d M Y H:i:s O") . "</lastBuildDate>
      <generator>$system_name, v$version</generator>\n";

    foreach ($posts as $post) {
        if ($post['url'] == "") {
            $rsslink = "";
            $linkfull = "";
            $guid = "        <guid isPermaLink=\"false\">" . $post['id'] . "</guid>";
        } else {
            $rssurl = htmlspecialchars($post['url']);
            $rsslink = "        <link>$rssurl</link>";
            $guid = "        <guid>$rssurl</guid>";
        }
        if (!empty($post['enclosure'])) {
            $enclosures = unserialize($post['enclosure']);
        }

        $rss .= "

      <item>\n";
        if (!empty($post['title'])) {
            $rss .= "        <title>" . htmlspecialchars(trim($post['title'])) . "</title>\n";
        }
        $rss .= "        <description><![CDATA[" . trim(str_replace("\n", '', $post['content'])) . "]]></description>
        <pubDate>" . date("D, d M Y H:i:s O", $post['createdon']) . "</pubDate>\n";
        $rss .= $guid . "\n";
        if (!empty($rsslink)) {
            $rss .= $rsslink . "\n";
        }
        if (!empty($linkfull)) {
            $rss .= $linkfull . "\n";
        }
        if (isset($enclosures)) {
            if (is_array($enclosures) && count($enclosures) > 0) {
                foreach ($enclosures as $enclosure) {
                    $elen = 'length="0"';
                    $etyp = 'type="application/octet-stream"';
                    if (!empty($enclosure['length'])) {
                        $elen = 'length="' . $enclosure['length'] . '"';
                    }
                    if (!empty($enclosure['type'])) {
                        $etyp = 'type="' . $enclosure['type'] . '"';
                    }
                    if (!empty($enclosure['url'])) {
                        $rss .= '        <enclosure url="' . htmlspecialchars(trim($enclosure['url'])) . '" ' . $elen . ' ' . $etyp . ' />' . "\n";
                    }
                }
            }
        }
        if (!empty($post['sourceurl']) || !empty($post['sourcetitle'])) {
            $rss .= '        <source url="' . htmlspecialchars(trim($post['sourceurl'])) . '">' . htmlspecialchars(trim($post['sourcetitle'])) . '</source>' . "\n";
        }
        $rss .= "        <author>System</author>\n";
        $rss .= "      </item>\n";
    }

    $rss .= "\n    </channel>\n  </rss>";


    //Mark feed as updated internally
    $afid = feed_exists("http://localhost" . $adminlogfeed);
    if ($afid != FALSE) {
        mark_feed_as_updated($afid);
    }


    loggit(1, "Built admin log rss feed.");
    return ($rss);
}


//Initialize an empty set of system prefs
function init_system_prefs()
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //New feed check token
    $afctoken = random_gen(17);

    //Build query
    $stmt = "INSERT IGNORE INTO $table_sysprefs (system_name,system_url,admin_feed_check_token) VALUES (?,?,?)";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $cg_system_name, $cg_system_url, $afctoken) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Now get the prefs we just made
    $sysprefs = get_system_prefs(TRUE);

    //Log and return
    loggit(1, "Initialized a default set of system prefs.");
    return ($sysprefs);
}


//Return an array of all the prefs for this system
function get_system_prefs($noinit = FALSE)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli_Extended($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Build query
    $sql = $dbh->prepare("SELECT * FROM $table_sysprefs") or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if the session is valid
    $recount = $sql->num_rows();
    if ($recount != 1) {
        $sql->close() or loggit(2, "MySql error: " . $dbh->error);
        loggit(2, "No system prefs could be retrieved. Records: [$recount]");
        if ($noinit == TRUE) {
            return (FALSE);
        } else {
            return (init_system_prefs());
        }
    }
    $sysprefs = $sql->fetch_assoc();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(1, "Returning system prefs array.");
    return ($sysprefs);
}


//Set prefs for this user
function set_system_prefs($sysprefs = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    if ($sysprefs == NULL) {
        loggit(2, "The prefs array is blank or corrupt: [$sysprefs]");
        return (FALSE);
    }

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $stmt = "UPDATE $table_sysprefs
	         SET  system_name=?,
                  system_url=?,
                  default_style_sheet=?,
                  s3key=?,
                  s3secret=?,
                  s3bucket=?,
                  s3cname=?,
                  twitterkey=?,
                  twittersecret=?,
                  twittertoken=?,
                  twittertokensecret=?,
                  urlshortener=?,
                  default_avatar_url=?,
                  s3shortbucket=?,
                  lastshortcode=?,
                  riverhours=?,
                  microblogtitle=?,
                  cartularytitle=?,
                  mbfilename=?,
                  cartfilename=?,
                  admin_feed_check_token=?
           WHERE system=1";

    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sssssssssssssssdsssss",
        $sysprefs['system_name'],
        $sysprefs['system_url'],
        $sysprefs['default_style_sheet'],
        $sysprefs['s3key'],
        $sysprefs['s3secret'],
        $sysprefs['s3bucket'],
        $sysprefs['s3cname'],
        $sysprefs['twitterkey'],
        $sysprefs['twittersecret'],
        $sysprefs['twittertoken'],
        $sysprefs['twittertokensecret'],
        $sysprefs['urlshortener'],
        $sysprefs['default_avatar_url'],
        $sysprefs['s3shortbucket'],
        $sysprefs['lastshortcode'],
        $sysprefs['riverhours'],
        $sysprefs['microblogtitle'],
        $sysprefs['cartularytitle'],
        $sysprefs['mbfilename'],
        $sysprefs['cartfilename'],
        $sysprefs['admin_feed_check_token']
    ) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(1, "Set system prefs.");
    return (TRUE);
}


//Set the system admin feed check token
function new_system_admin_feed_check_token() {

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Create a new token and set it
    $afctoken = random_gen(17);
    $sysprefs = get_system_prefs();
    $sysprefs['admin_feed_check_token'] = $afctoken;
    set_system_prefs($sysprefs);

    //Now change the admin feed url in the newsfeeds table
    $newurl = "http://localhost/adminlog-rss?t=$afctoken";
    $oldurl = "http://localhost/adminlog-rss";

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Look for the sid in the session table
    $stmt = "UPDATE $table_newsfeed SET url=? WHERE url LIKE CONCAT('%', ?, '%')";

    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss",
        $newurl,
        $oldurl
    ) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    loggit(3, "Set a new admin feed check token: [$afctoken]");
    return($afctoken);
}