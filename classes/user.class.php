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

function __construct($sock){
    $this->socket = $sock;
    socket_getpeername($sock, $this->ip);
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
    $this->buffer[] = $msg;
}

function writeBuffer(){
    global $ircd;
    foreach($this->buffer as $k=> $msg){
        $ircd->write($this->socket, $msg);
        unset($this->buffer[$k]);
    }
}

}

?>
