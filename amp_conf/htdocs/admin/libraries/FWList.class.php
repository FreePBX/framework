<?php
namespace FreePBX\Console\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
/**
* ListCommand displays the list of all available commands for the application.
*
* @author Fabien Potencier <fabien@symfony.com>
*/
class FWListCommand extends Command {
	protected function configure() {
		$this
		->setName('list')
		->setDefinition($this->createDefinition())
		->setDescription('Lists commands')
		->setHelp(<<<EOF
The <info>%command.name%</info> command lists all commands:

	<info>php %command.full_name%</info>

You can also display the commands for a specific namespace:

	<info>php %command.full_name% test</info>

You can also output the information in other formats by using the <comment>--format</comment> option:

	<info>php %command.full_name% --format=xml</info>

It's also possible to get raw list of commands (useful for embedding command runner):

	<info>php %command.full_name% --raw</info>
EOF
			)
		;
	}

	public function getNativeDefinition() {
		return $this->createDefinition();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln(" ______             _____  ______   __");
		$output->writeln("|  ____|           |  __ \|  _ \ \ / /");
		$output->writeln("| |__ _ __ ___  ___| |__) | |_) \ V /");
		$output->writeln("|  __| '__/ _ \/ _ \  ___/|  _ < > <");
		$output->writeln("| |  | | |  __/  __/ |    | |_) / . \\");
		$output->writeln("|_|  |_|  \___|\___|_|    |____/_/ \_\\");

		if ($input->getOption('xml')) {
			$input->setOption('format', 'xml');
		}

		$helper = new DescriptorHelper();
		$helper->describe($output, $this->getApplication(), array(
			'format' => $input->getOption('format'),
			'raw_text' => $input->getOption('raw'),
			'namespace' => $input->getArgument('namespace'),
		));
	}

	private function createDefinition()
	{
		return new InputDefinition(array(
			new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace name'),
			new InputOption('xml', null, InputOption::VALUE_NONE, 'To output list as XML'),
			new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
			new InputOption('format', null, InputOption::VALUE_REQUIRED, 'To output list in other formats', 'txt'),
		));
	}
}
