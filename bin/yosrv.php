<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?
/*
	Yo! server.  Exchange UDP notifcations
        Based on: http://www.binarytides.com/udp-socket-programming-in-php/
*/

  //Create a UDP socket
  if(!($sock = socket_create(AF_INET, SOCK_DGRAM, 0)))
  {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);

    die("Couldn't create socket: [$errorcode] $errormsg \n");
  }
  echo "Socket created \n";


  // Bind the source address
  if( !socket_bind($sock, "0.0.0.0" , 8904) )
  {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);

    die("Could not bind socket : [$errorcode] $errormsg \n");
  }
  echo "Socket bind OK \n";


  //Wait for incoming packets and process according to the action code:data
  //specified
  while(1) {

	echo "Waiting for data ... \n";

	//Receive some data
	$r = socket_recvfrom($sock, $buf, 512, 0, $remote_ip, $remote_port);
        $ac = substr(trim($buf), 0, 1);
        $data = substr(trim($buf), 2);

	echo "$remote_ip:$remote_port: [" . trim($buf) . "]";

	//Act according to the requested action code
        switch($ac) {

	  //This is a request to be notified when a feed we manage changes
	  case "R":
	  	//We need to first challenge the requester to make sure the
		//source ip address isn't spoofed
		$challenge = random_gen(16);
		socket_sendto($sock, "C:$challenge:$data", 100 , 0 , $remote_ip , $remote_port);
		break;

	  //This is a response to a challenge request
	  case "C":
	  	//We need to check the response value against what we have in the database
		//If it checks out, we go ahead and begin notifying
		socket_sendto($sock, "A:OK", 100 , 0 , $remote_ip , $remote_port);
		break;

	  //This is an owner notification that a feed has changed
	  case "U":
	  	//Flag this feed as needing to be scanned
		//See if this feed exists
		$url = $data;
		loggit(3, "YOSRV: Incoming ping for url: [$url].");
		$id = feed_exists($url);
		if( $id != FALSE ) {
		  loggit(3, "YOSRV: Feed exists as id: [".$id."]");
		  //Flag the feed as needing to be scanned
		  mark_feed_as_updated($id);
		}
		break;

	}

	//Always pause for a moment after handling a request to avoid
        //a hard loop
        sleep(1);
  }


  socket_close($sock);
?>
