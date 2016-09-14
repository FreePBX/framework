<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * This is the FreePBX Big Module Object.
 *
 * Class for sending emails with swiftmailer
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */
namespace FreePBX;
class Mail {
	public function __construct() {
    //TODO: Get configs from sysadmin if available
    $transport = $this->getTransport();
    $this->mailer = \Swift_Mailer::newInstance($transport);
    $from_email = \get_current_user() . '@' . \gethostname();
    if(function_exists('\\sysadmin_get_storage_email')){
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
    $this->mail->setFrom(array($from_email => _("PBX Message")));
    $this->mail->setSubject(sprintf(_("Notification from %s"),$brand));
    $this->toset = false;
    $this->bodyset = false;
    $this->attachmentset = false;
    $this->multipart = false;
    $this->mail->setPriority(3); //Normal
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
  public function addAttachment($parh){
    $this->mail->attach(\Swift_Attachment::fromPath($path));
    $this->attachmentset = true;
  }
  public function setMultipart($text,$html){
    $this->mail->setBody($html,'text/html');
    $this->mail->addPart($text,'text/plain');
    $this->multipart = true;
  }
  public function addHeader($header,$value){
    $this->mail->addTextHeader($header, $value);
  }

  public function send(){
    if($this->toset && ($this->bodyset || $this->multipart)){
      return $this->mailer->send($this->mail);
    }else{
      throw new Exception("Recipient and message must be set", 1);
    }
  }
  private function getTransport(){
    if(function_exists('\\sysadmin_get_email_setup')){
      $emailsettings = \sysadmin_get_storage_email();
    }else{
      return \Swift_SmtpTransport::newInstance('localhost', 25);
    }
    switch ($emailsettings['email_setup_server']) {
      case 'internal':
        return \Swift_SmtpTransport::newInstance('localhost', 25);
      break;
      case 'external':
        switch ($emailsettings['email_setup_provider']) {
          case 'gmail':
            $transport = \Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl');
            $transport->setUsername($emailsettings['email_setup_username']);
            $transport->setPassword($emailsettings['email_setup_password']);
            return $transport;
          break;
          case 'o365':
          $transport = \Swift_SmtpTransport::newInstance('smtp.office365.com', 587, 'tls');
          $transport->setUsername($emailsettings['email_setup_username']);
          $transport->setPassword($emailsettings['email_setup_password']);
          return $transport;
          break;
          case 'other':
          default:
            $transport = \Swift_SmtpTransport::newInstance('localhost', 25);
            if(isset($emailsettings['email_setup_smtp_setver']) && !empty($emailsettings['email_setup_smtp_setver'])){
              $transport->setHost($emailsettings['email_setup_smtp_setver']);
            }
            if(isset($emailsettings['email_setup_auth']) && $emailsettings['email_setup_auth'] === 'yes'){
              if(isset($emailsettings['email_setup_username']) && !empty($emailsettings['email_setup_username'])){
                $transport->setUsername($emailsettings['email_setup_username']);
              }
              if(isset($emailsettings['email_setup_password']) && !empty($emailsettings['email_setup_password'])){
                $transport->setPassword($emailsettings['email_setup_password']);
              }
            }
            if(isset($emailsettings['email_setup_tls']) && $emailsettings['email_setup_tls'] === 'yes'){
              $transport->setPort(587);
              $transport->setEncryption('tls');
            }
            return $transport;
          break;
        }
      break;
      default:
        return \Swift_SmtpTransport::newInstance('localhost', 25);
      break;
    }
  }
}
