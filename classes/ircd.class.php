<?php

class ircd {

var $forbidden = array("newConnecntion", "process", "welcome", "error", "debug");

function newConnection($in, $key){
    global $core;
    $e = explode(" ", $in);
        $command = strtolower($e['0']);
    switch(@$command){
        case 'quit':
        $p = $e;
        unset($p['0']);
        if(count($p) > 1){
            $p = implode(" ", $p);
        } elseif(count($p) == 0){
            $p = "";
        } else {
            $p = $p['1'];
        }
        if($p[0] == ":"){
            $p = substr($p, 1);
        }
        $this->quit($key, $p);
        break;
        case 'pass':

        break;
        case 'user':
        //USER nick mode unused :Real Name
        if(count($e) < 5){
            $this->error('461', $key, 'USER');
            break;
        }
        $err = FALSE;
        while($err == FALSE){
        if(!preg_match("/^[a-zA-Z\[\]\\\|\^\`_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`_\{\}]{0,}$/", $e['1'])){
            $err = "{$e['1']}:Illegal characters.";
            continue;
        }
        //if(modes n shit){
            
        //}
        if(count($e) == 5){
            $rn = $e['4'];
            if($rn[0] == ":"){
                                $rn = substr($rn, 1);
                        }
        } else {
            for($i=4;$i < count($e);$i++){
                $rn[] = $e[$i];
            }
            $rn = implode(" ", $rn);
            if($rn[0] == ":"){
                $rn = substr($rn, 1);
            }
        }
        if(!preg_match("/^[a-zA-Z\[\]\\\|\^\`_\{\} ]{1}[a-zA-Z0-9\[\]\\\|\^\`_\{\} ]{0,19}$/", $rn)){
            $err = "$rn:Illegal characters.";
            continue;
        }
        break;
        }
        if($err){
            $this->error('432', $key,"$err");
        } else {
            unset($e['3']); //unused
            $core->_clients[$key]['username'] = substr($e['1'], 0, 16);
            $core->_clients[$key]['usermode'] = "";
            $core->_clients[$key]['realname'] = $rn;
            $core->_clients[$key]['channels'] = array();
            $core->_clients[$key]['namesx'] = FALSE;
            if($core->_clients[$key]['regbit'] ^ 1){
                $core->_clients[$key]['regbit'] += 1;
            }
            if($core->_clients[$key]['regbit'] == 3){
                $core->_clients[$key]['lastping'] = time();
                $core->_clients[$key]['lastpong'] = $core->_clients[$key]['lastping'];
                                $core->_clients[$key]['registered'] = TRUE;
                $core->_clients[$key]['prefix'] = $core->_clients[$key]['nick']."!".$core->_clients[$key]['username']."@".$core->_clients[$key]['address'];
                $this->welcome($key);
                        }
        }

        break;
        case 'nick':
        if(count($e) < 2){
            $this->error('431', $key);
            break;
        }
        if(array_search(strtolower($e['1']), $core->_nicks) !== FALSE){
            $this->error('433', $key, $e['1']);
            break;
        }
        if(preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,}$/", $e['1'])){
            $core->_clients[$key]['nick'] = substr($e['1'], 0, $core->config['ircd']['nicklen']);
            $core->_nicks[$key] = strtolower($e['1']);
            if($core->_clients[$key]['regbit'] ^ 2){
                $core->_clients[$key]['regbit'] +=2;
            }
            if($core->_clients[$key]['regbit'] == 3){
                $core->_clients[$key]['lastping'] = time();
                $core->_clients[$key]['lastpong'] = $core->_clients[$key]['lastping'];
                $core->_clients[$key]['registered'] = TRUE;
                $core->_clients[$key]['prefix'] = $core->_clients[$key]['nick']."!".$core->_clients[$key]['username']."@".$core->_clients[$key]['address'];
                $this->welcome($key);
            }
        } else {
            $this->error('432', $key, $e['1'].":Illegal characters.");
        }
        break;
        default:
        $this->error('451', $key, $e['0']);
    }

}

function process($in, $key){
    global $core;
    $e = explode(" ", $in);
    $command = strtolower($e['0']);
    unset($e['0']);
    $params = implode (" ", $e);
    $core->_clients[$key]['lastpong'] = time();
    if(method_exists(__CLASS__,$command) && array_search($command, $this->forbidden) === FALSE){
        $this->$command($key, $params);
    } else {
        $this->error('421', $key, $command);
    }
}

