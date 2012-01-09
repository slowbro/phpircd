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
            'connect' => function(&$user){
                if($user->ssl)
                    $user->setMode('z');
            }
    ))
);

$channelModes = array(
    'a' => new Mode('a','channel','array',true),
    'A' => array(),
    'b' => new Mode('b','channel','array',true, array(
            'join'=> function(&$u, &$c, &$en, &$es){
                $en = 404;
                $es = ": You are banned (+b) ({$c->name})";
                if($c->isBanned($u))
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
            'privmsg' => function(&$u, &$c, &$en, &$es){
                $en = 404;
                $es = ":You need voice (+v) ($c->name)";
                if($c->hasMode('m'))
                    if(!$c->hasVoice($u))
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
            'join' => function(&$u, &$c, &$en, &$es){
                $en = 404;
                $es = ":You must be registered with services to join (+r)";
                if($c->hasMode('R') && !$u->hasMode('r'))
                        return false;
                return true;
            }
        )),
    's' => array(),
    'S' => array(),
    't' => array(),
    'v' => new Mode('v','channel','array', true),
    'V' => new Mode('V','channel','bool', false, array(
            'invite' => function(&$u, &$c, &$en, &$es){
                $en = 0;
                $es = false;
                if($c->hasMode('V') && !$c->isOp($u))
                    return false;
                return true;
            }
        )),
    'z' => new Mode('z','channel','bool', false, array(
            'join' => function(&$u, &$c, &$en, &$es){
                $en = 404;
                $es = ":You must be connected via SSL to join (+z)";
                if($c->hasMode('z') && !$u->hasMode('z'))
                        return false;
                return true;
            }
        ))
);

?>
