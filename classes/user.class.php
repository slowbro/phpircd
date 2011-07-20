<?php

class user extends ircd {

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

}

?>
