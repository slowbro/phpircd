<?php

class core {

var $version = "phpircd v0.1e";
var $config;
var $address;
var $port;
var $_clients = array();
var $_client_sock = array();
var $_socket;
var $sock_num;
var $servname;
var $network;
var $_channels = array();

function init($config){
	$this->config = parse_ini_file($config, true);
	$this->address = $this->config['core']['address'];
	$this->port = $this->config['core']['port'];
	$this->servname = $this->config['me']['servername'];
	$this->network = $this->config['me']['network'];
	$this->createdate = $this->config['me']['created'];
	$this->_socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
	if (!socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
	    echo socket_strerror(socket_last_error($this->_socket))."\n";
	    exit;
	} 
	socket_bind($this->_socket,$this->address,$this->port) or die('Could not bind socket.');
	socket_listen($this->_socket);
	socket_set_nonblock($this->_socket);
}

function write($sock, $data){
	$data = $data."\r\n";
	socket_write($sock, $data, strlen($data));
}

function close($key, $sock=false){
        if($sock){
                socket_close($sock);
	} else {
		socket_close($this->_client_sock[$key]);
		unset($this->_clients[$key]);
		unset($this->_client_sock[$key]);
	}
}

}
?>
