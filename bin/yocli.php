<?

/*
        Simple php udp socket client
	http://www.binarytides.com/udp-socket-programming-in-php/
*/

//Reduce errors
error_reporting(~E_WARNING);

$server = gethostbyname('remote.cartserver.com');
$port = 8904;

if(!($sock = socket_create(AF_INET, SOCK_DGRAM, 0)))
{
        $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);

    die("Couldn't create socket: [$errorcode] $errormsg \n");
}

echo "Socket created \n";

//Communication loop
while(1)
{
        //Take some input to send
        echo 'Enter a message to send : ';
        $input = fgets(STDIN);

        //Send the message to the server
        if( ! socket_sendto($sock, $input , strlen($input) , 0 , $server , $port))
        {
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);

                die("Could not send data: [$errorcode] $errormsg \n");
        }

        //Now receive reply from server and print it
        if(socket_recv ( $sock , $reply , 2045 , MSG_WAITALL ) === FALSE)
        {
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);

                die("Could not receive data: [$errorcode] $errormsg \n");
        }

        echo "Reply : $reply";
}

?>

