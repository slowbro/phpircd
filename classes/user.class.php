<?php

class user {

var $nick = NULL;
var $real;
var $prefix;
var $ip;
var $address;
var $socket;
var $channels = array();
var $swhois;
var $regbit = 0;
var $registered = false;
var $lastping;
var $lastpong;
var $ssl = false;

function __construct($sock, $ssl=false){
    global $ircd;
    $ircd->debug("Called user::__construct with ssl=".(int)$ssl);
    $this->socket = $sock;
    $this->ssl = $ssl;
    $ip = stream_socket_get_name($this->socket, true);
    $c = strrpos($ip, ":");
    $this->ip = substr($ip, 0, $c);
    $this->lastping = $this->lastpong = time();
}

function __destruct(){

}

function addChannel($chan){
    $this->channels[] = $chan->name;
}

function removeChannel($chan){
    if(($k = array_search($chan->name, $this->channels)) !== FALSE)
        unset($this->channels[$k]);
}

function send($msg){
    global $ircd;
    $ircd->debug("Called user::send");
    $this->buffer[] = $msg;
}

function writeBuffer(){
    global $ircd;
    $ircd->debug("Called user::writeBuffer with this::ssl=".(int)$this->ssl);
    foreach($this->buffer as $k=> $msg){
        $ircd->write($this->socket, $msg);
        unset($this->buffer[$k]);
    }
}

}

?>
