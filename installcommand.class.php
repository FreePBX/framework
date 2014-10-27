<?php
namespace FreePBX\Install;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class FreePBXInstallCommand extends Command {
	protected function configure() {
		$this
			->setName('install')
			->setDescription('FreePBX Installation Utility')
			;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$style = new OutputFormatterStyle('white', 'red', array('bold'));
		$output->getFormatter()->setStyle('fire', $style);

		$output->writeln("wat");
	}
}
