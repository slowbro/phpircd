<?php

class Channel {

var $id;
var $name;
var $created = 0;
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

function addUser(&$user, $creator=false){
    $this->users[$user->id] = true;
    if($creator)
        $this->modes['o'][] = $user->nick;
}

function getModes(){
    global $ircd;
    $modes = '+';
    $extra = array();
    foreach($this->modes as $m=>$e){
        if(is_array($e))
            continue;
        $modes .= "$m";
        if($e !== true)
           $extra[] = $e;
    }
    return $modes.' '.implode(' ', $extra);
}

function getUserPrefix($user){
    global $ircd;
    $pfx = '';
    $n = 0;
    foreach($ircd->chanModes as $m)
        if($m->type == Mode::TYPE_P && isset($m->prefix) && $this->hasMode($m->letter, $user->nick))
            if($m->privs > $n)
                $pfx = $m->prefix;
    return $pfx;
}

function hasMode($m, $t=false){
    global $ircd;
    if(isset($this->modes[$m]))
        if($ircd->chanModes[$m]->type == Mode::TYPE_A || $ircd->chanModes[$m]->type == Mode::TYPE_P)
            return in_array($t, $this->modes[$m]);
        else
            return true;
    return false;
}

function hasPrivs($user, $priv){
    global $ircd;
    $upriv = 0;
    foreach($ircd->chanModes as $m)
        if($m->type == Mode::TYPE_P && isset($m->privs) && $this->hasMode($m->letter, $user->nick))
            $upriv = $upriv | $m->privs;
    if($upriv & $priv)
        return true;
    return false;
}

function isBanned($user){
    return false;
}

function nick($user, $oldnick){
    foreach($this->modes as &$m)
        if(is_array($m))
            foreach($m as &$n)
                $n = ($n == $oldnick?$user->nick:$n);
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

function setMode(&$user, $act, $mode, &$parts, &$what){
    global $ircd;
    $atext = ($act=='+'?'set':'unset');
    //first, check set/unset hook
    if(isset($mode->hooks[$atext])){
        $d = array('user'=>&$user, 'chan'=>&$this, 'extra'=>@$parts['0']);
        if(!$mode->hooks[$atext](&$d)){
            if(isset($d['errno']))
                $ircd->error($d['errno'], $user, @$d['errstr']);
            return false;
        }
    }
    $ms = isset($this->modes[$mode->letter]);
    if($mode->type == Mode::TYPE_A || $mode->type == Mode::TYPE_P){
        //modes like +beI and +qaohv
        $pt = array_shift($parts);
        // ignore if regex mismatch
        if(isset($mode->regex) && !preg_match(@$mode->regex, $pt)) return false;
        //if empty param, list
        if($pt === NULL){
            
        } else {
            if($act == '+'){
                $this->modes[$mode->letter][] = $pt;
            } else {
                if(($k = array_search($pt, @$this->modes[$mode->letter])) !== FALSE)
                    unset($this->modes[$mode->letter][$k]);
                else
                    return false;
            }
            $what['params'][] = $pt;
        }
    } elseif($mode->type == Mode::TYPE_B || $mode->type == Mode::TYPE_C){
        //modes like +fJklL
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
        //other toggleable modes
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

function setModes(&$user, $mask){
    global $ircd;
    $parts = explode(" ", $mask);
    $mask = str_split(array_shift($parts));
    $act = "";
    $what = array();
    //set the modes and gather info in $what
    foreach($mask as $c){
        if($c == '+' || $c == '-'){
            $act = $c;
            continue;
        }
        if($act == "")
            continue;
        if(!($mode = $ircd->chanModes[$c])){
            $ircd->error(472, $user, $c);
            continue;
        }
        $this->setMode($user, $act, $mode, $parts, $what);
    }
    //parse $what and ooutput what modes were actually set
    $p = @implode(' ',@$what['params']);
    unset($what['params']);
    $ta = $str = "";
    foreach($what as $w){
        if($ta != ($a = substr($w, 0, 1)))
            $str .= $ta = $a;
        $str .= substr($w, 1, 2);
    }
    if(!empty($str))
        $this->send(":{$user->prefix} MODE {$this->name} ".$str.(!empty($p)?' '.$p:''));
}

function setTopic($user, $msg){
    $this->topic = $msg;
    $this->topic_setby = $user->nick;
    $this->topic_seton = time();
}

function userInChan($nick){
    global $ircd;
    $u = $ircd->getUserByNick($nick);
    if(!$u)
        return false;
    if(isset($this->users[$u->id]))
        return true;
    return false;
}

}

?>
