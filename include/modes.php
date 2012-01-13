<?php

$userModes = array(
    'a' => new Mode('a','user',Mode::TYPE_D),
    'A' => new Mode('A','user',Mode::TYPE_D),
    'b' => new Mode('b','user',Mode::TYPE_D),
    'B' => new Mode('B','user',Mode::TYPE_D),
    'C' => new Mode('C','user',Mode::TYPE_D),
    'd' => new Mode('d','user',Mode::TYPE_D),
    'g' => new Mode('g','user',Mode::TYPE_D),
    'G' => new Mode('G','user',Mode::TYPE_D),
    'h' => new Mode('h','user',Mode::TYPE_D),
    'H' => new Mode('H','user',Mode::TYPE_D),
    'i' => new Mode('i','user',Mode::TYPE_D),
    'N' => new Mode('N','user',Mode::TYPE_D),
    'o' => new Mode('o','user',Mode::TYPE_D),
    'O' => new Mode('O','user',Mode::TYPE_D),
    'p' => new Mode('p','user',Mode::TYPE_D),
    'q' => new Mode('q','user',Mode::TYPE_D),
    'r' => new Mode('r','user',Mode::TYPE_D),
    'R' => new Mode('R','user',Mode::TYPE_D),
    's' => new Mode('s','user',Mode::TYPE_C),
    't' => new Mode('t','user',Mode::TYPE_D),
    'T' => new Mode('T','user',Mode::TYPE_D),
    'w' => new Mode('w','user',Mode::TYPE_D),
    'W' => new Mode('W','user',Mode::TYPE_D),
    'x' => new Mode('x','user',Mode::TYPE_D, array(
            'connect' => function(&$d){
                if($d['ircd']->config['ircd']['hostmask'] == "on")
                    $d['user']->maskHost();
            }
    )),
    'z' => new Mode('z','user',Mode::TYPE_D, array(
            'connect' => function(&$d){
                if($d['user']->ssl)
                    $d['user']->setModes('+z');
            },
            'set' => function(&$d){
                if(!$d['user']->ssl)
                    return false;
                return true;
            },
            'unset' => function(&$d){
                if($d['user']->ssl)
                    return false;
                return true;
            }
    ))
);

$channelModes = array(
    'a' => new Mode('a','channel',Mode::TYPE_P, array(
            'set' => function(&$d){
                $d['errno'] = 499;
                if(!$d['chan']->hasPrivs($d['user'], Mode::CHANNEL_MODE_a))
                    return false;
                return true;
            }
        ), array(
            'weight'=>10,
            'prefix'=>'&',
            'privs'=> Mode::CHANNEL_AOP | Mode::CHANNEL_OP | Mode::CHANNEL_HOP | Mode::CHANNEL_VOICE
        )),
    'A' => new Mode('A','channel',Mode::TYPE_D),
    'b' => new Mode('b','channel',Mode::TYPE_A, array(
            'join'=> function(&$d){
                $d['errno'] = 404;
                $d['errstr'] = ": You are banned (+b) ({$d['chan']->name})";
                if($d['chan']->isBanned($d['user']))
                    return false;
                return true;
            }
        )),
    'c' => new Mode('c','channel',Mode::TYPE_D),
    'C' => new Mode('C','channel',Mode::TYPE_D),
    'e' => new Mode('e','channel',Mode::TYPE_A),
    'f' => new Mode('f','channel',Mode::TYPE_C),
    'G' => new Mode('G','channel',Mode::TYPE_D),
    'h' => new Mode('h','channel',Mode::TYPE_P, array(), array(
            'weight'=>2,
            'prefix'=>'%',
            'privs'=> Mode::CHANNEL_HOP | Mode::CHANNEL_VOICE
        )),
    'H' => new Mode('H','channel',Mode::TYPE_D),
    'i' => new Mode('i','channel',Mode::TYPE_D),
    'I' => new Mode('I','channel',Mode::TYPE_A),
    'J' => new Mode('J','channel',Mode::TYPE_C),
    'k' => new Mode('k','channel',Mode::TYPE_B),
    'K' => new Mode('K','channel',Mode::TYPE_D),
    'l' => new Mode('l','channel',Mode::TYPE_C),
    'L' => new Mode('L','channel',Mode::TYPE_C),
    'm' => new Mode('m','channel',Mode::TYPE_D, array(
            'privmsg' => function(&$d){
                $d['errno'] = 404;
                $d['errstr'] = ":You need voice (+v) ({$d['chan']->name})";
                if($d['chan']->hasMode('m'))
                    if(!$d['chan']->hasPrivs($d['user'], Mode::CHANNEL_VOICE))
                        return false;
                return true;
            }
        )),
    'M' => new Mode('M','channel',Mode::TYPE_D),
    'n' => new Mode('n','channel',Mode::TYPE_D),
    'N' => new Mode('N','channel',Mode::TYPE_D),
    'o' => new Mode('o','channel',Mode::TYPE_P, array(
            'set' => function(&$d){
                $d['errno'] = 482;
                if(!$d['chan']->hasPrivs($d['user'], Mode::CHANNEL_MODE_o))
                    return false;
                return true;
            }
        ), array(
            'weight'=>5,
            'prefix'=>'@',
            'privs'=>Mode::CHANNEL_OP | Mode::CHANNEL_HOP | Mode::CHANNEL_VOICE
        )),
    'O' => new Mode('O','channel',Mode::TYPE_D),
    'p' => new Mode('p','channel',Mode::TYPE_D),
    'q' => new Mode('q','channel',Mode::TYPE_P, array(), array(
            'weight'=>20,
            'prefix'=>'~',
            'privs'=>Mode::CHANNEL_QOP | Mode::CHANNEL_AOP | Mode::CHANNEL_OP | Mode::CHANNEL_HOP | Mode::CHANNEL_VOICE
        )),
    'Q' => new Mode('Q','channel',Mode::TYPE_D),
    'r' => new Mode('r','channel',Mode::TYPE_D),
    'R' => new Mode('R','channel',Mode::TYPE_D, array(
            'join' => function(&$d){
                $d['errno'] = 404;
                $d['errstr'] = ":You must be registered with services to join (+r)";
                if($d['chan']->hasMode('R') && !$d['user']->hasMode('r'))
                        return false;
                return true;
            }
        )),
    's' => new Mode('s','channel',Mode::TYPE_D),
    'S' => new Mode('S','channel',Mode::TYPE_D),
    't' => new Mode('t','channel',Mode::TYPE_D),
    'u' => new Mode('u','channel',Mode::TYPE_D),
    'v' => new Mode('v','channel',Mode::TYPE_P, array(
            'set' => function(&$d){
                $d['errstr'] = "+v";
                $d['errno'] = 482;
                if(!$d['chan']->hasPrivs($d['user'], Mode::CHANNEL_MODE_v))
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
                if(!$d['chan']->hasPrivs($d['user'], Mode::CHANNEL_MODE_v))
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
        ), array(
            'weight'=>1,
            'prefix'=>'+',
            'privs'=> Mode::CHANNEL_VOICE
        )),
    'V' => new Mode('V','channel',Mode::TYPE_D, array(
            'invite' => function(&$d){
                $d['errno'] = 0;
                $d['errstr'] = false;
                if($d['chan']->hasMode('V') && !$d['chan']->hasPrivs($d['user'], Mode::CHANNEL_MODE_V))
                    return false;
                return true;
            }
        )),
    'z' => new Mode('z','channel',Mode::TYPE_D, array(
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
