<?php

class core {

var $config;
var $address;
var $port;
var $_clients = array();
var $_socket;

function __construct($config){
	$this->config = parse_ini_file($config, true);
	$this->address = $this->config['core']['address'];
	$this->port = $this->config['core']['port'];
	$this->_socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
	socket_bind($this->_socket,$this->address,$this->port);
	socket_listen($this->_socket);
	socket_set_nonblock($this->_socket);
	echo "listening for new clients\n";
	while(true){
		if(($new = @socket_accept($this->_socket)) !== FALSE){
			echo "new client\n";
			$this->_clients[] = $new;
			var_dump($this->_clients);
		}
	usleep(700);
	}
}

}

?>
