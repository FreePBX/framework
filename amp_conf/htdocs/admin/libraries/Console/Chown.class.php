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
	private $infos = array();
	protected function configure(){
		$this->setName('chown')
		->setDescription(_('Change ownership of files'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
		$this->fs = new Filesystem();
		$this->modfiles = array();
		$this->actions = array();
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		if(posix_geteuid() != 0) {
			$output->writeln("<error>You need to be root to run this command</error>");
			exit(1);
		}
		$output->writeln("Setting Permissions...");
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
		$sessdir = !empty($session) ? $session : '/var/lib/php/session';
		$this->modfiles['framework'][] = array('type' => 'rdir',
													'path' => $sessdir,
													'perms' => 0744);
		$this->modfiles['framework'][] = array('type' => 'file',
													'path' => '/etc/amportal.conf',
													'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'file',
													'path' => '/etc/freepbx.conf',
													'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'dir',
													'path' => $ASTRUNDIR,
													'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
													'path' => \FreePBX::GPG()->getGpgLocation(),
													'perms' => 0755);
		//we may wish to declare these manually or through some automated fashion
		$this->modfiles['framework'][] = array('type' => 'rdir',
													'path' => $ASTETCDIR,
													'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'file',
													'path' => $ASTVARLIBDIR . '/.ssh/id_rsa',
													'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'rdir',
													'path' => $ASTLOGDIR,
													'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
													'path' => $ASTSPOOLDIR,
													'perms' => 0755);

		//I have added these below individually,
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => $FPBXDBUGFILE,
											   'perms' => 0644);
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => $FPBX_LOG_FILE,
											   'perms' => 0644);
		//We may wish to declare files individually rather than touching everything
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $ASTVARLIBDIR . '/' . $MOHDIR,
											   'perms' => 0755);
		/* this was never actually assigned to do anything
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/dev/tty9',
											   'perms' => 0644);
		*/
		$this->modfiles['framework'][] = array('type' => 'file',
											   'path' => '/etc/obdc.ini',
											   'perms' => 0644);
		//we were doing a recursive on this which I think is not needed.
		//Changed to just be the directory
		//^ Needs to be the whole shebang, doesnt work otherwise
		$this->modfiles['framework'][] = array('type' => 'rdir',
											   'path' => $AMPWEBROOT,
											   'perms' => 0755);
		/* Same as above
		$this->modfiles['framework'][] = array('type' => 'rdir',
												'path' => $AMPWEBROOT . '/admin/',
												'perms' => 0755);

		$this->modfiles['framework'][] = array('type' => 'rdir',
												'path' => $AMPWEBROOT . '/recordings/',
												'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'rdir',
												'path' => $AMPWEBROOT . '/ucp/',
												'perms' => 0755);
		*/
		//Anything in bin and agi-bin should be exec'd
		//Should be after everything except but before hooks
		//So that we dont get overwritten by ampwebroot
		$this->modfiles['framework'][] = array('type' => 'execdir',
		'path' => $AMPBIN,
		'perms' => 0755);
		$this->modfiles['framework'][] = array('type' => 'execdir',
		'path' => $ASTAGIDIR,
		'perms' => 0755);
		//Merge static files and hook files, then act on them as a single unit
		$this->modfiles = array_merge_recursive($this->modfiles,$this->fwcChownFiles());

		$ampowner = $AMPASTERISKWEBUSER;
		/* Address concerns carried over from amportal in FREEPBX-8268. If the apache user is different
		 * than the Asterisk user we provide permissions that allow both.
		 */
		$ampgroup =  $AMPASTERISKWEBUSER != $AMPASTERISKUSER ? $AMPASTERISKGROUP : $AMPASTERISKWEBGROUP;
		foreach($this->modfiles as $modfilearray => $modfilelist){
			foreach($modfilelist as $file){
				if(!file_exists($file['path'])){
						continue;
				}
				//Handle custom ownership (optional)
				$owner = array_key_exists('owner', $file)?$file['owner']:$ampowner;
				$group = array_key_exists('group', $file)?$file['group']:$ampgroup;
				//Set warning for bad permissions and move on
				$this->padPermissions($file['path'],$file['perms']);
				switch($file['type']){
					case 'file':
					case 'dir':
						$this->actions[] = array($file['path'],$owner,$group,$file['perms']);
						break;
					case 'rdir':
						$fileperms = $this->stripExecute($file['perms']);
						$files = $this->recursiveDirList($file['path']);
						$this->actions[] = array($file['path'], $owner, $group, $file['perms']);
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
						$this->actions[] = array($file['path'], $owner, $group, $file['perms']);
						foreach($files as $f){
							$this->actions[] = array($f, $owner, $group, $file['perms']);
						}
						break;
				}
			}
		}
		$actioncount = count($this->actions);
		$progress = new ProgressBar($output, $actioncount);
		$progress->setRedrawFrequency(100);
		$progress->start();
		foreach($this->actions as $action){
			$this->singleChown($action[0],$action[1],$action[2]);
			$this->singlePerms($action[0], $action[3]);
			$progress->advance();
		}
		$progress->finish();
		$output->writeln("");
		$output->writeln("Finished setting permissions");
		foreach($this->errors as $error) {
			$output->writeln("<error>".$error."</error>");
		}
		foreach($this->infos as $error) {
			$output->writeln("<info>".$error."</info>");
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
				$this->errors[] = _('An error occurred while changing ownership ') . $file;
			}
		}
		try {
			$this->fs->chgrp($file,$group);
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] = _('An error occurred while changing group ') . $file;
			}
		}
	}
	private function recursiveChown($dir, $user, $group){
		try {
			$this->fs->chown($dir,$user, true);
		} catch (IOExceptionInterface $e) {
			if($dir){
				$this->errors[] = _('An error occurred while changing ownership ') . $realfile;
			}
		}
		try {
			$this->fs->chgrp($dir,$group, true);
		} catch (IOExceptionInterface $e) {
			if($file){
				$this->errors[] = _('An error occurred while changing group ') . $file;
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
					if(file_exists($realfile)) {
						$this->errors[] = _('An error occurred while changing permissions on link ') . $file . _(' which points to ').$realfile;
					} else {
						//File does not exist. Now we have a dangling symlink so remove it.
						$this->infos[] = _('Removing dangling symlink ') . $file . _(' which points to a file that no longer exists');
						unlink($file);
					}
				}
			break;
			case 'dir':
			case 'file':
				try {
					$this->fs->chmod($file,$perms);
				} catch (IOExceptionInterface $e) {
					if($file){
						$this->errors[] = _('An error occurred while changing permissions on file') . $file;
					}
				}
			break;
			default:
				throw new \Exception(_("Unknown filetype of:").$filetype."[".$file."]");
			break;
		}
	}
	private function recursivePerms($dir, $perms){
		try {
			$this->fs->chmod($dir,$perms, 0000, true);
		} catch (IOExceptionInterface $e) {
			if($dir){
				$this->errors[] = _('An error occurred while changing permissions ') . $dir;
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
	private function padPermissions($file, $mode){
		if(($mode>>9) == 0){
			return true;
		}else{
			$this->errors[] = $file . _(' Likely will not work as expected');
			$this->errors[] = _('Permissions should be set with a leading 0, example 644 should be 0644 File:') . $file . _(' Permission set as: ') . $mode ;
			return false;
		}
	}

	private function fwcChownFiles(){
		$modules = \FreePBX::Hooks()->processHooks();
		return $modules;
	}
}
