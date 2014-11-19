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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Chown extends Command {
	private $errors = array();
	protected function configure(){
		$this->setName('chown')
		->setDescription('Change ownership of files')
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
		$this->fs = new Filesystem();	
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
			dbug($error);
			$output->writeln("<error>".$error."</error>");
		}
	}
	private function singleChown($file, $user, $group){
		try {
			$this->fs->chown($file,$user);
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] ='An error occurred while changing ownership ' . $file;
			}
		}
		try {
			$this->fs->chgrp($file,$group);
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] ='An error occurred while changing group ' . $file;
			}
		}

	}
	private function recursiveChown($dir, $user, $group){
		try {
			$this->fs->chown($realfile,$user, true);
		} catch (IOExceptionInterface $e) {
			if($realfile){
				$this->errors[] ='An error occurred while changing ownership ' . $realfile;
			}
		}
		try {
			$this->fs->chgrp($file,$group, true);
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] ='An error occurred while changing group ' . $file;
			}
		}
	}
	private function singlePerms($file, $perms){
		$filetype = filetype($file);
		switch($filetype){
			case 'link':
				$realfile = readlink($file);
				try {
					$this->fs->chmod($realfile,$perms);
				} catch (IOExceptionInterface $e) {
					if($realfile){
						if($realfile){
							$this->errors[] ='An error occurred while changing permissions ' . $realfile;
						}
					}
				}
				break;
			case 'file':
				$realfile = readlink($file);
				try {
					$this->fs->chmod($file,$perms);
				} catch (IOExceptionInterface $e) {
					if($file){
						$this->errors[] ='An error occurred while changing permissions ' . $file;
					}
				}
				break;
		}
	}
	private function recursivePerms($dir, $perms){
		try {
			$this->fs->chmod($dir,$perms, 0000, true);
		} catch (IOExceptionInterface $e) {
			if($dir){
				$this->errors[] ='An error occurred while changing permissions ' . $dir;
			}
		}
	}
}

