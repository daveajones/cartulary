<?

//Get the configuration variables
$envars = parse_ini_file(get_cfg_var("cartulary_conf")."/conf/cartulary.conf");

//Pass the array back
extract($envars);

//Set root path to app
$confroot = rtrim(get_cfg_var("cartulary_conf"), '/');


?>