function error($numeric, $key, $extra=""){
    global $core;
    $socket = $core->_client_sock[$key];
    $target = (empty($core->_clients[$key]['nick'])?"*":$core->_clients[$key]['nick']);
    $prefix = ":".$core->servname." ".$numeric." ".$target." ";
    switch($numeric){
    case 401:
    $message = "$extra :No such nick/channel.";
    break;
    case 402:
    $message = "$extra :No such server.";
    break;
    case 403:
    $message = "$extra :No such channel.";
    break;
    case 404:
    $message = $extra." :Can not send to channel.";
    break;
    case 405:
    $message = "$extra :You have joined too many channels.";
    break;
    case 406:
    $message = "$extra :There was no such nickname.";
    break;
    case 407:
    $message = "$extra :Too many targets.";
    break;
    case 408:
    $message = "$extra :No such service.";
    break;
    case 409:
    $message = ":No origin specified.";
    break;
    //410 doesn't exist
    case 411:
    $message = ":No recipient given.";
    break;
    case 412:
    $message = ":No text to send.";
    break;
    case 421:
    $message = strtoupper($extra)." :Unknown command.";
    break;
    case 422:
    $message = ":MOTD file missing.";
    break;
    case 431:
    $message = ":No nickname given.";
    break;
    case 432:
    $extra = explode(":",$extra);
    $message = $extra['0']." :Erroneous nickname".($extra['1']?": ".$extra['1']:"");
    break;
    case 433:
    $message = $extra." :Nickname already in use.";
    break;
    case 442:
    $message = $extra." :You're not in that channel.";
    break;
    case 451:
    $message = $extra." :You have not registered.";
    break;
    case '461':
    $message = strtoupper($extra)." :Not enough parameters.";
    break;
    case '462':
    $message = ":You may not register more than once.";
    }
    $core->write($socket, $prefix.$message);
}

function welcome($key){
    global $core;
    $socket = $core->_client_sock[$key];
    $cl = $core->_clients[$key];
    $core->write($socket, ":{$core->servname} 001 {$cl['nick']} :Welcome to the {$core->network} IRC network, {$cl['prefix']}");
    $core->write($socket, ":{$core->servname} 002 {$cl['nick']} :Your host is {$core->servname} running {$core->version}");
    $core->write($socket, ":{$core->servname} 003 {$cl['nick']} :This server was created {$core->createdate}");
    $core->write($socket, ":{$core->servname} 004 {$cl['nick']} {$core->servname} {$core->version} <umodes> <chanmodes>");
    $core->write($socket, ":{$core->servname} 005 {$cl['nick']} CHANTYPES={$core->config['ircd']['chantypes']} PREFIX=(qaohv)~&@%+ NAMESX :are supported by this server");
    $this->motd($key);
}

function join($key, $p=""){
    global $core;
    $joins = array();
    if(empty($p)){
        $this->error(461, $key, 'join');
        return;
    }
    $ps = explode(" ", $p);
    if(count($ps) > 1){ //we have channel keys
        $chans = explode(",", $ps['0']);
        $keys = explode(",", $ps['1']);
        foreach($chans as $k => $v){
            $joins[] = array($v, @$keys[$k]);
        }
    } else {
        $chans = explode(",", $p);
        foreach($chans as $k => $v){
            $joins[] = array($v, '');
        }
    }
    foreach($joins as $value){
        $chan = $value['0'];
        $kee = @$value['1'];
        if(array_search($chan[0], str_split($core->config['ircd']['chantypes'])) === FALSE){
            $this->error(403, $key, $chan);
            continue;
        }
        if(array_key_exists($chan, $core->_channels) === FALSE){
            $tpl = array();
            $tpl['users'] = array($key => "@@".$core->_clients[$key]['nick']);
            $tpl['modes'] = NULL;
            $tpl['bans'] = array();
            $tpl['excepts'] = array();
            $tpl['invex'] = array();
            $tpl['topic'] = array("message" => NULL, "changed" => NULL, "nick" => NULL);
            $core->_channels[$chan] = $tpl;
            $core->_clients[$key]['channels'][] = $chan;
            $core->write($core->_client_sock[$key], ":{$core->_clients[$key]['prefix']} JOIN $chan");
        } else {
            $core->_channels[$chan]['users'][$key] = $core->_clients[$key]['nick'];
            $core->_clients[$key]['channels'][] = $chan;
            $core->write($core->_client_sock[$key], ":{$core->_clients[$key]['prefix']} JOIN $chan");
            if(!empty($core->_channels[$chan]['topic']['message'])){
                $this->topic($key, $chan);
            }
        }
    }
}

function lusers($key, $p=""){
    
}

