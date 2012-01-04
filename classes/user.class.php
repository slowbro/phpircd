<?php

class user {

var $nick = NULL;
var $username;
var $realname;
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
var $oper = false;

function __construct($sock, $ssl=false){
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

function diconnect(){
    global $ircd;
    fclose($this->socket);
    unset($ircd->_clients[$this->id]);
    unset($this);
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
