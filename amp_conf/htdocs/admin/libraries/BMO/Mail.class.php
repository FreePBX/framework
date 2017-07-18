<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Class for sending emails with swiftmailer
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2016 Sangoma Technologies Corporation.
 */
namespace FreePBX;
class Mail {
	public function __construct() {
		//TODO: Get configs from sysadmin if available
		$transport = \Swift_SmtpTransport::newInstance('localhost', 25);
		$this->mailer = \Swift_Mailer::newInstance($transport);
		$from_email = \get_current_user() . '@' . \gethostname();
		if(function_exists('sysadmin_get_storage_email')){
			$emails = \sysadmin_get_storage_email();
			//Check that what we got back above is a email address
			if(!empty($emails['fromemail']) && filter_var($emails['fromemail'],FILTER_VALIDATE_EMAIL)){
				//Fallback address
				$from_email = $emails['fromemail'];
			}
		}
		$brand = \FreePBX::Config()->get('DASHBOARD_FREEPBX_BRAND');
		$brand = ($brand)?$brand:'FreePBX';
		$this->mail = \Swift_Message::newInstance();
		$this->headers = $this->mail->getHeaders();
		$this->mail->setFrom(array($from_email => _("PBX Message")));
		$this->mail->setSubject(sprintf(_("Notification from %s"),$brand));
		$this->toset = false;
		$this->bodyset = false;
		$this->attachmentset = false;
		$this->multipart = false;
		$this->mail->setPriority(3); //Normal
		$this->addHeader('Auto-Submitted', 'auto-generated');
	}

	public function setFrom($email,$name){
		$this->mail->setFrom(array($email => $name));
	}

	//5 = lowest, 4 = low, 3 = Normal, 2 = High, 1 = Highest
	public function setPriority($priority){
		$this->mail->setPriority($priority);
	}

	public function setSubject($subject){
		$this->mail->setSubject($subject);
	}

	public function setTo($recipientArray){
		$this->mail->setTo($recipientArray);
		$this->toset= true;
	}

	public function setBody($body){
		$this->mail->setBody($body);
		$this->bodyset = true;
	}
	public function addAttachment($path){
		$this->mail->attach(\Swift_Attachment::fromPath($path));
		$this->attachmentset = true;
	}
	public function setMultipart($text,$html){
		$this->mail->setBody($html,'text/html');
		$this->mail->addPart($text,'text/plain');
		$this->multipart = true;
	}
	public function addHeader($header,$value){
		$this->headers->addTextHeader($header, $value);
	}

	public function send(){
		if($this->toset && ($this->bodyset || $this->multipart)){
			return $this->mailer->send($this->mail);
		}else{
			throw new Exception("Recipient and message must be set", 1);
		}
	}
}
