<?php

class core {

var $version = "phpircd0.3a";
var $config;
var $address;
var $port;
var $_clients = array();
var $_client_sock = array();
var $_sockets = array();
var $sock_num = 0;
var $servname;
var $network;
var $_channels = array();
var $_nicks = array();

function init($config){
    $this->config = parse_ini_file($config, true);
    if(!$this->config)
        die("Config file parse failed: check your syntax!");
    $listens = explode(',', $this->config['core']['listen']);
//    $this->address = $this->config['core']['address'];
//    $this->port = $this->config['core']['port'];
    $this->servname = $this->config['me']['servername'];
    $this->network = $this->config['me']['network'];
    $this->createdate = $this->config['me']['created'];
    foreach($listens as $l){
        $this->debug("bind to address $l");
        $a = explode(":", trim($l));
        $s = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        $this->_sockets[] = $s;
        if (!socket_set_option($s, SOL_SOCKET, SO_REUSEADDR, 1)) {
            echo socket_strerror(socket_last_error($si))."\n";
            exit;
        } 
        @socket_bind($s,$a['0'],$a['1']) or die("Could not bind socket: ".socket_strerror(socket_last_error($s))."\n");
        socket_listen($s);
        socket_set_nonblock($s);
    }
}

function write($sock, $data){
    $data = substr($data, 0, 509)."\r\n";
    socket_write($sock, $data, strlen($data));
}

function close($key, $sock=false){
    if($sock){
        socket_close($key);
    } else {
        @socket_close($this->_client_sock[$key]);
        unset($this->_clients[$key]);
        unset($this->_client_sock[$key]);
        unset($this->_nicks[$key]);
    }
}

function debug($msg){
    if($this->config['core']['debug'] == true)
        echo $msg . "\n";
}

}
?>
