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
    'a' => array(
        'extra'=>true,
        'type'=>'array'
    ),
    'A' => array(),
    'b' => array(
        'extra'=>true,
        'type'=>'array'
    ),
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
    'h' => array(
        'extra'=>true,
        'type'=>'array'
    ),
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
                    if(!$c->hasMode('v', $u->nick))
                        return false;
                return true;
            }
        )),
    'M' => array(),
    'n' => array(),
    'N' => array(),
    'o' => array(
        'extra'=>true,
        'type'=>'array'
    ),
    'O' => array(
        'extra'=>true
    ),
    'p' => array(),
    'q' => array(
        'extra'=>true,
        'type'=>'array'
    ),
    'Q' => array(),
    'r' => array(),
    'R' => new Mode('R','channel','bool',false, array(
            'join' => function(&$u, &$c, &$en, &$es){
                $en = 404;
                $es = ":You must be registered with services to join (+r)";
                if($c->hasMode('R'))
                    if(!$u->hasMode('r'))
                        return false;
                return true;
            }
        )),
    's' => array(),
    'S' => array(),
    't' => array(),
    'v' => new Mode('v','channel','array', true),
    'V' => array(),
    'z' => array()
);

?>
