<?php

class ircd {

function newConnection($in, $key){
	global $core;
	$e = explode(" ", $in);
        $command = strtolower($e['0']);
	switch(@$command){
		case 'pass':

		break;
		case 'user':
		//USER nick mode unused :Real Name
		$err = FALSE;
		while($err == FALSE){
		if(preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,16}$/", $e['1'])){
			$err = "Illegal characters.";
			continue;
		}
		//if(modes n shit){
			
		//}
		unset($e['3']); //unused
		if(count($e) == 5){
			$rn = $e['4'];
		} else {
			for($i=4;$i > count($e);$i++){
				$rn[] = $e[$i];
			}
			$rn = implode(" ", $rn);
			if($rn[0] == ":"){
				$rn = substr($rn, 1);
			}
		}
		if(preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,19}$/", $e['1'])){
			$err = "Illegal characters.";
			continue;
		}
		}
		if($err){
			$this->error('432', $key,$e['1'].":$err");
		}

		break;
		case 'nick':
		if(preg_match("/^[a-zA-Z\[\]\\\|\^\`\_\{\}]{1}[a-zA-Z0-9\[\]\\\|\^\`\_\{\}]{0,16}$/", $e['1'])){
			$core->_clients[$key]['nick'] = $e['1'];
			$core->_clients[$key]['regbit'] += 2;
			if($core->_clients[$key]['regbit'] == 3){
				$core->_clients[$key]['registered'] == TRUE;
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
	if(method_exists(__CLASS__,$command)){
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
	}
	$core->write($socket, $message);
}

function user($key, $pa){
	echo "h";
}
}

?>
