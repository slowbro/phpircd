<?php

class ircd {

var $forbidden = array("newConnecntion", "process", "welcome", "error", "debug");
var $nickRegex = "/^[a-zA-Z\[\]\\\|^`_{}]{1}[a-zA-Z0-9\[\]\\|^`_{}]{0,}$/";
var $rnRegex = "/^[a-zA-Z\[\]\\\|^`_{} ]{1}[a-zA-Z0-9\[\]\\|^`_{} ]{0,}$/";

function newConnection($in, $user){
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
        $this->quit($user, $p);
        break;
        case 'pass':

        break;
        case 'user':
        //USER nick mode unused :Real Name
        if(count($e) < 5){
            $this->error('461', $user, 'USER');
            break;
        }
        $err = FALSE;
        while($err == FALSE){
            if(!$this->checkNick($e['1'])){
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
            if(!$this->checkRealName($rn)){
                $err = "$rn:Illegal characters.";
                continue;
            }
            break;
        }
        if($err){
            $this->error('432', $user,"$err");
        } else {
            unset($e['3']); //unused
            $user->username = $e['1'];
            $user->usermode = "";
            $user->realname = $rn;
            $user->namesx = FALSE;
            if($user->regbit ^ 1){
                $user->regbit += 1;
            }
            if($user->regbit == 3){
                $user->lastping = $user->lastpong = time();
                $user->registered = TRUE;
                $user->prefix = $user->nick."!".$user->username."@".$user->address;
                $this->welcome($user);
                        }
        }

        break;
        case 'nick':
        if(count($e) < 2){
            $this->error('431', $user);
            break;
        }
        $this->stripColon($e['1']);
        if(array_search(strtolower($e['1']), $core->_nicks) !== FALSE){
            $this->error('433', $user, $e['1']);
            break;
        }
        if($this->checkNick(@$e['1'])){
            $user->nick = substr($e['1'], 0, $core->config['ircd']['nicklen']);
            $core->_nicks[$user->id] = strtolower($e['1']);
            if($user->regbit ^ 2){
                $user->regbit +=2;
            }
            if($user->regbit == 3){
                $user->lastping = $user->lastpong = time();
                $user->registered = TRUE;
                $user->prefix = $user->nick."!".$user->username."@".$user->address;
                $this->welcome($user);
            }
        } else {
            $this->error('432', $user, $e['1'].":Illegal characters.");
        }
        break;
        default:
        $this->error('451', $user, $e['0']);
    }

}

function process($in, $user){
    global $core;
    $e = explode(" ", $in);
    $command = strtolower($e['0']);
    unset($e['0']);
    $params = implode (" ", $e);
    $user->lastpong = time();
    if(method_exists(__CLASS__,$command) && array_search($command, $this->forbidden) === FALSE){
        $this->$command($user, $params);
    } else {
        $this->error('421', $user, $command);
    }
}

function error($numeric, $user, $extra=""){
    global $core;
    $target = (empty($user->nick)?"*":$user->nick);
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
    $core->write($user->socket, $prefix.$message);
}

function welcome($user){
    global $core;
    $core->write($user->socket, ":{$core->servname} 001 {$user->nick} :Welcome to the {$core->network} IRC network, {$user->prefix}");
    $core->write($user->socket, ":{$core->servname} 002 {$user->nick} :Your host is {$core->servname}, running {$core->version}");
    $core->write($user->socket, ":{$core->servname} 003 {$user->nick} :This server was created {$core->createdate}");
    $core->write($user->socket, ":{$core->servname} 004 {$user->nick} {$core->servname} {$core->version} iowghraAsORTVSxNCWqBzvdHtGp lvhopsmntikrRcaqOALQbSeIKVfMCuzNTGj");
    $_005 = "";
    $core->write($user->socket, ":{$core->servname} 005 {$user->nick} CHANTYPES={$core->config['ircd']['chantypes']} PREFIX=(qaohv)~&@%+ NETWORK={$core->config['me']['network']} :are supported by this server");
    $this->lusers($user);
    $this->motd($user);
}

function join($user, $p=""){
    global $core;
    $joins = array();
    if(empty($p)){
        $this->error(461, $user, 'join');
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
            $this->error(403, $user, $chan);
            continue;
        }
        if(array_key_exists($chan, $core->_channels) === FALSE){
            $tpl = array();
            $tpl['users'] = array($user->id => "@@");
            $tpl['modes'] = NULL;
            $tpl['bans'] = array();
            $tpl['excepts'] = array();
            $tpl['invex'] = array();
            $tpl['topic'] = array("message" => 'default topic!', "changed" => time(), "nick" => 'phpircd');
            $core->_channels[$chan] = $tpl;
            $user->channels[] = $chan;
            $core->write($user->socket, ":{$user->prefix} JOIN $chan");
        } else {
            $core->_channels[$chan]['users'][$user->id] = '';
            $user->channels[] = $chan;
            foreach($core->_channels[$chan]['users'] as $i=>$u)
                $core->write($u->socket, ":{$user->prefix} JOIN $chan");
       }
        $this->topic($user, $chan);
        $this->names($user, $chan);
    }
}