function motd($key, $p=""){
    global $core;
    $socket = $core->_client_sock[$key];
    $cl = $core->_clients[$key];
    if(empty($p)){
        if(file_exists("motd.txt")){
            $core->write($socket, ":{$core->servname} 375 {$cl['nick']} :- {$core->servname} Message of the day -");
            $motd = file("motd.txt");
            foreach($motd as $value){
                $core->write($socket, ":{$core->servname} 372 {$cl['nick']} :- ".rtrim($value));
            }
            $core->write($socket, ":{$core->servname} 376 {$cl['nick']} :End of MOTD");
        } else {
            $this->error('422', $key);
        }
    }
}

function names($key, $p){
    global $core;
    $socket = $core->_client_sock[$key];
    $cl = $core->_clients[$key];
    $prefix = ":{$core->servname} 353 {$cl['nick']} ";
    if(empty($p)){
        foreach($core->_clients as $val){
            if(count($val['channels']) == "0"){
                $names[] = $val['nick'];
            }
        }
        $prefix .= "= * :";
    } else {
        $p = explode(" ", $p);
        if(array_key_exists($p['0'], $core->_channels) === FALSE){
            $core->write($socket, ":{$core->servname} 366 {$cl['nick']} {$p['0']} :End of /NAMES list.");
            return;
        }
        $chan = $p['0'];
        $names = $core->_channels[$chan]['users'];
        foreach($names as $k => $v){
            if(strpos($v, "@@") !== FALSE){
                $names[$k] = str_replace("@@", "@", $v);
            }
        }
        if(!$cl['namesx']){
        
        }
        if(array_search("p",str_split($core->_channels[$chan]['modes'])) !== FALSE){
            $prefix .= "* $chan :";
        } elseif(array_search("s",str_split($core->_channels[$chan]['modes'])) !== FALSE){
            $prefix .= "@ $chan :";
        } else {
            $prefix .= "= $chan :";
        }
    }
    if(count(@$names) == 0){
        $core->write($socket, ":{$core->servname} 366 {$cl['nick']} * :End of /NAMES list.");
        return;
    }
    $names = implode(" ", $names);
    $len = strlen($prefix);
    if($len+strlen($names) <= 510){
        $core->write($socket, $prefix.$names);
    } else {
        $max = 510 - $len;
        while(strlen($names) > 510){
        $nsub = substr($names, 0, $max-1);
        if($names[strlen($nsub)-1] != " " || !empty($names[strlen($nsub)-1])){
            $pos = strrpos($nsub, " ");
            $nsub = substr($nsub, 0, $pos);
        }
        $names = substr($names, strlen($nsub)+1);
        $core->write($socket, $prefix.$nsub);
        }
        $core->write($socket, $prefix.$names);
    }
    $core->write($socket, ":{$core->servname} 366 {$cl['nick']} ".(empty($chan)?"*":$chan)." :End of /NAMES list.");
    return;
}

function nick($key, $p){
    global $core;
    $socket = $core->_client_sock[$key];
    if(empty($p)){
        $this->error('461', $key, 'NICK');
        return;
    }
    if(array_search(strtolower($p), $core->_nicks) !== FALSE){
        $this->error('433', $key, $p);
        return;
    }
    if(preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,}$/", $p)){
        $p = substr($p, 0, $core->config['ircd']['nicklen']);
        $core->write($socket, ":{$core->_clients[$key]['prefix']} NICK $p");
        $core->_clients[$key]['nick'] = $p;
        $core->_nicks[$key] = strtolower($p);
        $core->_clients[$key]['prefix'] = $core->_clients[$key]['nick']."!".$core->_clients[$key]['username']."@".$core->_clients[$key]['address'];
        //foreach($core->_clients[$key]['channels'] as $key => $value){
        //  alert the channel's occupants
        //}
    } else {
        $this->error('432', $key, $p.":"."Illegal characters.");
    }
}

function ping($key, $p, $e=false){
    global $core;
    $socket = $core->_client_sock[$key];
    if($e){
        $core->write($socket, "PING :$p");
        return;
    }
    if(empty($p)){
        $this->error('461', $key, 'PING');
        return;
    }
    $p = explode(" ", $p);
    if(count($p) == 1){
        $p = $p['0'];
        if(strpos($p, ":") === 0){
                    $p = substr($p, 1);
            }
        $core->write($socket, ":{$core->servname} PONG {$core->servname} ".":$p");
        $core->_clients[$key]['lastpong'] = time();
    } else {
        //ping some server
    }
}

