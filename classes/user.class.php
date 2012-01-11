<?php

class User {

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
var $buffer = array();
var $readBuffer = array();
var $modes = array();

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

function disconnect(){
    global $ircd;
    fclose($this->socket);
    unset($ircd->_clients[$this->id]);
    unset($this);
}

function getModes(){
    $modes = '+';
    $extra = array();
    foreach($this->modes as $m=>$e){
        if(is_array($e))
            continue;
        $modes .= $m;
        if($e !== true)
            $extra[] = $e;
    }
    return $modes.' '.implode(' ', $extra);
}

function hasMode($m, $t=false){
    global $ircd;
    if(isset($this->modes[$m]))
        if($ircd->userModes[$m]->type == Mode::TYPE_A || $ircd->userModes[$m]->type ==  Mode::TYPE_P)
            return in_array($t, $this->modes[$m]);
        else
            return true;
    return false;
}

function maskHost(){
    global $ircd;
    $address = explode('.',$this->address);
    if(count($address) == 2 && strlen($address['1']) < 5){
        //mask things like slowbro.org, minecraft.net, etc
        $address['0'] = $ircd->config['ircd']['hostmask_prefix'].
                    '-'.
                    strtoupper(substr(hash('sha512', $ircd->config['ircd']['hostmask_secret'].$address['0']), 0, $ircd->config['ircd']['hostmask_length']));
        $address = implode('.', $address);
    } elseif(count($address) == 2 && strlen($address['1']) > 5) {
        //mask things like localhost.localdomain
        $address = implode('.', $address);
        $address = $ircd->config['ircd']['hostmask_prefix'].
                    '-'.
                    strtoupper(substr(hash('sha512', $ircd->config['ircd']['hostmask_secret'].$address), 0, $ircd->config['ircd']['hostmask_length']));
    } elseif(preg_match($ircd->ipv4Regex, $this->address)){
        // mask ipv4 address
        foreach($address as &$a){
            $a = strtoupper(substr(hash('sha512', $ircd->config['ircd']['hostmask_secret'].$a), 0, $ircd->config['ircd']['hostmask_length']));
        }
        $address['3'] = "IP";
        $address = implode('.', $address);
    } else {
        //mask things like ip20-30-60-90.phx.tc.cox.net, ip20.30.60.90gr1.phx.west.verizon.net, ect
        $last = 0;
        $mask = array();
        foreach($address as $k=>$a){
            if(preg_match("[0-9]", $a)){
                $last = $k;
            }
        }
        if($last == 0)
            $last = 2;
        if($last == count($address)-1)
            $last -=1;
        for($i=0;$i<=$last;$i++){
            $mask[] = $address[$i];
            unset($address[$i]);
        }
        $mask = $ircd->config['ircd']['hostmask_prefix'].
                    '-'.
                    strtoupper(substr(hash('sha512', $ircd->config['ircd']['hostmask_secret'].implode('.', $mask)), 0, $ircd->config['ircd']['hostmask_length']));
        $address = $mask.'.'.implode('.', $address);
    }
    $this->prefix = $this->nick."!".$this->username."@".$address;
    $this->setModes("+x");
}

function removeChannel($chan){
    if(($k = array_search($chan->name, $this->channels)) !== FALSE)
        unset($this->channels[$k]);
}

function send($msg){
    $this->buffer[] = $msg;
}

function setMode($act, $mode, &$parts, &$what){
    global $ircd;
    $atext = ($act=='+'?'set':'unset');
    //first, check set/unset hook
    if(isset($mode->hooks[$atext])){    
        $d = array('user'=>&$this, 'extra'=>@$parts['0']);
        if(!$mode->hooks[$atext](&$d)){
            if(isset($d['errno']))
                $ircd->error($d['errno'], $this, @$d['errstr']);
            return false;
        }
    }
    $ms = isset($this->modes[$mode->letter]);
    if($mode->type == Mode::TYPE_A || $mode->type == Mode::TYPE_P){
        // there are no TYPE_A or TYPE_P usermodes
        return false;
    } elseif($mode->type == Mode::TYPE_B || $mode->type == Mode::TYPE_C){
        // only mode +s (TYPE_C)
        $pt = array_shift($parts);
        // 1: ignore if ((regex is set AND !match regex) OR param is missing) AND (act is add OR type is TYPE_B) OR...
        // 2: ignore if mode is already set AND act is add OR...
        // 3: ignore if mode is not set AND act is remove
        if(((isset($mode->regex) && !preg_match(@$mode->regex, $pt)) || $pt === NULL ) && ($act == '+' || $mode->type == Mode::TYPE_B)) return false;
        if($ms && $act == '+') return false;
        if(!$ms && $act == '-') return false;
        if($act == '+')
            $this->modes[$mode->letter] = $pt;
        else
            unset($this->modes[$mode->letter]);
        $what['params'][] = $pt;
    } elseif($mode->type == Mode::TYPE_D){
        // ignore not already set
        if(($act == '+' && $ms) || ($act == '-' && !$ms))
            return false;
        if($act == '+')
            $this->modes[$mode->letter] = true;
        else
            unset($this->modes[$mode->letter]);
    }
    $what[] = $act.$mode->letter;
    return true;
}

function setModes($mask){
    global $ircd;
    $parts = explode(" ", $mask);
    $mask = str_split(array_shift($parts));
    $act = "";
    $what = array();
    foreach($mask as $c){
        if($c == '+' || $c == '-'){
            $act = $c;
            continue;
        }
        if($act == "")
            continue;
        if(!($mode = $ircd->userModes[$c])){
            $ircd->error(472, $user, $c);
            continue;
        }
        $this->setMode($act, $mode, $parts, $what);
    }
    $p = @implode(' ',@$what['params']);
    unset($what['params']);
    $ta = $str = "";
    foreach($what as $w){
        if($ta != ($a = substr($w, 0, 1)))
            $str .= $ta = $a;
        $str .= substr($w, 1, 2);
    }
    if(!empty($str))
        $this->send(":{$this->nick} MODE {$this->nick} ".$str.(!empty($p)?' '.$p:''));
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
