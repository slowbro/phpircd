<?php

class ircd {

var $forbidden = array("newConnecntion", "process", "welcome");

function newConnection($in, $key){
	global $core;
	$e = explode(" ", $in);
        $command = strtolower($e['0']);
	switch(@$command){
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
		if(!preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,16}$/", $e['1'])){
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
		if(!preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\} ]{0,19}$/", $rn)){
			$err = "$rn:Illegal characters.";
			continue;
		}
		break;
		}
		if($err){
			$this->error('432', $key,"$err");
		} else {
			unset($e['3']); //unused
			$core->_clients[$key]['username'] = $e['1'];
			$core->_clients[$key]['usermode'] = "";
			$core->_clients[$key]['realname'] = $rn;
			$core->_clients[$key]['lastping'] = time();
			if($core->_clients[$key]['regbit'] ^ 1){
				$core->_clients[$key]['regbit'] += 1;
			}
			if($core->_clients[$key]['regbit'] == 3){
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
		if(preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,16}$/", $e['1'])){
			$core->_clients[$key]['nick'] = $e['1'];
			if($core->_clients[$key]['regbit'] ^ 2){
				$core->_clients[$key]['regbit'] +=2;
			}
			if($core->_clients[$key]['regbit'] == 3){
				$core->_clients[$key]['registered'] = TRUE;
				$core->_clients[$key]['prefix'] = $core->_clients[$key]['nick']."!".$core->_clients[$key]['username']."@".$core->_clients[$key]['address'];
				$this->welcome($key);
			}
		} else {
			$this->error('432', $key, $e['1'].":Illegal characters.");
		}
		break;
	}

}

function process($in, $key){
	//example:COMMAND ?(:)prams
	$e = explode(" ", $in);
	$command = strtolower($e['0']);
	unset($e['0']);
	$params = implode (" ", $e);
	if(method_exists(__CLASS__,$command) && array_search($command, $this->forbidden) === FALSE){
		$this->$command($key, $params);
	}
}

function error($numeric, $key, $extra=""){
	global $core;
	$socket = $core->_client_sock[$key];
	$target = $core->_clients[$key]['nick'];
	$prefix = ":".$core->servname." ".$numeric." ".$target." ";
	switch($numeric){
	case 409:
	$message = $prefix.":No origin specified.";
	break;
	case 411:
	$message = $prefix.":No recipient given ($extra).";
	break;
	case 412:
	$message = $prefix.":No text to send.";
	break;
	case 422:
	$message = $prefix.":MOTD file missing.";
	break;
	case 431:
	$message = $prefix.":No nickname given.";
	break;
	case 432:
	$extra = explode(":",$extra);
	$message = $prefix."$extra[0] :Erroneous nickname".($extra['1']?": ".$extra['1']:"");
	break;
	case '461':
	$message = $prefix."$extra :Not enough parameters.";
	}
	$core->write($socket, $message);
}

function welcome($key){
	global $core;
	$socket = $core->_client_sock[$key];
	$cl = $core->_clients[$key];
	$core->write($socket, ":{$core->servname} 001 {$cl['nick']} :Welcome to the {$core->network} IRC network, {$cl['prefix']}");
	$core->write($socket, ":{$core->servname} 002 {$cl['nick']} :Your host is {$core->servname} running {$core->version}");
	$core->write($socket, ":{$core->servname} 003 {$cl['nick']} :This server was created {$core->createdate}");
	$core->write($socket, ":{$core->servname} 004 {$cl['nick']} {$core->servname} {$core->version} <umodes> <chanmodes>");
}

function user($key, $pa){
	echo "h";
}
}

?>