function pong($key, $p){
    global $core;
        $socket = $core->_client_sock[$key];
    //PONG :samecrap
    if(strpos($p, ":") === 0){
        $p = substr($p, 1);
    }
    if($p == $core->servname){ //respond to keepalive ping
        if($core->_clients[$key]['lastpong'] < $core->_clients[$key]['lastping']){
            $core->_clients[$key]['lastpong'] = time();
        }
    }
}

function privmsg($key, $p){
    global $core;
    $socket = $core->_client_sock[$key];
    // target ?:message
    $e = explode(" ", $p);
    $chantypes = str_split($core->config['ircd']['chantypes']);
    $target = $e['0'];
    if($target[0] == ":"){
        //ERR_NORECIPIENT
        $this->error(411, $key);
        return;
    }
    if(count($e) < 2){
        //ERR_NOTEXTTOSEND
        $this->error(412, $key, $target);
        return;
    }
    if($target[0] == "$"){
        if($core->_clients[$key]['oper'] & 32){ //replace with actual oper bit
            //client is allowed to message $*
        } else {
            //ERR_NOSUCHNICK
            $this->error(401, $key, $target);
            return;
        }
    }
    $is_channel = FALSE;
    if(array_search($target[0], $chantypes) !== FALSE){
        $is_channel = TRUE;
    }
    if($is_channel){
        if(!preg_match("/[\x01-\x07\x08-\x09\x0B-\x0C\x0E-\x1F\x21-\x2B\x2D-\x39\x3B-\xFF]{1,}/", $target)){
            //ERR_NOSUCHNICK (illegal characters)
            $this->error(401, $key, $target);
            return;
        }
        if(array_key_exists($target, $core->_channels) === FALSE){
            //ERR_NOSUCHNICK (channel doesnt exist)
            $this->error(401, $key, $target);
            return;
        }
        if(array_search($target, $core->_clients[$key]['channels']) === FALSE){
            //ERR_CANNOTSENDATOCHAN
            $this->error(404, $key, $target);
            return;
        }
    } else {
        $sock = NULL;
        if(($key2 = array_search(strtolower($target), $core->_nicks)) === FALSE){
            //ERR_NOSUCHNICK (user doesnt exist)
            $this->error(401, $key, $target);
            return;
        }
        $sock = $core->_client_sock[$key2];
    }
    $message = substr($p, strlen($target)+1);
    $message = ($message[0] == ":"?substr($message, 1):$message);
    if($is_channel){
        //send to whole channel
        foreach($core->_channels[$target]['users'] as $k => $v){
            if($k == $key){
                continue;
            }
            $cl = $core->_clients[$k];
            $core->write($core->_client_sock[$k], ":{$core->_clients[$key]['prefix']} PRIVMSG $target :$message");
        }
    } else {
        $core->write($sock, ":".$core->_clients[$key]['prefix']." PRIVMSG ".$target." :$message");
    }
}

function protoctl($key, $p){
    global $core;
    if(empty($p)){
        $this->error(461, $key, 'protoctl');
        return;
    }
    if(strtolower($p) == "namesx"){
        $core->_clients[$key]['namesx'] = TRUE;
    }
}

function quit($key, $p){
    global $core;
    $socket = $core->_client_sock[$key];
    $core->write($socket, "ERROR: Closing Link: {$core->_clients[$key]['address']} ($p)");
    $core->close($key);
    //foreach($core->_clients[$key]['channels'] as $key => $value){
        //alert the channel's occupants
    //}
}

function topic($key, $p){
    global $core;
    $socket = $core->_client_sock[$key];
    if(empty($p)){
        $this->error(461, $key, 'topic');
        return;
    }
    $p = explode(" ", $p);
    if(array_key_exists($p['0'], $core->_channels) === FALSE){
        $this->error(403, $key, $p['0']);
        return;
    }
    if(array_search($p['0'], $core->_clients[$key]['channels']) === FALSE){
        $this->error(442, $key, $p['0']);
        return;
    }
    if(count($p) == 1){
        $chan = $p['0'];
        $topic = $core->_channels[$chan]['topic'];
        if(empty($topic['message'])){
            $core->write($socket, ":{$core->servname} 331 $chan :No topic set.");
            return;
        }
        $core->write($socket, ":{$core->servname} 332 $chan :{$topic['message']}");
        $core->write($socket, ":{$core->servname} 333 $chan {$topic['name']} {$topic['changed']}");
    } else {
        //change topic
        
    }
}

function user($key, $p){
    $this->error('462', $key);
}

function who($key, $p){
    
}

}// end class

?>
