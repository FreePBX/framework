<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;

class Chown extends Command {
	private $errors = array();
	protected function configure(){
		$this->setName('chown')
		->setDescription('Change ownership of files')
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$freepbx_conf = \freepbx_conf::create();
		$conf = $freepbx_conf->get_conf_settings();
		foreach ($conf as $key => $val){
			${$key} = $val['value'];
		}
		//This needs to be a hook, where modules declare rather than a list.
		$sessdir = session_save_path();
		$own = array(
			'/etc/amportal.conf',
			$FREEPBX_CONF,
			$ASTRUNDIR,
			$ASTETCDIR,
			$ASTVARLIBDIR,
			$ASTVARLIBDIR . '/.ssh.id_rsa',
			$ASTLOGDIR,
			$ASTSPOOLDIR,
			$AMPWEBROOT . '/admin',
			$AMPWEBROOT . '/recordings',
			$AMPBIN,
			$FPBXDBUGFILE,
			$FPBX_LOG_FILE,
			$ASTAGIDIR,
			$ASTVARLIBDIR . '/agi-bin',
			$ASTVARLIBDIR . '/' . $MOHDIR,
			'/dev/tty9',
			'/dev/zap',
			'/dev/dahdi',
			'/dev/capi20',
			'/dev/misdn',
			'/dev/mISDN',
			'/dev/dsp',
			'/etc/dahdi',
			'/etc/wanpipe',
			'/etc/obdc.ini',
			$sessdir,
			$AMPWEBROOT,
		);
		$perms = array(
			'/etc/amportal.conf' => 0660,
			${FREEPBX_CONF} => 0660,
			${ASTETCDIR} => 0775,
			${ASTVARLIBDIR} => 0775,
			${ASTVARLIBDIR} . '/.ssh.id_rsa' => 0600,
			${ASTLOGDIR} => 0755,
			${AMPWEBROOT} . '/admin' => 0774,
			${AMPWEBROOT} . '/recordings' => 0774,
			${ASTSPOOLDIR} => 0775,
			${AMPBIN} => 0775,
			//${ASTAGIDIR} => 0775',
			${ASTVARLIBDIR} . '/agi-bin' => 0775,
			);

		$output->writeln("Setting Ownership");
		$progress = new ProgressBar($output, count($own));
		$progress->start();
		foreach($own as $file){
			$progress->advance();
			$filetype = filetype($file);
			switch($filetype){
				case 'dir':
					$this->recursiveChown($file, $AMPASTERISKWEBUSER, $AMPASTERISKWEBGROUP);
				break;
				case 'link':
					$realfile = readlink($file);
					$this->singleChown($realfile, $AMPASTERISKWEBUSER,$AMPASTERISKWEBGROUP);
				break;
				case 'file':
					$this->singleChown($file, $AMPASTERISKWEBUSER,$AMPASTERISKWEBGROUP);
				break;
			}
		}
		$progress->finish();

		$output->writeln("");
		$output->writeln("Setting Permissions");
		$progress = new ProgressBar($output, count($perms));
		$progress->start();
		foreach($perms as $file => $perm){
			$progress->advance();
			$filetype = filetype($file);
			if($filetype == 'dir'){
				$this->recursivePerms($file, $perm);
			}else{
				$this->singlePerms($file, $perm);
			}
		}
		$progress->finish();

		$output->writeln("");
		foreach($this->errors as $error) {
			$output->writeln("<error>".$error."</error>");
		}
	}
	private function singleChown($file, $user, $group){

		$oret = chown($file, $user);
		$gret = chgrp($file, $group);
		if(!$oret){
			$this->errors[] = 'Setting owner for ' . $file . ' failed';
		} else if(!$gret){
			$this->errors[] = 'Setting Group for ' . $file . ' failed';
		} else {
		}
	}
	private function recursiveChown($dir, $user, $group){
		$files = scandir($dir);
		foreach($files as $file){
			if($file == '.' || $file == '..'){
				continue;
			}
			$fullpath = $dir . '/' . $file;
			$filetype = filetype($fullpath);
			switch($filetype){
				case 'dir':
					$this->recursiveChown($fullpath, $user, $group);
				break;
				case 'link':
					$realfile = readlink($fullpath);
					$this->singleChown($realfile, $user, $group);
				break;
				case 'file':
					$this->singleChown($fullpath, $user, $group);
				break;
			}

		}
	}
	private function singlePerms($file, $perms){
		$filetype = filetype($file);
		switch($filetype){
			case 'link':
				$realfile = readlink($file);
				$ret = chmod($realfile,$perms);
				if(!$ret){
					$this->errors[] = 'Permissions for ' . $realfile . ' failed';
				}
				unset($ret);
			break;
			case 'file':
				$ret = chmod($file,$perms);
				if(!$ret){
					$this->errors[] = 'Permissions for ' . $file . ' failed';
				}
				unset($ret);
			break;
		}
	}
	private function recursivePerms($dir, $perms){
		$files = scandir($dir);
		foreach($files as $file){
			if($file == '.' || $file == '..'){
				continue;
			}
			$fullpath = $dir . '/' . $file;
			$filetype = filetype($fullpath);
			if($filetype == 'dir'){
				$this->recursivePerms($fullpath, $perms);
			}else{
				$this->singlePerms($fullpath,$perms);
			}
		}
	}
}
