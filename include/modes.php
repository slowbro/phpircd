<?php

$userModes = array(
    /*'i' => array(
        'type' => 'bool',
        'extra' => false
    ),
    'w' => array(
        'type' => 'bool',
        'extra' => false
    ),
    'x' => array(
        'type' => 'bool',
        'extra' => false
    ),*/
    'z' => new Mode('z','user','bool',false, array(
            'connect' => function(&$d){
                if($d['user']->ssl)
                    $d['user']->setMode('z');
            }
    ))
);

$channelModes = array(
    'a' => new Mode('a','channel','array',true),
    'A' => array(),
    'b' => new Mode('b','channel','array',true, array(
            'join'=> function(&$d){
                $d['errno'] = 404;
                $d['errstr'] = ": You are banned (+b) ({$d['chan']->name})";
                if($d['chan']->isBanned($d['user']))
                    return false;
                return true;
            }
        )),
    'c' => array(),
    'C' => array(),
    'e' => array(
        'extra'=>true,
        'type'=>'array'
    ),
    'f' => array(
        'extra'=>true
    ),
    'G' => array(),
    'h' => new Mode('h','channel','array',true),
    'H' => array(),
    'i' => array(),
    'I' => array(
        'extra'=>true,
        'type'=>'array'
    ),
    'J' => array(),
    'k' => array(
        'extra'=>true
    ),
    'K' => array(),
    'l' => array(
        'extra'=>true
    ),
    'L' => array(
        'extra'=>true
    ),
    'm' => new Mode('m','channel','bool',false, array(
            'privmsg' => function(&$d){
                $d['errno'] = 404;
                $d['errstr'] = ":You need voice (+v) ({$d['chan']->name})";
                if($d['chan']->hasMode('m'))
                    if(!$d['chan']->hasVoice($d['user']))
                        return false;
                return true;
            }
        )),
    'M' => array(),
    'n' => array(),
    'N' => array(),
    'o' => new Mode('i','channel','array',true),
    'O' => new Mode('O','channel','array',true),
    'p' => array(),
    'q' => new Mode('q','channel','array',true),
    'Q' => array(),
    'r' => array(),
    'R' => new Mode('R','channel','bool',false, array(
            'join' => function(&$d){
                $d['errno'] = 404;
                $d['errstr'] = ":You must be registered with services to join (+r)";
                if($d['chan']->hasMode('R') && !$d['user']->hasMode('r'))
                        return false;
                return true;
            }
        )),
    's' => array(),
    'S' => array(),
    't' => array(),
    'v' => new Mode('v','channel','array', true, array(
            'set' => function(&$d){
                $d['errstr'] = "-v";
                $d['errno'] = 482;
                if(!$d['chan']->isOp($d['user']))
                    return false;
                $d['errno'] = 431;
                if(!isset($d['extra']))
                    return false;
                $d['errstr'] = $d['extra'];
                $d['errno'] = 401;
                if(!$d['chan']->userInChan($d['extra']))
                    return false;
                return true;
            },
            'unset' => function(&$d){
                $d['errstr'] = "-v";
                if($d['user']->nick == $d['extra'])
                    return true;
                $d['errno'] = 482;
                if(!$d['chan']->isOp($d['user']))
                    return false;
                $d['errno'] = 431;
                if(!isset($d['extra']))
                    return false;
                $d['errstr'] = $d['extra'];
                $d['errno'] = 401;
                if(!$d['chan']->userInChan($d['extra']))
                    return false;
                return true;
            }
        )),
    'V' => new Mode('V','channel','bool', false, array(
            'invite' => function(&$d){
                $d['errno'] = 0;
                $d['errstr'] = false;
                if($d['chan']->hasMode('V') && !$d['chan']->isOp($d['user']))
                    return false;
                return true;
            }
        )),
    'z' => new Mode('z','channel','bool', false, array(
            'join' => function(&$d){
                $d['errno'] = 404;
                $d['errstr']  = ":You must be connected via SSL to join (+z)";
                if($d['chan']->hasMode('z') && !$d['user']->hasMode('z'))
                        return false;
                return true;
            }
        ))
);

?>
