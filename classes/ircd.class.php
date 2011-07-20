<?php

class ircd extends core {

var $forbidden = array("newConnecntion", "process", "welcome", "error", "debug");
var $nickRegex = "/^[a-zA-Z\[\]\\\|^`_{}]{1}[a-zA-Z0-9\[\]\\|^`_{}]{0,}$/";
var $rnRegex = "/^[a-zA-Z\[\]\\\|^`_{} ]{1}[a-zA-Z0-9\[\]\\|^`_{} ]{0,}$/";

function newConnection($in, $user){
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
        if(array_search(strtolower($e['1']), $this->_nicks) !== FALSE){
            $this->error('433', $user, $e['1']);
            break;
        }
        if($this->checkNick(@$e['1'])){
            $user->nick = substr($e['1'], 0, $this->config['ircd']['nicklen']);
            $this->_nicks[$user->id] = strtolower($e['1']);
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
    $target = (empty($user->nick)?"*":$user->nick);
    $prefix = ":".$this->servname." ".$numeric." ".$target." ";
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
    $user->send($prefix.$message);
}

function welcome($user){
    $user->send(":{$this->servname} 001 {$user->nick} :Welcome to the {$this->network} IRC network, {$user->prefix}");
    $user->send(":{$this->servname} 002 {$user->nick} :Your host is {$this->servname}, running {$this->version}");
    $user->send(":{$this->servname} 003 {$user->nick} :This server was created {$this->createdate}");
    $user->send(":{$this->servname} 004 {$user->nick} {$this->servname} {$this->version} iowghraAsORTVSxNCWqBzvdHtGp lvhopsmntikrRcaqOALQbSeIKVfMCuzNTGj");
    $_005 = "";
    $user->send(":{$this->servname} 005 {$user->nick} CHANTYPES={$this->config['ircd']['chantypes']} PREFIX=(qaohv)~&@%+ NETWORK={$this->config['me']['network']} :are supported by this server");
    $this->lusers($user);
    $this->motd($user);
}

function join($user, $p=""){
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
        if(array_search($chan[0], str_split($this->config['ircd']['chantypes'])) === FALSE){
            $this->error(403, $user, $chan);
            continue;
        }
        if(array_key_exists($chan, $this->_channels) === FALSE){
            $nchan = new Channel($this->channel_num++, $chan);
            $nchan->addUser($user, "@@");
            $nchan->setTopic($user, "default topic!");
            $this->_channels[$nchan->name] = $nchan;
            $user->addChannel($nchan);
            $user->send(":{$user->prefix} JOIN $chan");
        } else {
            $this->_channels[$chan]->addUser($user);
            $user->addChannel($this->_channels[$chan]);
            $this->_channels[$chan]->send(":{$user->prefix} JOIN $chan");
       }
        $this->topic($user, $chan);
        $this->names($user, $chan);
    }
}

function lusers($user, $p=""){
    $nick = $user->nick;
    $lusers = <<<EOM
:{$this->servname} 251 $nick :There are 2 users and 0 invisible on 1 servers
:{$this->servname} 252 $nick 0 :operator(s) online
:{$this->servname} 254 $nick 1 :channels formed
:{$this->servname} 255 $nick :I have 2 clients and 1 servers
:{$this->servname} 265 $nick :Current Local Users: 11  Max: 79
:{$this->servname} 266 $nick :Current Global Users: 113  Max: 130469
EOM;
    foreach(explode("\n", trim($lusers)) as $s){
        $user->send(trim($s));
    }
}

function motd($user, $p=""){
    if(empty($p)){
        if(file_exists("motd.txt")){
            $user->send(":{$this->servname} 375 {$user->nick} :- {$this->servname} Message of the day -");
            $motd = file("motd.txt");
            foreach($motd as $value){
                $user->send(":{$this->servname} 372 {$user->nick} :- ".rtrim($value));
            }
            $user->send(":{$this->servname} 376 {$user->nick} :End of MOTD");
        } else {
            $this->error('422', $user);
        }
    }
}

function names($user, $p){
    $prefix = ":{$this->servname} 353 {$user->nick} ";
    if(empty($p)){
        foreach($this->_clients as $val){
            if(count($val->channels) == "0"){
                $names[] = $val->nick;
            }
        }
        $prefix .= "= * :";
    } else {
        $p = explode(" ", $p);
        if(array_key_exists($p['0'], $this->_channels) === FALSE){
            $user->send(":{$this->servname} 366 {$user->nick} {$p['0']} :End of /NAMES list.");
            return;
        }
        $chan = $this->_channels[$p['0']];
        $names = $chan->users;
        foreach($names as $k => $v){
                $names[$k] = str_replace("@@", "@", $v).$this->_clients[$k]->nick;
        }
        if(!$user->namesx){
        
        }
        if(array_search("p",str_split($chan->modes)) !== FALSE){
            $prefix .= "* {$chan->name} :";
        } elseif(array_search("s",str_split($chan->modes)) !== FALSE){
            $prefix .= "@ {$chan->name} :";
        } else {
            $prefix .= "= {$chan->name} :";
        }
    }
    if(count(@$names) == 0){
        $user->send(":{$this->servname} 366 {$user->nick} * :End of /NAMES list.");
        return;
    }
    $names = implode(" ", $names);
    $len = strlen($prefix);
    if($len+strlen($names) <= 510){
        $user->send($prefix.$names);
    } else {
        $max = 510 - $len;
        while(strlen($names) > 510){
        $nsub = substr($names, 0, $max-1);
        if($names[strlen($nsub)-1] != " " || !empty($names[strlen($nsub)-1])){
            $pos = strrpos($nsub, " ");
            $nsub = substr($nsub, 0, $pos);
        }
        $names = substr($names, strlen($nsub)+1);
        $user->send($prefix.$nsub);
        }
        $user->send($prefix.$names);
    }
    $user->send(":{$this->servname} 366 {$user->nick} ".(empty($chan->nick)?"*":$chan->nick)." :End of /NAMES list.");
}

function nick($user, $p){
    $this->stripColon($p);
    if(empty($p)){
        $this->error('461', $user, 'NICK');
        return;
    }
    if(array_search(strtolower($p), $this->_nicks) !== FALSE){
        $this->error('433', $user, $p);
        return;
    }
    if($user->nick == $p)
        return;
    if($this->checkNick($p)){
        $p = substr($p, 0, $this->config['ircd']['nicklen']);
        $user->send(":{$user->prefix} NICK $p");
        $user->nick = $p;
        $this->_nicks[$user->id] = strtolower($p);
        $oldprefix = $user->prefix;
        $user->prefix = $user->nick."!".$user->username."@".$user->address;
        foreach($user->channels as $chan){
                $this->_channels[$chan]->send(":{$oldprefix} NICK $p", $user);
        }
    } else {
        $this->error('432', $user, $p.":"."Illegal characters.");
    }
}

function oper($user, $p){

}

function part($user, $p){
    $chans = explode(",", $p);
    foreach($chans as $k => $v){
        $x = explode(" ", $v, 2);
        $v = trim($x['0']);
        $reason = (empty($x['1'])?"":trim($x['1']));
        if(!array_key_exists($v, $this->_channels)){
            $this->error('403', $user, $v);
            return;
        }
        if(!array_key_exists($user->id, $this->_channels[$v]->users)){
            $this->error('442', $user, $v);
            return;
        }
        $this->_channels[$v]->send(":{$user->prefix} PART $v $reason");
        $this->_channels[$v]->removeUser($user);
        $user->removeChannel($this->_channels[$v]);
    }
}

function ping($user, $p, $e=false){
    if($e){
        $user->send("PING :$p");
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
        $user->send(":{$this->servname} PONG {$this->servname} ".":$p");
        $user->lastpong = time();
    } else {
        //ping some server
    }
}

function pong($user, $p){
    //PONG :samecrap
    if(strpos($p, ":") === 0){
        $p = substr($p, 1);
    }
    if($p == $this->servname){ //respond to keepalive ping
        if($user->lastpong < $user->lastping){
            $user->lastpong = time();
        }
    }
}

function privmsg($user, $p){
    // target ?:message
    $e = explode(" ", $p);
    $chantypes = str_split($this->config['ircd']['chantypes']);
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
    $is_channel = (array_search($target[0], $chantypes) !== FALSE?TRUE:FALSE);
    if($is_channel){
        if(!preg_match("/[\x01-\x07\x08-\x09\x0B-\x0C\x0E-\x1F\x21-\x2B\x2D-\x39\x3B-\xFF]{1,}/", $target)){
            //ERR_NOSUCHNICK (illegal characters)
            $this->error(401, $user, $target);
            return;
        }
        if(array_key_exists($target, $this->_channels) === FALSE){
            //ERR_NOSUCHNICK (channel doesnt exist)
            $this->error(401, $user, $target);
            return;
        }
        if(array_search($target, $user->channels) === FALSE){
            //ERR_CANNOTSENDATOCHAN
            $this->error(404, $user, $target);
            return;
        }
        $message = substr($p, strlen($target)+1);
        $message = ($message[0] == ":"?substr($message, 1):$message);
        //send to whole channel minus yourself
        $this->_channels[$target]->send(":{$user->prefix} PRIVMSG $target :$message", $user);
    } else {
        if(($key2 = array_search(strtolower($target), $this->_nicks)) === FALSE){
            //ERR_NOSUCHNICK (user doesnt exist)
            $this->error(401, $user, $target);
            return;
        }
        $message = substr($p, strlen($target)+1);
        $message = ($message[0] == ":"?substr($message, 1):$message);
        $this->_clients[$key2]->send(":".$user->prefix." PRIVMSG ".$target." :$message");
    }
}

function protoctl($user, $p){
    if(empty($p)){
        $this->error(461, $user, 'protoctl');
        return;
    }
    if(strtolower($p) == "namesx"){
        $user->namesx = TRUE;
    }
}

function quit($user, $p="Leaving"){
    $user->send("ERROR: Closing Link: {$user->address} ($p)");
    foreach(@$user->channels as $chan){
        $this->_channels[$chan]->send(":{$user->prefix} QUIT :Quit: $p");
        $this->_channels[$chan]->removeUser($user);
    }
    $this->close($user);
    unset($this->_clients[$user->id]);
}

function topic($user, $p){
    if(empty($p)){
        $this->error(461, $user, 'topic');
        return;
    }
    $p = explode(" ", $p);
    if(array_key_exists($p['0'], $this->_channels) === FALSE){
        $this->error(403, $user, $p['0']);
        return;
    }
    if(array_search($p['0'], $user->channels) === FALSE){
        $this->error(442, $user, $p['0']);
        return;
    }
    if(count($p) == 1){
        $chan = $this->_channels[$p['0']];
        if(empty($chan->topic)){
            $user->send(":{$this->servname} 331 ($chan->name} :No topic set.");
            return;
        }
        $user->send(":{$this->servname} 332 {$user->nick} {$chan->name} :{$chan->topic}");
        $user->send(":{$this->servname} 333 {$user->nick} {$chan->name} {$chan->topic_setby} {$chan->topic_seton}");
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
    if(empty($nick))
        return false;
    if(!preg_match($this->nickRegex, $nick))
        return false;
    $nick = substr($nick, 0, $this->config['ircd']['nicklen']);
    return true;
}

function checkRealName(&$nick){
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
