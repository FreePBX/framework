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
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;

class Mail {
	private $toset = false;
	private $bodyset = false;
	private $attachmentset = false;
	private $multipart = false;
	private $freepbx;
	private $mailer = null;
	private $message = null;

	public function __construct($freepbx) {
		$this->freepbx = $freepbx;
	}

	/**
	 * Kept so that $mail->mailer and $mail->message keep working for callers
	 * that used them before they became real properties.
	 */
	public function __get($var) {
		switch($var) {
			case 'mailer':
				return $this->getMailer();
			break;
			case 'message':
				return $this->getMessage();
			break;
		}
	}

	/**
	 * Reset Message
	 *
	 * @return void
	 */
	public function resetMessage() {
		$from_email = get_current_user() . '@' . gethostname();

		$this->freepbx->Modules->loadFunctionsInc('sysadmin');

		if(function_exists('sysadmin_get_storage_email')){
			$emails = sysadmin_get_storage_email();
			//Check that what we got back above is a email address
			if(!empty($emails['fromemail']) && filter_var($emails['fromemail'],FILTER_VALIDATE_EMAIL)){
				//Fallback address
				$from_email = $emails['fromemail'];
			}
		}

		//$brand = $this->freepbx->Config->get('DASHBOARD_FREEPBX_BRAND');
		$ident = $this->freepbx->Config->get('FREEPBX_SYSTEM_IDENT');
		$this->getMessage()->setFrom(array($from_email => sprintf(_("%s Message"),$ident)));
		$this->getMessage()->setSubject(sprintf(_("Notification from %s"),$ident));
		$this->getMessage()->setPriority(3); //Normal
		$this->getMessage()->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');
	}

	public function setFrom($email,$name){
		$this->getMessage()->setFrom(array($email => $name));
	}

	//5 = lowest, 4 = low, 3 = Normal, 2 = High, 1 = Highest
	public function setPriority($priority){
		$this->getMessage()->setPriority($priority);
	}

	public function setSubject($subject){
		$this->getMessage()->setSubject($subject);
	}

	public function setTo($recipientArray){
		$this->getMessage()->setTo($recipientArray);
		$this->toset= true;
	}

	public function setBody($body){
		$this->getMessage()->setBody($body);
		$this->bodyset = true;
	}

	public function addAttachment($path){
		$this->getMessage()->attach(\Swift_Attachment::fromPath($path));
		$this->attachmentset = true;
	}

	public function setMultipart($text,$html){
		$this->getMessage()->setBody($html,'text/html');
		$this->getMessage()->addPart($text,'text/plain');
		$this->multipart = true;
	}

	public function addHeader($header,$value){
		$headers = $this->getMessage()->getHeaders();
		$headers->addTextHeader($header, $value);
	}

	public function send(){

		if($this->toset && ($this->bodyset || $this->multipart)){
			return $this->getMailer()->send($this->getMessage());
		}else{
			throw new Exception("Recipient and message must be set", 1);
		}
	}

	public function getMessage() {
		if ($this->message === null) {
			$this->message = new Swift_Message();
			$this->resetMessage();
		}
		return $this->message;
	}

	public function getMailer() {
		if ($this->mailer === null) {
			$this->mailer = new Swift_Mailer(new Swift_SmtpTransport('localhost', 25));
		}
		return $this->mailer;
	}
	
	/**
	 * Function to get mail template form
	 * @param Array $templateData is an array of array which consists of form field data.  
	 */
	public function getEmailTemplateForm($templateData)
	{
		$displayvars = [
			'templateData' => $templateData
		];
		return load_view(__DIR__ . '/../../views/email_template_form.php', $displayvars);
	}

}
