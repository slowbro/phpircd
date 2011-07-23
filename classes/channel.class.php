<?php

class Channel {

var $id;
var $name;
var $users = array();
var $modes = array();
var $bans = array();
var $excepts = array();
var $invex = array();
var $topic = "";
var $topic_setby = "";
var $topic_seton = 0;

function __construct($id, $name){
    $this->id = $id;
    $this->name = $name;
    $this->created = time();
}

function addUser($user, $mode=''){
    $this->users[$user->id] = $mode;
}

function getModes(){
    $modes = '+';
    $extra = array();
    foreach($this->modes as $m=>$e){
        $modes .= "$m";
        if(!empty($e))
            $extra[] = $e;
    }
    return $modes.' '.implode(' ', $extra);
}

function removeUser($user){
    if(array_key_exists($user->id, $this->users))
        unset($this->users[$user->id]);
}

function send($msg, $excl=""){
    global $ircd;
    foreach($this->users as $id=>$m){
        if(is_object($excl))
            if($excl->id == $id)
                continue;
        $ircd->_clients[$id]->send($msg);
    }
}

function setModes($user, $mask){
    $parts = explode(" ", $mask);
    $mask = str_split($parts['0']);
    $act = "";
    foreach($mask as $c){
        if($c == '+' || $c == '-'){
            $act = $c;
            continue;
        }
        if($act == '+')
            $this->modes[$c] = '';
        else
            unset($this->modes[$c]);
        $this->send(":{$user->prefix} MODE $this->name $act$c");
    }
}

function setTopic($user, $msg){
    $this->topic = $msg;
    $this->topic_setby = $user->nick;
    $this->topic_seton = time();
}

}

?>
