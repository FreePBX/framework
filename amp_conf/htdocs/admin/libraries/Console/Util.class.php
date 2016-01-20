<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//Process
use Symfony\Component\Process\Process;
class Util extends Command {
	protected function configure(){
		$this->setName('util')
			->setDescription(_('Common utilities'))
			->setDefinition(array(
				new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$command = isset($args[0])?$args[0]:'';
		switch ($command) {
			case 'cleanplaybackcache':
				$output->writeln(_("Starting Cache cleanup"));
				$days = \FreePBX::Config()->get("CACHE_CLEANUP_DAYS");
				$time = $days*24*60*60;
				$path = \FreePBX::Config()->get("AMPPLAYBACK");
				$path = trim($path);
				$user = \FreePBX::Config()->get("AMPASTERISKWEBUSER");
				$formats = \FreePBX::Media()->getSupportedHTML5Formats();
				if(empty($path) || $path == "/") {
					$output->writeln("<error>".sprintf(_("Invalid path %s"),$path)."</error>");
					exit(1);
				}
				if (file_exists($path)) {
					foreach (new \DirectoryIterator($path) as $fileInfo) {
						if ($fileInfo->isDot()) {
							continue;
						}
						$info = posix_getpwuid($fileInfo->getOwner());
						if($info['name'] != $user) {
							continue;
						}
						$extension = pathinfo($fileInfo->getFilename(),PATHINFO_EXTENSION);
						if ($fileInfo->isFile() && in_array($extension,$formats) && (time() - $fileInfo->getCTime() >= $time)) {
							$output->writeln(sprintf(_("Removing file %s"),basename($fileInfo->getRealPath())));
							unlink($fileInfo->getRealPath());
						}
					}
				}
				$output->writeln(_("Finished cleaning up cache"));
			break;
			case 'tablefix':
				if(posix_geteuid() != 0) {
					$output->writeln("<error>You need to be root to run this command</error>");
					exit(1);
				}
				$process = new Process('mysqlcheck --repair --all-databases');
				try {
					$output->writeln(_("Attempting to repair MySQL Tables this may take a while"));
					$process->mustRun();
					$output->writeln(_("MySQL Tables Repaired"));
				} catch (ProcessFailedException $e) {
						$output->writeln(sprintf(_("MySQL table repair Failed: %s"),$e->getMessage()));
				}
			break;
			case "zendid":
				$output->writeln("===========================");
				foreach(zend_get_id() as $id){
					$output->writeln($id);
				}
				$output->writeln("===========================");
			break;

			default:
				$output->writeln('Invalid argument');
			break;
		}
	}
}
