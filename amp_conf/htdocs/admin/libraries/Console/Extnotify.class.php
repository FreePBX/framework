<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Extnotify extends Command {
	protected function configure(){
    $this->FreePBX = \FreePBX::Create();
		$this->FreePBXConf = $this->FreePBX->Config();
    $this->nt = \notifications::create($this->FreePBX->Database);
    $this->cm = \cronmanager::create($this->FreePBX->Database);
    $this->mid = $this->cm->get_machineid();
    $this->email = $this->cm->get_email();
    $this->brand = $this->FreePBXConf->get('DASHBOARD_FREEPBX_BRAND')?$this->FreePBXConf->get('DASHBOARD_FREEPBX_BRAND'):'FreePBX';
		$this->setName('extnotify')
		->setDescription(_('Sends Scheduled Notifications'))
		->setDefinition(array(
			new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$this->cm->run_jobs();
    $this->updateSecurity();
    if($this->email){
      $this->unsignedEmail();
      $this->securityEmail();
      $this->updateEmail();
      $this->hookEmails();
    }else{
      $this->nt->add_notice('freepbx', 'NOEMAIL', _('No email address for online update checks'), _('You are automatically checking for online updates nightly but you have no email address setup to send the results. This can be set in Module Admin. They will continue to show up here.'), 'config.php?display=modules#email', 'PASSIVE', false);
    }
	}
  protected function send_email($to,$subject,$message,$priority = 3){
    $em = clone \FreePBX::Email();
    $em->setTo(array($to));
    $em->setSubject($subject);
    $em->setBody($message);
    $em->setPriority($priority);
    return $em->send();
  }
  protected function updateSecurity(){
    if($this->FreePBXConf->get('AUTOSECURITYUPDATES')) {
    	$mf = \module_functions::create();
    	$mods = $mf->get_security();
    	$mods = is_array($mods) ? $mods : array();
    	if(!empty($mods)) {
    		set_time_limit(0); //we NEED these updates
    		$ampsbin = $this->FreePBXConf->get('AMPSBIN');
    		$errorvuls = array();
    		foreach($mods as $rawname => $info) {
    			$mi = $mf->getinfo($rawname);
    			if(!isset($mi[$rawname])) {
    				//module doesnt exist on this system
    				continue;
    			}
    			switch($mi[$rawname]['status']) {
    				case MODULE_STATUS_NOTINSTALLED:
    				case MODULE_STATUS_DISABLED:
    				case MODULE_STATUS_BROKEN:
    					$action = "download";
    				break;
    				case MODULE_STATUS_ENABLED:
    				case MODULE_STATUS_NEEDUPGRADE:
    					$action = "upgrade";
    				break;
    				default:
    					$action = "";
    				break;
    			}
    			if(empty($action)) {
    				//not sure what to do???
    				$errorvuls[$rawname] = $info;
    				continue;
    			}
    			exec($ampsbin."/fwconsole ma ".escapeshellarg($action)." ".escapeshellarg($rawname)." --format=json",$out,$ret);
    			if($ret != 0) {
    				$errorvuls[$rawname] = $info;
    			}
    		}
    		$this->nt->delete("freepbx", "VULNERABILITIES");
    		if(!empty($errorvuls)) {
    			//unable to automatically update these modules
    			if (!empty($errorvuls)) {
    				$cnt = count($errorvuls);
    				if ($cnt == 1) {
    					$text = _("There is 1 module vulnerable to security threats");
    				} else {
    					$text = sprintf(_("There are %s modules vulnerable to security threats"), $cnt);
    				}
    				$extext = "";
    				foreach($errorvuls as $m => $vinfo) {
    					$extext .= sprintf(
    						_("%s (Cur v. %s) should be upgraded to v. %s to fix security issues: %s")."\n",
    						$m, $vinfo['curver'], $vinfo['minver'], implode($vinfo['vul'],', ')
    					);
    				}
    				$this->nt->add_security('freepbx', 'VULNERABILITIES', $text, $extext, 'config.php?display=modules');
    			}
    		} else {
    			$text = _("Modules vulnerable to security threats have been updated");
    			foreach($mods as $m => $vinfo) {
    				$extext .= sprintf(
    					_("%s has been automatically upgraded to fix security issues: %s")."\n",
    					$m, implode($vinfo['vul'],', ')
    				);
    			}
    			$extext = $extext.". "._("You can disable this in advanced settings under 'Allow Automatic Security Updates'");
    			$this->nt->add_notice('freepbx', 'AUTOVULNUPDATE', $text, $extext, 'config.php?display=modules',true,true);
    		}
    	}
    }
  }
  protected function unsignedEmail(){
    if(!$this->FreePBXConf->get('SEND_UNSIGNED_EMAILS_NOTIFICATIONS')){
      return true;
    }
    $send_email = false;

    $unsigned = $this->nt->list_signature_unsigned();
    $text = '';
    if (count($unsigned)) {
      $send_email = true;
      $text = $htext;
      $text .= "\n" . _("UNSIGNED MODULES NOTICE:")."\n\n";
      foreach ($unsigned as $item) {
        $text .= $item['display_text'].":\n";
        $text .= $item['extended_text']."\n";
      }
    }
    $text .= "\n\n";

    if ($send_email && (! $this->cm->check_hash('update_sigemail', $text))) {
      $this->cm->save_hash('update_sigemail', $text);
      if ($this->send_email($this->email, sprintf(_("%s: New Unsigned Modules Notifications (%s)"),$this->brand, $this->mid), $text)) {
        $this->nt->delete('freepbx', 'SIGEMAILFAIL');
      } else {
        $this->nt->add_error('freepbx', 'SIGEMAILFAIL', _('Failed to send unsigned modules notification email'), sprintf(_('An attempt to send email to: %s with unsigned modules notifications failed'),$this->email));
      }
    }
  }
  protected function securityEmail(){
    $text = "";
  	$send_email = false;

  	$security = $this->nt->list_security();
  	if (count($security)) {
  		$send_email = true;
  		$text = $htext . "\n";
  		$text .= _("SECURITY NOTICE:")."\n\n";
  		foreach ($security as $item) {
  			$text .= $item['display_text'].":\n";
  			$text .= $item['extended_text']."\n";
  		}
  	}
  	$text .= "\n\n";

  	if ($send_email && (! $this->cm->check_hash('update_semail', $text))) {
  		$this->cm->save_hash('update_semail', $text);
  		if ($this->send_email($this->email, sprintf(_("%s: New Security Notifications (%s)"),$this->brand, $this->mid), $text)) {
  			$this->nt->delete('freepbx', 'SEMAILFAIL');
  		} else {
  			$$this->nt->add_error('freepbx', 'SEMAILFAIL', _('Failed to send security notification email'), sprintf(_('An attempt to send email to: %s with security notifications failed'),$this->email));
  		}
  	}
  }
  protected function updateEmail(){
    $text = "";
  	$send_email = false;

  	$updates = $this->nt->list_update();
  	if (count($updates)) {
  		$send_email = true;
  		$text = $htext . "\n";
  		$text .= _("UPDATE NOTICE:")."\n\n";
  		foreach ($updates as $item) {
  			$text .= $item['display_text']."\n";
  			$text .= $item['extended_text']."\n\n";
  		}
  	}

  	if ($send_email && (! $this->cm->check_hash('update_email', $text))) {
  		$this->cm->save_hash('update_email', $text);
  		if ($this->send_email($this->email, sprintf(_("%s: New Online Updates Available (%s)"),$brand,$mid), $text)) {
  			$this->nt->delete('freepbx', 'EMAILFAIL');
  		} else {
  			$this->nt->add_error('freepbx', 'EMAILFAIL', _('Failed to send online update email'), sprintf(_('An attempt to send email to: %s with online update status failed'),$this->email));
  		}
  	}
  }
  protected function hookEmails(){
    $hooks = \FreePBX::Hooks()->processHooks();
    foreach ($hooks as $key => $value) {
      if(!isset($value['message'])){
        continue;
      }
      $to = isset($value['to'])?$value['to']:$this->email;
      $subject = isset($value['subject'])?$value['subject']:sprintf(_("Automated notification from %s"),$key);
      $message = $value['$message'];
      $priority = isset($value['priority'])?$value['priority']:3;
      if ((!$this->cm->check_hash($key.'_email', $message))) {
        $this->cm->save_hash($key.'_email', $message);
        if($this->send_email($to, $subject, $message, $priority)){
          $this->nt->delete('freepbx', 'EMAILFAIL');
        }else{
          $this->nt->add_error('freepbx', 'EMAILFAIL', _('Failed to send online hook email'), sprintf(_('An attempt to send email to: %s from %s failed'),$this->email, $key));
        }
      }
    }
  }
}
