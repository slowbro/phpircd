<?php

class core {

var $version = "phpircd0.3.03";
var $config;
var $address;
var $port;
var $_clients = array();
var $_sockets = array();
var $_channels = array();
var $client_num = 0;
var $channel_num = 0;
var $servname;
var $network;

function init($config){
    $this->config = parse_ini_file($config, true);
    if(!$this->config)
        die("Config file parse failed: check your syntax!");
    $listens = explode(',', $this->config['core']['listen']);
    $this->servname   = $this->config['me']['servername'];
    $this->network    = $this->config['me']['network'];
    $this->createdate = $this->config['me']['created'];
    foreach($listens as $l){
        $this->debug("bind to address $l");
        $a = explode(":", str_replace("::ffff:","",trim($l)));
        $s = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
        $this->_sockets[] = $s;
        if (!socket_set_option($s, SOL_SOCKET, SO_REUSEADDR, 1)) {
            echo socket_strerror(socket_last_error($si))."\n";
            exit;
        } 
        if(@!socket_bind($s,$a['0'],$a['1']))
            if(socket_last_error($s) == -10001)
                @socket_bind($s,"::ffff:".$a['0'],$a['1']) or die("Could not bind socket: ".socket_strerror(socket_last_error($s))."\n");
            else
                die("Could not bind socket: ".socket_strerror(socket_last_error($s))."\n");
        socket_listen($s);
        socket_set_nonblock($s);
    }
}

function read($sock){
    $buf = @socket_read($sock, 1024, PHP_BINARY_READ);
    if($buf)
        $this->debug(trim($buf));
    return $buf;
}

function write($sock, $data){
    $data = substr($data, 0, 509)."\r\n";
    socket_write($sock, $data, strlen($data));
}

function close($user, $sock="legacy"){
    if(is_resource($user)){
        socket_close($user);
    } else {
        @socket_close($user->socket);
        unset($this->_nicks[$user->nick]);
        unset($this->_clients[$user->id]);
    }
}

function debug($msg){
    if($this->config['core']['debug'] == true)
        echo $msg . "\n";
}

}
?>
