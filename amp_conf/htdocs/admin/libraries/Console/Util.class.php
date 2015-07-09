<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//Process
use Symfony\Component\Process\Process;
class Util extends Command {
  protected function configure(){
    $this->setName('util')
      ->setDescription(_('Common utilities'))
      ->setDefinition(array(
        new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
  }
  protected function execute(InputInterface $input, OutputInterface $output){
    if(posix_geteuid() != 0) {
      $output->writeln("<error>You need to be root to run this command</error>");
      exit(1);
    }
    $args = $input->getArgument('args');
    $command = isset($args[0])?$args[0]:'';
    switch ($command) {
      case 'tablefix':
      $process = new Process('mysqlcheck --repair --all-databases');
      try {
				$output->writeln(_("Attempting to repair MySQL Tables this may take a while"));
				$process->mustRun();
				$output->writeln(_("MySQL Tables Repaired"));
			} catch (ProcessFailedException $e) {
					$output->writeln(sprintf(_("MySQL table repair Failed: %s"),$e->getMessage()));
			}
      break;
      case "zendid":
        $output->writeln("===========================");
        foreach(zend_get_id() as $id){
          $output->writeln($id);
        }
        $output->writeln("===========================");
      break;

      default:
        $output->writeln('Invalid argument');
      break;
    }
  }
}
