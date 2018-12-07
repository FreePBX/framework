<?php
namespace FreePBX\Console\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class System extends Command {
	private $emailbody = [];
	private $sendemail = false;

	public function __destruct() {
		$this->endOfLife();
	}

	private function endOfLife() {
		if(!$this->sendemail || empty($this->emailbody)) {
			return;
		}

		$brand = \FreePBX::Config()->get('DASHBOARD_FREEPBX_BRAND');
		// We are sending an email.
		$body = array_merge([
			sprintf(_("This is an automatic notification from your %s server."), $brand),
			"",
		], $this->emailbody);

		// Note this is force = true, as we always want to send it.
		$updatemanager = new \FreePBX\Builtin\UpdateManager();
		$updatemanager->sendEmail("systemautoupdates", _("Module Updates"), implode("\n", $body), 4, true);
	}

	protected function configure(){
		$this->setName('systemupdate')
		->setAliases(array('sysup','sys'))
		->setDescription('System Update Administration')
		->setDefinition(array(
			new InputOption('sendemail', '', InputOption::VALUE_NONE, _('Send out finalized email')),
			new InputArgument('args', InputArgument::IS_ARRAY, 'arguments passed to system update admin, this is s stopgap', null)
		));
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		$sysUpdate = new \FreePBX\Builtin\SystemUpdates();
		if(!$sysUpdate->canDoSystemUpdates()) {
			$output->writeln("<error>Sorry system updates can not be performed on this machine</error>");
			exit();
		}

		if(!empty($args)){
			try {
				$this->handleArgs($args,$output);
			} catch(\Exception $e) {
				//run our last minute commands as they wont run later
				$this->endOfLife();
				throw $e;
			}
		}
	}

	private function handleArgs($args,$output){
		switch($action){
			case "listonline":
			break;
		}
	}
}