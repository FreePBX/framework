<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Setting extends Command {
	protected function configure(){
		$this->FreePBXConf = \FreePBX::Config();
		$this->setName('setting')
		->setAliases(array('set'))
		->setDescription(_('View and update settings'))
		->setDefinition(array(
			new InputOption('dump', 'd', InputOption::VALUE_NONE, _('Dump Configs')),
			new InputOption('reset', 'r', InputOption::VALUE_NONE, _('Reset to defailt')),
			new InputOption('import', 'i', InputOption::VALUE_REQUIRED, _('Import settings from file')),
			new InputOption('export', 'e', InputOption::VALUE_REQUIRED, _('Export settings to file')),
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$FLAGS = False;
		if ($input->getOption('export')){
			$FLAGS = True;
			$filename = $input->getOption('export');
			$confdump = $this->FreePBXConf->get_conf_settings();
			$confarray = array();
			foreach($confdump as $key => $val){
				$confarray[$key] = $val['value'];
			}
			$configjson = json_encode($confarray);
			$fs = new Filesystem();
			try {
				$fs->dumpFile($filename,$configjson);
				return true;
			} catch (IOExceptionInterface $e){
				$output->writeln(sprintf(_("Could not write to %s"),$filename));
				return false;
			}
		}
		if ($input->getOption('import')){
			$FLAGS = True;
			$filename = $input->getOption('import');
			$settings = json_decode(file_get_contents($filename));
			foreach($settings as $key => $val){
				if($this->FreePBXConf->conf_setting_exists($key)){
					$output->writeln(sprintf(_('Changing %s to %s'),$key,$val));
					$this->FreePBXConf->set_conf_values(array($key => $val),true,true);
				}else{
					$output->writeln(sprintf(_('The setting %s was not found!'),$key));
				}
			}
			return true;
		}
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
					if($dialog->askConfirmation($output, '<question>'.sprintf(_('Are you sure you want to set %s to its default?'),$arg).'</question>',false)){
						if($this->FreePBXConf->conf_setting_exists($arg)){
							$default = $this->FreePBXConf->get_conf_default_setting($arg);
							$output->writeln('Changing ' . $arg . ' to ' . $default);
							$this->FreePBXConf->set_conf_values(array($arg => $default),true,true);
						}else{
							$output->writeln(sprintf(_('The setting %s was not found!'),$arg));
						}
					}else{
						$output->writeln(sprintf(_('Current setting for %s left in place'),$arg));
					}
				}
			}
		}
		if(!$FLAGS){
			$setting = $args[0];
			$value = $args[1];
			if($this->FreePBXConf->conf_setting_exists($setting)){
				$output->writeln(sprintf(_('Changing %s to %s'),$setting,$value));
				$this->FreePBXConf->set_conf_values(array($setting => $value),true,true);
			}else{
				$output->writeln(sprintf(_('The setting %s was not found!'),$arg));
			}
		}
	}
}
