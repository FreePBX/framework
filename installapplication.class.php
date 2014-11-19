<?php
namespace FreePBX\Install;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class FreePBXInstallApplication extends Application {
	protected function getCommandName(InputInterface $input) {
		return 'install';
	}

	protected function getDefaultCommands() {
		$defaultCommands = parent::getDefaultCommands();
		$defaultCommands[] = new FreePBXInstallCommand();
		return $defaultCommands;
	}

	public function getDefinition() {
		$inputDefinition = parent::getDefinition();
		$inputDefinition->setArguments();
		return $inputDefinition;
	}
}
?>
