<?php
namespace FreePBX\Dialplan;
class Transfer extends Extension{
    var $number;

    function __construct($number) {
        $this->number = $number;
    }

    function output() {
        return "Transfer(".$this->number.")";
    }
}