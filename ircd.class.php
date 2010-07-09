<?php

class ircd {

function newConnection($in, $key){
	global $core;
	$e = explode(" ", $in);
        $command = strtolower($e['0']);
	switch(@$command){
		case 'user':
		
		break;
		case 'nick':
		if(preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,16}$/", $e['1'])){
			$core->_clients[$key]['nick'] = $e['1'];
		} else {
			$core->write($core->_client_sock[$key], $core->servname." 432 ".@$core->_clients[$key]['nick']." ".$e['1']." :Erroneous Nickname: Illegal characters.");
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
	if(method_exists(__CLASS__,$command)){
		$this->$command($key, $params);
	}
}

function user($key, $pa){
	echo "h";
}
}

?>
