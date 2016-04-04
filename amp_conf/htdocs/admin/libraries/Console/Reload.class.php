<?php
namespace FreePBX\Console\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Reload extends Command {
	protected function configure(){
		$this->FreePBXConf = \FreePBX::Config();
		$this->setName('r')
		->setAliases(array('reload'))
		->setDescription(_('Reload Configs'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$output->writeln(_("Reloading FreePBX"));
		$args = $input->getArgument('args');
		$result = do_reload();
		if ($result['status'] != true) {
			$output->writeln("<error>"._("Error(s) have occured, the following is the retrieve_conf output:")."</error>");
			$retrieve_array = explode('<br/>',$result['retrieve_conf']);
			foreach ($retrieve_array as $line) {
				$line = preg_replace('#<br\s*/?>#i','', $line);
				$output->writeln("<error>".$line."</error>");
			};
		} else {
			if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
				$retrieve_array = explode('<br/>',$result['retrieve_conf_verbose']);
				foreach ($retrieve_array as $line) {
					$line = preg_replace('#<br\s*/?>#i','', $line);
					$output->writeln("\t".$line);
				};
			}
			$output->writeln($result['message']);
		}
	}
}
