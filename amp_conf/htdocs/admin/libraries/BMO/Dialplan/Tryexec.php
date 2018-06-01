<?php
namespace FreePBX\Dialplan;
class Tryexec extends Extensions{
   var $try_application;

   function __construct($try_application = '') {
       $this->try_application = $try_application;
   }
   function output() {
       return "TryExec(".$this->try_application.")";
   }
}