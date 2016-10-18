<?php
namespace FreePBX\Console\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoteUnlock extends Command {
	protected function configure(){
		$this->setName('genunlockkey')
		->setDescription(_('Return key to unlock session remotely'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$output->writeln(_('If REMOTEUNLOCK is enabled, you will receive a value for KEY.'));
		$output->writeln(_('You can use that as a parameter to config.php, thus:'));
		$output->writeln(_('http://192.168.1.1/admin/config.php?unlock=abc123def... '));
		$output->writeln('');
		$output->writeln("KEY=".\FreePBX::Unlock()->genUnlockKey());
	}
}
