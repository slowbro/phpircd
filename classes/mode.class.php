<?php

class Mode {

const TYPE_A = 1;
const TYPE_B = 2;
const TYPE_C = 3;
const TYPE_D = 4;
const TYPE_P = 5;

var $target;
var $type;
var $letter;
var $param = "";
var $list = array();
var $hooks = array();

function __construct($letter, $target, $type, $hooks=array()){
    $this->letter = $letter;
    $this->type   = $type;
    $this->target = $target;
    foreach($hooks as $n => $f)
        $this->registerHook($n, $f);
}

function registerHook($n, $f){
    $this->hooks[$n] = $f;
}


}

?>
