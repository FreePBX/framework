<?php
namespace FreePBX\Console\Command\Retrieveconf;
/**
 * This class is used to run various System functions
 */
class Asterisk{
    public function __construct($freepbx){
        $this->freepbx = $freepbx
    }
    /**
     * Run asterisk checks
     *
     * @return true or throw exception
     */
    public function runChecks(){
        $this->checkAsterisk();
        $this->checkAstman();
        //If we didn't throw an exception lets assume we are cool
        return true;
    }
}
