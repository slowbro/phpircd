<?php

class Mode {

var $target;
var $type;
var $letter;
var $extra = false;
var $hooks = array();

function __construct($letter, $target, $type, $extra=false, $hooks=array()){
    $this->letter = $letter;
    $this->type   = $type;
    $this->target = $target;
    $this->extra  = $extra;
    foreach($hooks as $n => $f)
        $this->registerHook($n, $f);
}

function registerHook($n, $f){
    $this->hooks[$n] = $f;
}


}

?>
