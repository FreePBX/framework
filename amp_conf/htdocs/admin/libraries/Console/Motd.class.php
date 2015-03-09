<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Motd extends Command {
	private $errors = array();
	protected function configure(){
		$this->setName('motd')
		->setDescription(_('Prints MOTD'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
		$this->brand = "FreePBXDistro";
		//banners should be base64 encoded, Why? Because php likes to randomly shift multiline variables. Also makes it all look super cryptic :-/ 
		$this->banner = "IF9fX19fICAgICAgICAgICAgICBfX19fICBfX19fX18gIF9fDQp8ICBfX198IF9fIF9fXyAgX19ffCAgXyBcfCBfXyApIFwvIC8NCnwgfF8gfCAnX18vIF8gXC8gXyBcIHxfKSB8ICBfIFxcICAvIA0KfCAgX3x8IHwgfCAgX18vICBfXy8gIF9fL3wgfF8pIC8gIFwgDQp8X3wgIHxffCAgXF9fX3xcX19ffF98ICAgfF9fX18vXy9cX1wgICAgIA==";
		$this->supporturl = 'http://www.freepbx.org/support-and-professional-services';
		$this->FreePBX = \FreePBX::Create();
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$alerts = $this->FreePBX->Notifications->get_num_active();
		$output->write(base64_decode($this->banner));
		$output->writeln("");
		$output->writeln("");
		if($alerts != 0){
			$output->writeln(_("<fg=red>NOTICE: YOU HAVE: ") . $alerts . _(" NOTIFICATIONS PLEASE LOG IN TO THE UI TO SEE THEM! </fg=red>"));
		}
		$output->writeln("");
		$output->writeln("<info>"._("NETWORK")."</info>");
		$iflist = $this->listIFS();
		if($iflist){
			$rows = array();
			foreach($iflist as $if => $info){
				$rows[] = array($if,$info['mac'],$info['ip']);
			}
			$table = new Table($output);
			$table
				->setHeaders(array('INTERFACE','MAC ADDRESS','IP ADDRESS'))
				->setRows($rows);
			$table->render();	
		}else{
			$output->writeln("-------------------");	
			$output->writeln(_("No interfaces found"));	
			$output->writeln("-------------------");	
		}
		$output->writeln("");
		$output->writeln(_("Please note most tasks should be handled through the ") . $this->brand . _(" UI."));
		$output->writeln(_("You can access the ") . $this->brand ._(" GUI by typing one of the above IP's in to your web browser."));
		$output->writeln(_("For support please visit: ") . $this->supporturl);
		$output->writeln("");
	}
	private function listIFS(){
		$iflist = array();
		$ifs = scandir('/sys/class/net/');		
		foreach($ifs as $if){
			if($if == '.' || $if == '..'){
				continue;
			}
			$iftype = file_get_contents('/sys/class/net/' . $if . '/type');
			if($iftype != 1){
				continue;
			}
			$MAC = file_get_contents('/sys/class/net/' . $if . '/address');
			$MAC = strtoupper($MAC);
			$ip = shell_exec("/sbin/ifconfig " . $if . " | grep 'inet addr:'| cut -d: -f2 | awk '{ print $1}'");
			$iflist[$if] = array('mac' => $MAC, 'ip' => $ip);
		}
		return $iflist;
	}	
}
