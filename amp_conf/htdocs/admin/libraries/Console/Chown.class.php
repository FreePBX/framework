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
		$this->setName('pwn')
		->setDescription('Change ownership of files')
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
		$this->fs = new Filesystem();
		$this->modfiles = array();
		$this->actions = array();
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$freepbx_conf = \freepbx_conf::create();
		$conf = $freepbx_conf->get_conf_settings();
		foreach ($conf as $key => $val){
			${$key} = $val['value'];
		}
		/*
		 * These are files Framework is responsible for This list can be 
		 * reduced by moving responsibility to other modules as a hook
		 * where appropriate. 
		 * 
		 * Types:
		 * 		file:		Set permissions/ownership on a single item
		 * 		dir: 		Set permissions/ownership on a single directory
		 * 		rdir: 		Set permissions/ownership on a single directory then recursively on
		 * 					files within less the execute bit. If the dir is 755, child files will be 644,
		 * 					child directories will be set the same as the parent.
		 * 		execdir:	Same as rdir but the execute bit is not stripped. 
		 */
		$sessdir = session_save_path();
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/etc/amportal.conf',
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'dir',
											   'path' => $ASTRUNDIR,
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTETCDIR,
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTVARLIBDIR,
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTVARLIBDIR . '/.ssh.id_rsa',
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTLOGDIR,
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTSPOOLDIR,
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $AMPWEBROOT . '/admin/',
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $AMPWEBROOT . '/recordings/',
											   'perms' => 0755);
		//We may wish to declare files individually rather than touching everything											   
		$this->modfiles['framework'][] = array('type' => 'execdir',
											   'path' => $AMPBIN,
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => $FPBXDBUGFILE,
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => $FPBX_LOG_FILE,
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTAGIDIR,
											   'perms' => 0755);
		//We may wish to declare files individually rather than touching everything
		$this->modfiles['framework'][] = array('type' => 'execdir',
											   'path' => $ASTVARLIBDIR . '/agi-bin',
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTVARLIBDIR . '/' . $MOHDIR,
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/dev/tty9',
											   'perms' => 0644);
		//TODO: Move these to dahdiconfig hook //
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/dev/zap',
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/dev/dahdi',
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => '/etc/dahdi',
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => '/etc/wanpipe',
											   'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/dev/misdn',
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/dev/mISDN',
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/dev/dsp',
											   'perms' => 0644);
		//END TODO
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/etc/obdc.ini',
											   'perms' => 0644);		
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $sessdir,
											   'perms' => 0644);		
		$this->modfiles['framework'][] = array('type' => 'dir',
											   'path' => $AMPWEBROOT,
											   'perms' => 0755);
											  
		//Merge static files and hook files, then act on them as a single unit
		$this->modfiles = array_merge_recursive($this->modfiles,$this->fwcChownFiles());
		$output->writeln("Setting Ownership");
		$owner = $AMPASTERISKWEBUSER;
		$group = $AMPASTERISKWEBGROUP;
		$output->writeln("Building action list...");
		foreach($this->modfiles as $modfilearray => $modfilelist){
			foreach($modfilelist as $file){
				switch($file['type']){
					case 'file':
					case 'dir':
						$this->actions[] = array($file['path'],$owner,$group,$file['perms']);
						break;
					case 'rdir':
						$fileperms = $this->stripExecute($file['perms']);
						$files = $this->recursiveDirList($file['path']);
						foreach($files as $f){
							if(is_dir($f)){
								$this->actions[] = array($f, $owner, $group, $file['perms']);
							}else{
								$this->actions[] = array($f, $owner, $group, $fileperms);
							}
						}
						break;
					case 'execdir':
						$files = $this->recursiveDirList($file['path']);
						foreach($files as $f){
							$this->actions[] = array($f, $owner, $group, $file['perms']);
						}
						break;
				}
			}	
		}
		$actioncount = count($this->actions);
		$output->writeln("");
		$output->writeln($actioncount . " Actions queued");
		$output->writeln("");
		$progress = new ProgressBar($output, $actioncount);
		$progress->start();
		foreach($this->actions as $action){
			$this->singleChown($action[0],$action[1],$action[2]);
			$this->singlePerms($action[0], $action[3]);
			$progress->advance();	
		}
		$progress->finish();
		$output->writeln("");
		$output->writeln("");
		foreach($this->errors as $error) {
			dbug($error);
			$output->writeln("<error>".$error."</error>");
		}
	}
	private function stripExecute($mask){
		$mask = ($mask & ~(1<<0));
		$mask = ($mask & ~(1<<3));
		$mask = ($mask & ~(1<<6));
		return $mask;
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
	private function recursiveDirList($path){
		$list =  array();
		$files = scandir($path);
		foreach($files as $file){
			if ($file == '.' || $file == '..'){
				continue;
			}
			$fullpath = $path . '/' . $file;
			$filetype = filetype($fullpath);
			if($filetype == 'dir'){
				$list[] = $fullpath;
				$getFiles = $this->recursiveDirList($fullpath);
				foreach($getFiles as $f){
					$list[] = $f;
				}
			}else{
				$list[] = $fullpath;
			}
		}
		return array_unique($list);
	}

	private function fwcChownFiles(){
		$modules = \FreePBX::Hooks()->processHooks($files);
		return $modules;
	}
}
