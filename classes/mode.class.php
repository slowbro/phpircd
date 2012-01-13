<?php

class Mode {

//types
const TYPE_A = 1;
const TYPE_B = 2;
const TYPE_C = 3;
const TYPE_D = 4;
const TYPE_P = 5;

//channel privs
const CHANNEL_KICK   = 2;
const CHANNEL_TOPIC  = 2;
const CHANNEL_MODE_a = 8;
const CHANNEL_MODE_A = 4;
const CHANNEL_MODE_b = 2;
const CHANNEL_MODE_c = 4;
const CHANNEL_MODE_C = 4;
const CHANNEL_MODE_e = 2;
const CHANNEL_MODE_f = 4;
const CHANNEL_MODE_G = 4;
const CHANNEL_MODE_h = 4;
const CHANNEL_MODE_H = 4;
const CHANNEL_MODE_i = 4;
const CHANNEL_MODE_I = 2;
const CHANNEL_MODE_J = 4;
const CHANNEL_MODE_k = 4;
const CHANNEL_MODE_K = 4;
const CHANNEL_MODE_l = 4;
const CHANNEL_MODE_L = 4;
const CHANNEL_MODE_m = 2;
const CHANNEL_MODE_M = 4;
const CHANNEL_MODE_n = 4;
const CHANNEL_MODE_N = 4;
const CHANNEL_MODE_o = 4;
const CHANNEL_MODE_O = 32;
const CHANNEL_MODE_p = 4;
const CHANNEL_MODE_q = 16;
const CHANNEL_MODE_Q = 4;
const CHANNEL_MODE_r = 32;
const CHANNEL_MODE_R = 4;
const CHANNEL_MODE_s = 4;
const CHANNEL_MODE_S = 4;
const CHANNEL_MODE_t = 4;
const CHANNEL_MODE_u = 4;
const CHANNEL_MODE_v = 2;
const CHANNEL_MODE_V = 4;
const CHANNEL_MODE_z = 4;

// role definitions
const CHANNEL_VOICE = 1;
const CHANNEL_HOP   = 2;
const CHANNEL_OP    = 4;
const CHANNEL_AOP   = 8;
const CHANNEL_QOP   = 16;

//info
var $target;
var $type;
var $letter;
var $param = "";
var $list = array();
var $hooks = array();

function __construct($letter, $target, $type, $hooks=array(), $extra=false){
    $this->letter = $letter;
    $this->type   = $type;
    $this->target = $target;
    foreach($hooks as $n => $f)
        $this->registerHook($n, $f);
    if($extra){
        foreach($extra as $k=>$v)
            $this->{$k} = $v;
    }
}

function registerHook($n, $f){
    $this->hooks[$n] = $f;
}


}

?>
