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


class Cron extends Command {
	protected function configure() {
		$this->setName('cron')
		->setDescription(_('Centralized cron management'))
		->setDefinition(array(
			new InputOption('run', '', InputOption::VALUE_OPTIONAL, _('Download/Upgrade forcing edge mode')),
		));
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		if(is_null($input->getParameterOption('--run'))) {
			//run all
			$this->run();
		}
		if($input->getOption('run')) {
			//run specific
		}
	}

	private function run() {
		$job_list = $this->findAllJobs();
		$jobby = new \Jobby\Jobby();

		$this->registerTasks( $jobby, $job_list );

		$jobby->run();
	}

	private function registerTasks( \Jobby\Jobby $runner, $task_list ) {
		foreach( $task_list as $task ) {
			$task_class = $this->crony_job_namespace . '\\' . $task;
			// Per the interface...
			$task_config = call_user_func( $task_class . '::config' );
			// If there's no command registered in the configuration, we'll bind an anonymous function to
			// run our specified task.
			if ( !isset( $task_config['command'] ) ) {
				$task_config['command'] = function() use ($task_class) { return call_user_func( $task_class . '::run' ); };
			}

			$runner->add( $task, $task_config );
		}
	}

	public function getJobList() {
		return $this->findAllJobs();
	}

	private function findAllJobs() {
		$jobs = array();
		foreach( glob( $this->crony_job_root . '/*.php') as $job_file ) {
			$job_class = basename( $job_file, '.php' );
			// Before we include this job in the job list, we need to make sure it implements \Crony\TaskInterface
			$implementations = class_implements( $this->crony_job_namespace . '\\' . $job_class );
			if ( in_array( 'Crony\TaskInterface', $implementations ) ) {
				array_push( $jobs, $job_class );
			}
		}
		return $jobs;
	}
}