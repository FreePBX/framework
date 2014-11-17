<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Setting extends Command {
	protected function configure(){
		$this->FreePBXConf = \FreePBX::Config();
		$this->setName('setting')
		->setAliases(array('set'))
		->setDescription('Stream files for debugging')
		->setDefinition(array(
			new InputOption('dump', 'd', InputOption::VALUE_NONE, 'Dump Configs'),
			new InputOption('reset', 'r', InputOption::VALUE_NONE, 'Reset to defailt'),
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		array_shift($args);
		$FLAGS = False;
		if ($input->getOption('dump')){
			$FLAGS = True;
			if(!$args){
				$conf = $this->FreePBXConf->get_conf_settings();
				foreach ($conf as $key => $val){
					$output->writeln($key . '=' . $val['value']);
				}
			}else{
				$conf = $this->FreePBXConf->get_conf_settings();
				foreach ($conf as $key => $val){
					${$key} = $val['value'];
				}
				foreach($args as $arg){
					$output->writeln($arg . '=' . ${$arg});
				}
			}
		}
		if($input->getOption('reset')){
			$FLAGS = True;
			if($args){
				foreach($args as $arg){
					$dialog = $this->getHelper('dialog');
					if($dialog->askConfirmation($output, '<question>Are you sure you want to set ' . $arg . ' to its default?</question>',false)){ 
						if($this->FreePBXConf->conf_setting_exists($arg)){
							$default = $this->FreePBXConf->get_conf_default_setting($arg);
							$output->writeln('Changing ' . $arg . ' to ' . $default);
							$this->FreePBXConf->set_conf_values(array($arg => $default),true,true);	
						}else{
							$output->writeln('The setting ' . $arg . ' was not found!'); 
						}
					}else{
						$output->writeln('Current setting for ' . $arg . ' left in place');
					}
				}
			}
		}
		if(!$FLAGS){
			$setting = $args[0];
			$value = $args[1];
			if($this->FreePBXConf->conf_setting_exists($setting)){
				$output->writeln('Changing ' . $setting . ' to ' . $value);
				$this->FreePBXConf->set_conf_values(array($setting => $value),true,true);	
			}else{
				$output->writeln('The setting ' . $arg . ' was not found!'); 
			}			
		}
	}
}