function lusers($user, $p=""){
    global $core;
    $nick = $user->nick;
    $lusers = <<<EOM
:{$core->servname} 251 $nick :There are 2 users and 0 invisible on 1 servers
:{$core->servname} 252 $nick 0 :operator(s) online
:{$core->servname} 254 $nick 1 :channels formed
:{$core->servname} 255 $nick :I have 2 clients and 1 servers
:{$core->servname} 265 $nick :Current Local Users: 11  Max: 79
:{$core->servname} 266 $nick :Current Global Users: 113  Max: 130469
EOM;
    foreach(explode("\n", trim($lusers)) as $s){
        $core->write($user->socket, trim($s));
    }
}

function motd($user, $p=""){
    global $core;
    if(empty($p)){
        if(file_exists("motd.txt")){
            $core->write($user->socket, ":{$core->servname} 375 {$user->nick} :- {$core->servname} Message of the day -");
            $motd = file("motd.txt");
            foreach($motd as $value){
                $core->write($user->socket, ":{$core->servname} 372 {$user->nick} :- ".rtrim($value));
            }
            $core->write($user->socket, ":{$core->servname} 376 {$user->nick} :End of MOTD");
        } else {
            $this->error('422', $user);
        }
    }
}

function names($user, $p){
    global $core;
    $prefix = ":{$core->servname} 353 {$user->nick} ";
    if(empty($p)){
        foreach($core->_clients as $val){
            if(count($val->channels) == "0"){
                $names[] = $val->nick;
            }
        }
        $prefix .= "= * :";
    } else {
        $p = explode(" ", $p);
        if(array_key_exists($p['0'], $core->_channels) === FALSE){
            $core->write($user->socket, ":{$core->servname} 366 {$user->nick} {$p['0']} :End of /NAMES list.");
            return;
        }
        $chan = $p['0'];
        $names = $core->_channels[$chan]['users'];
        foreach($names as $k => $v){
                $c = $core->_clients[$k];
                $names[$k] = str_replace("@@", "@", $v).$c->nick;
        }
        if(!$user->namesx){
        
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
        $core->write($user->socket, ":{$core->servname} 366 {$user->nick} * :End of /NAMES list.");
        return;
    }
    $names = implode(" ", $names);
    $len = strlen($prefix);
    if($len+strlen($names) <= 510){
        $core->write($user->socket, $prefix.$names);
    } else {
        $max = 510 - $len;
        while(strlen($names) > 510){
        $nsub = substr($names, 0, $max-1);
        if($names[strlen($nsub)-1] != " " || !empty($names[strlen($nsub)-1])){
            $pos = strrpos($nsub, " ");
            $nsub = substr($nsub, 0, $pos);
        }
        $names = substr($names, strlen($nsub)+1);
        $core->write($user->socket, $prefix.$nsub);
        }
        $core->write($user->socket, $prefix.$names);
    }
    $core->write($user->socket, ":{$core->servname} 366 {$user->nick} ".(empty($chan)?"*":$chan)." :End of /NAMES list.");
}

function nick($user, $p){
    global $core;
    $this->stripColon($p);
    if(empty($p)){
        $this->error('461', $user, 'NICK');
        return;
    }
    if(array_search(strtolower($p), $core->_nicks) !== FALSE){
        $this->error('433', $user, $p);
        return;
    }
    if($user->nick == $p)
        return;
    if($this->checkNick($p)){
        $p = substr($p, 0, $core->config['ircd']['nicklen']);
        $core->write($user->socket, ":{$user->prefix} NICK $p");
        $user->nick = $p;
        $core->_nicks[$key] = strtolower($p);
        $oldprefix = $user->prefix;
        $user->prefix = $user->nick."!".$user->username."@".$user->address;
        var_dump($user->channels);
        foreach($user->channels as $chan){
            foreach($core->_channels[$chan]['users'] as $cid => $cnick){
                if($user->id != $cid){
                    $c = $core->_clients[$cid];
                    $core->write($c->socket, ":{$oldprefix} NICK $p");
                }
            } 
        }
    } else {
        $this->error('432', $user, $p.":"."Illegal characters.");
    }
}

function oper($user, $p){

}

function part($user, $p){
    global $core;
    $chans = explode(",", $p);
    foreach($chans as $k => $v){
        $x = explode(" ", $v, 2);
        $v = trim($x['0']);
        $reason = (empty($x['1'])?"":trim($x['1']));
        if(!array_key_exists($v, $core->_channels)){
            $this->error('403', $user, $v);
            return;
        }
        if(!array_key_exists($user->id, $core->_channels[$v]['users'])){
            $this->error('442', $user, $v);
            return;
        }
        foreach($core->_channels[$v]['users'] as $cid => $cnick){
            $c = $core->_clients[$cid];
            $core->write($c->socket, ":{$user->prefix} PART $v $reason");
        }
        unset($core->_channels[$v]['users'][$user->id]);
    }
}

function ping($user, $p, $e=false){
    global $core;
    if($e){
        $core->write($user->socket, "PING :$p");
        return;
    }
    if(empty($p)){
        $this->error('461', $user, 'PING');
        return;
    }
    $p = explode(" ", $p);
    if(count($p) == 1){
        $p = $p['0'];
        if(strpos($p, ":") === 0){
            $p = substr($p, 1);
        }
        $core->write($user->socket, ":{$core->servname} PONG {$core->servname} ".":$p");
        $user->lastpong = time();
    } else {
        //ping some server
    }
}

function pong($user, $p){
    global $core;
    //PONG :samecrap
    if(strpos($p, ":") === 0){
        $p = substr($p, 1);
    }
    if($p == $core->servname){ //respond to keepalive ping
        if($user->lastpong < $user->lastping){
            $user->lastpong = time();
        }
    }
}

function privmsg($user, $p){
    global $core;
    // target ?:message
    $e = explode(" ", $p);
    $chantypes = str_split($core->config['ircd']['chantypes']);
    $target = $e['0'];
    if($target[0] == ":"){
        //ERR_NORECIPIENT
        $this->error(411, $user);
        return;
    }
    if(count($e) < 2){
        //ERR_NOTEXTTOSEND
        $this->error(412, $user, $target);
        return;
    }
    if($target[0] == "$"){
        if($user->oper & 32){ //replace with actual oper bit
            //client is allowed to message $*
        } else {
            //ERR_NOSUCHNICK
            $this->error(401, $user, $target);
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
            $this->error(401, $user, $target);
            return;
        }
        if(array_key_exists($target, $core->_channels) === FALSE){
            //ERR_NOSUCHNICK (channel doesnt exist)
            $this->error(401, $user, $target);
            return;
        }
        if(array_search($target, $user->channels) === FALSE){
            //ERR_CANNOTSENDATOCHAN
            $this->error(404, $user, $target);
            return;
        }
    } else {
        $c = NULL;
        if(($key2 = array_search(strtolower($target), $core->_nicks)) === FALSE){
            //ERR_NOSUCHNICK (user doesnt exist)
            $this->error(401, $user, $target);
            return;
        }
        $c = $core->_clients[$key2];
    }
    $message = substr($p, strlen($target)+1);
    $message = ($message[0] == ":"?substr($message, 1):$message);
    if($is_channel){
        //send to whole channel
        foreach($core->_channels[$target]['users'] as $id => $u){
            if($id == $user->id){
                continue;
            }
            $c = $core->_clients[$id];
            $core->write($c->socket, ":{$user->prefix} PRIVMSG $target :$message");
        }
    } else {
        $core->write($c->socket, ":".$user->prefix." PRIVMSG ".$target." :$message");
    }
}

function protoctl($user, $p){
    global $core;
    if(empty($p)){
        $this->error(461, $user, 'protoctl');
        return;
    }
    if(strtolower($p) == "namesx"){
        $user->namesx = TRUE;
    }
}

function quit($user, $p="Quit: Leaving"){
    global $core;
    $core->write($user->socket, "ERROR: Closing Link: {$user->address} ($p)");
    foreach(@$user->channels as $chan){
        //alert the channel's occupants
        foreach($core->_channels[$chan]['users'] as $ck => $cu){
            $c = $core->_clients[$ck];
            $core->write($c->socket, ":{$user->prefix} QUIT $p");
        }
        unset($core->_channels[$chan]['users'][$user->id]);
    }
    $core->close($user);
    unset($core->_clients[$user->id]);
}

function topic($user, $p){
    global $core;
    if(empty($p)){
        $this->error(461, $user, 'topic');
        return;
    }
    $p = explode(" ", $p);
    if(array_key_exists($p['0'], $core->_channels) === FALSE){
        $this->error(403, $user, $p['0']);
        return;
    }
    if(array_search($p['0'], $user->channels) === FALSE){
        $this->error(442, $user, $p['0']);
        return;
    }
    if(count($p) == 1){
        $chan = $p['0'];
        $topic = $core->_channels[$chan]['topic'];
        if(empty($topic['message'])){
            $core->write($user->socket, ":{$core->servname} 331 $chan :No topic set.");
            return;
        }
        $core->write($user->socket, ":{$core->servname} 332 {$user->nick} $chan :{$topic['message']}");
        $core->write($user->socket, ":{$core->servname} 333 {$user->nick} $chan {$topic['nick']} {$topic['changed']}");
    } else {
        //change topic
    }
}

function user($user, $p){
    $this->error('462', $user);
}

function who($user, $p){
    
}

//utility methods

function checkNick(&$nick){
    global $core;
    if(empty($nick))
        return false;
    if(!preg_match($this->nickRegex, $nick))
        return false;
    $nick = substr($nick, 0, $core->config['ircd']['nicklen']);
    return true;
}

function checkRealName(&$nick){
    global $core;
    if(empty($nick))
        return false;
    if(!preg_match($this->rnRegex, $nick))
        return false;
    return true;
}

function stripColon(&$p){
    if($p[0] == ":")
        $p = substr($p, 1);
}

}// end class

?>
