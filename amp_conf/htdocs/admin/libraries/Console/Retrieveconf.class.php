<?php
namespace FreePBX\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
class Util extends Command {
    public function configure(){

    }
    public function execute(InputInterface $input, OutputInterface $output){
        $this->input = $input;
        $this->output = $output;
        $this->freepbx = \FreePBX::Create();
        $this->fpbxConf = $this->freepbx-FreePBX_conf;
        $this->checkAstman();
        $this->checkAsterisk();
        $this->loadMetrics();
        $this->checkMemory();
    }
    private function checkAstman(){
        if(!$this->freepbx->astman->connected()){
            throw new \RuntimeException(sprintf(_("Unable to connect to Asterisk Manager from %s, aborting"),__FILE__));
        }
    }
    private function checkAsterisk(){
        $asteriskcommand = fpbx_which("asterisk");
        if(empty($asteriskcommand)){
            throw new \RuntimeException(_("Unable to find the Asterisk binary"));
        }
        exec($asteriskcommand . " -rx 'core show version'",$out,$ret);
        if($ret != 0){
            throw new \RuntimeException(_("Unable to connect to Asterisk through the CLI"));
        }
        return true;
    }
    private function loadMetrics(){

    }
}