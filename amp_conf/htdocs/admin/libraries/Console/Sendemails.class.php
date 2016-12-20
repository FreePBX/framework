<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Sendemails extends Command {

	private $FreePBX;
	private $brand;
	private $nt;
	private $updatemanager;
	private $email_to;
	private $machine_id;
	private $forcesend;

	protected function configure() {
		$this->FreePBX = \FreePBX::Create();
		$this->brand = $this->FreePBX->Config()->get('DASHBOARD_FREEPBX_BRAND');

		$this->setName('sendemails')
			->setDefinition([
				new InputOption('force', 'f', InputOption::VALUE_NONE, _('Force email sending')),
				new InputOption('securityonly', '', InputOption::VALUE_NONE, _('Only process security emails')),
				new InputOption('willupdate', '', InputOption::VALUE_NONE, _('Adds a warning in the email that modules will be updated in one hour')),
			])
			->setDescription(_('Generates and sends Scheduled Notification emails'));
	}

	public function getFromEmail() {
		// This is our default
		$fromemail = get_current_user() . '@' . gethostname();

		// Sysadmin lets you override the 'from' address.
		if(function_exists('sysadmin_get_storage_email')){
			$emails = sysadmin_get_storage_email();
			//Check that what we got back above is a email address
			if(!empty($emails['fromemail']) && filter_var($emails['fromemail'],FILTER_VALIDATE_EMAIL)){
				$fromemail = $emails['fromemail'];
			}
		}

		return $fromemail;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->nt = \notifications::create($this->FreePBX->Database);
		$this->updatemanager = new \FreePBX\Builtin\UpdateManager();
		$settings = $this->updatemanager->getCurrentUpdateSettings(false); // Don't html encode the output
		$this->email_to = $settings['notification_emails'];
		$this->machine_id = $settings['system_ident'];
		$this->force = $input->getOption('force');

		$this->email_from = $this->getFromEmail();

		if ($this->email_from) {
			// Clear out any warnings about emails
			$this->nt->delete('freepbx', 'NOEMAIL');
		} else {
			// Add our notificaiton about not having an email.
			$this->nt->add_notice('freepbx', 'NOEMAIL', _('No email address for online update checks'), _('There is no email address configured to send the results of updates and security issues.'), 'config.php?display=modules#email', 'PASSIVE', false);
		}

		// Check for security vulnerabilities and update
		$this->updateSecurity();

		// Generate (and potentially send) our emails
		if (!$input->getOption('securityonly')) {
			$this->unsignedEmail();
			$this->securityEmail();
			$this->updateEmail($input->getOption("willupdate"));
		}
		$this->hookEmails();
	}

	protected function updateSecurity() {
		if(!$this->FreePBX->Config()->get('AUTOSECURITYUPDATES')) {
			// Should we put a notification here?
			return;
		}

		// We're doing security updates.
		$mf = \module_functions::create();
		$mods = (array) $mf->get_security();

		if (!$mods) {
			// Easy. No security vulnerabilities!
			return;
		}

		// OK, There are security vulnerabilities. Prepare our email.
		$email_subject = sprintf(_("%s Security Alert (%s)"), $this->brand, $this->machine_id);
		$email_body = sprintf(_("Your server discovered the following security issues:"), $this->brand)."\n";
		$send_email = false;

		set_time_limit(0); // Never time out.
		$ampsbin = $this->FreePBX->Config()->get('AMPSBIN');

		$errorvuls = []; // This will contain anything that's not fixed

		foreach($mods as $rawname => $info) {
			$mi = $mf->getinfo($rawname);
			if(!isset($mi[$rawname])) {
				//module doesnt exist on this system
				continue;
			}
			// We've made it here, we ARE sending an email
			$send_email = true;

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
			}

			if(!$action) {
				// not sure what to do??? This is probably a bug with a new MODULE_STATUS
				// not being handled correctly
				$errorvuls[$rawname] = $info;
				continue;
			}

			// Upgrade/install/whatever our mod
			exec($ampsbin."/fwconsole ma $action ".escapeshellarg($rawname)." --format=json",$out,$ret);

			// If this failed...
			if($ret != 0) {
				$errorvuls[$rawname] = $info;
			}

			// Remove any old notifications.
			$this->nt->delete("freepbx", "VULNERABILITIES");

			$notification_title = "";
			$notification_body = "";

			if(!empty($errorvuls)) {
				// There were issues upgrading some modules.
				$cnt = count($errorvuls);
				if ($cnt == 1) {
					$emailbody .= "\n\n"._("WARNING: There was an issue automatically repairing the security vulnerabilities on the following module. This module requires manual attention urgently:")."\n";
					$notification_title = _("There is 1 module vulnerable to security threats");
				} else {
					$emailbody .= "\n\n"._("WARNING: There were issues automatically repairing the security vulnerabilities on the following modules. They require manual attention urgently:")."\n";
					$notification_title = sprintf(_("There are %s modules vulnerable to security threats"), $cnt);
				}
				foreach($errorvuls as $m => $vinfo) {
					$line = sprintf(
						_("%s (Cur v. %s) should be upgraded to v. %s to fix security issues: %s")."\n",
						$m, $vinfo['curver'], $vinfo['minver'], implode($vinfo['vul'],', ')
					);
					$notification_body .= $line;
					$email_body .= "    $line";
				}
			} else {
				$text = $emailbody = _("Modules vulnerable to security threats have been automatically updated");
				foreach($mods as $m => $vinfo) {
					$line = sprintf(
						_("%s has been automatically upgraded to fix security issues: %s")."\n",
						$m, implode($vinfo['vul'],', ')
					);
					$notification_body .= $line;
					$email_body .= "    $line";
				}
			}
		}
		$this->nt->add_security('freepbx', 'VULNERABILITIES', $notification_title, $notification_body, 'config.php?display=modules');
		$this->updatemanager->sendEmail("vulnerabilities", $this->email_to, $this->email_from, $email_subject, $email_body, 4, $this->force);
	}

	private function getUnsignedEmailBody() {
		if (!$this->FreePBX->Config()->get('SEND_UNSIGNED_EMAILS_NOTIFICATIONS')) {
			$warning = _("Unsigned module notifications are turned off!")."\n\n";
			$warning .= _("You will not get alerts about new modules that are installed on your system without a valid signature. It is unusual to turn off this protection. You can turn it back on in 'Advanced Settings' by enabling the 'SEND_UNSIGNED_EMAILS_NOTIFICATIONS' option.")."\n";
			$warning .= _("If you want to sign your own modules to protect them from unauthorized tampering, please see the link below for more information:")."\n";
			$warning .= "    http://wiki.freepbx.org/display/FOP/Signing+your+own+modules\n";
			return $warning;
		}
		// Now see if there ARE any unsigned modules to complain about
		$unsigned = $this->nt->list_signature_unsigned();
		if (!$unsigned) {
			return "";
		}
		$warning = _("UNSIGNED MODULES DETECTED:")."\n\n";
		$warning .= _("Warning: It is unusual to have Unsigned modules on your system!")."\n";
		$warning .= sprintf(_("There are several ways to protect modules against tampering in %s. Please see the wiki page on module signing for more information:"), $this->brand)."\n";
		$warning .= "    http://wiki.freepbx.org/display/FOP/Signing+your+own+modules\n\n";
		foreach ($unsigned as $item) {
			$warning .= $item['display_text'].":\n";
			$warning .= $this->updatemanager->cleanHtml($item['extended_text'])."\n";
		}
		return $warning;
	}

	protected function unsignedEmail() {
		if(!$this->FreePBX->Config()->get('SEND_UNSIGNED_EMAILS_NOTIFICATIONS')){
			$this->nt->delete("freepbx", "SIGEMAIL");
			return true;
		}

		$email_body = $this->getUnsignedEmailBody();
		if (!$email_body) {
			$this->nt->delete("freepbx", "SIGEMAIL");
			return true;
		}

		$email_subject = sprintf(_("%s Unsigned Module Alert (%s)"), $this->brand, $this->machine_id);

		// Send an email if we need to.
		$sent = $this->updatemanager->sendEmail("unsigned", $this->email_to, $this->email_from, $email_subject, $email_body, 4, $this->force);
		
		if (!$sent) {
			$this->nt->add_error('freepbx', 'SIGEMAIL', _('Failed to send unsigned modules notification email'), sprintf(_('An attempt to send an email to "%s" with an alert about unsigned modules notifications failed'),$this->email_to));
		}
	}

	protected function securityEmail() {

		$security = $this->nt->list_security();
		if (!$security) {
			return;
		}

		$email_subject = sprintf(_("%s Security Alert (%s)"), $this->brand, $this->machine_id);
		$email_body = $this->getUnsignedEmailBody();

		$email_body .= _("SECURITY NOTICE:")."\n\n";
		foreach ($security as $item) {
			$email_body .= $item['display_text'].":\n";
			$email_body .= $this->updatemanager->cleanHtml($item['extended_text'])."\n";
		}
		$email_body .= "\n\n";

		// Send an email if we need to.
		$sent = $this->updatemanager->sendEmail("security", $this->email_to, $this->email_from, $email_subject, $email_body, 4, $this->force);
		
		if (!$sent) {
			$this->nt->add_error('freepbx', 'SECEMAIL', _('Failed to send security notification email'), sprintf(_('An attempt to send an email to "%s" with security notifications failed'),$this->email_to));
		}
	}

	protected function updateEmail($willupdate = false) {

		$updates = $this->nt->list_update();
		if (!$updates) {
			return;
		}

		$email_subject = sprintf(_("%s Updates Notification (%s)"), $this->brand, $this->machine_id);
		$email_body = _("Module updates are available.")."\n\n";
		$email_body .= $this->getUnsignedEmailBody()."\n";
		if ($willupdate) {
			$email_body .= "\n"._("Automatic updates are enabled!  These modules will be automatically updated in one hours time.")."\n\n";
		}
		foreach ($updates as $item) {
			$email_body .= $item['display_text']."\n";
			$email_body .= $item['extended_text']."\n\n";
		}

		// Send an email if we need to.
		$sent = $this->updatemanager->sendEmail("updates", $this->email_to, $this->email_from, $email_subject, $email_body, 4, $this->force);
		
		if ($sent) {
			$this->nt->delete('freepbx', 'UPDATEEMAIL');
		} else {
			$this->nt->add_error('freepbx', 'UPDATEEMAIL', _('Failed to send update notification email'), sprintf(_('An attempt to send an email to "%s" with update notifications failed'),$this->email_to));
		}
	}

	protected function hookEmails(){
		$hooks = \FreePBX::Hooks()->processHooks();
		foreach ($hooks as $key => $value) {
			if(!isset($value['message'])){
				continue;
			}
			$to = isset($value['to'])?$value['to']:$this->email_to;
			$subject = isset($value['subject'])?$value['subject']:sprintf(_("Automated notification from %s"),$this->machine_id);
			$message = $value['$message'];
			$priority = isset($value['priority'])?$value['priority']:3;
			$hashkey = $key."_email";

			if ($this->updatemanager->sendEmail($hashkey, $to, $this->email_from, $subject, $message, $priority, 4, $this->force)) {
				$this->nt->delete('freepbx', 'EMAILFAIL');
			} else {
				$this->nt->add_error('freepbx', 'EMAILFAIL', _('Failed to send online hook email'), sprintf(_('An attempt to send email to "%s" from "%s" failed'),$this->email_to, $key));
			}
		}
	}
}
