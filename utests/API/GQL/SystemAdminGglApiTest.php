<?php 

namespace FreepPBX\framework\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\framework;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

/**
 * SystemAdminGqlApiTest
 */
class SystemAdminGqlApiTest extends ApiBaseTestCase {
    protected static $sysadmin;
        
    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass() {
      parent::setUpBeforeClass();
    }
        
    /**
     * tearDownAfterClass
     *
     * @return void
     */
    public static function tearDownAfterClass() 
    {
      parent::tearDownAfterClass();
    }
      
    /**
     * testaddInitialSetupRequiredParmaterNotSentShouldReturnfalse
     *
     * @return void
     */
    public function testaddInitialSetupRequiredParmaterNotSentShouldReturnfalse(){
      $username = 'test';
      $password = 'test';

      $response = $this->request("mutation {
        addInitialSetup(input: { 
          username: \"{$username}\" 
          password: \"{$password}\" }) 
          { status message }
        }
      ");

		$json = (string)$response->getBody();

      $this->assertEquals('{"errors":[{"message":"Field updateAdminAuthInput.notificationEmail of required type String! was not provided.","status":false}]}', $json);
      
      $this->assertEquals(400, $response->getStatusCode());
    }
    
    /**
     * testaddInitialSetupWhenDuplicateEntriesShouldReturnfalse
     *
     * @return void
     */
    public function testaddInitialSetupWhenDuplicateEntriesShouldReturnfalse(){
      $db = self::$freepbx->Database();
      $sql = $db->prepare("DELETE FROM `ampusers` where username like ?");
		  $sql->execute(array("%test%"));
      $pass = rand();

      $sql = $db->prepare("INSERT INTO `ampusers` (`username`, `password_sha1`, `sections`) VALUES ( ?, ?, '*')");
	  	$sql->execute(array("test", $pass));

      $response = $this->request("mutation {
        addInitialSetup(input: { 
         username: \"test\" 
         password: \"{$pass}\" 
         notificationEmail: \"test@gmail.com\"
         systemIdentifier: \"VOIP Server\"
         autoModuleUpdate: \"enabled\"
         autoModuleSecurityUpdate: \"enabled\"
         securityEmailUnsignedModules: \"test@test.com\"
         updateDay: \"monday\"
         updatePeriod: \"0to4\"

         }) { status message }
        }
      ");

		$json = (string)$response->getBody();

    $this->assertEquals('{"data":{"addInitialSetup":{"status":true,"message":"Admin user already exists, updating other parameters"}}}', $json);
    $this->assertEquals(200, $response->getStatusCode());
    }
   
   /**
    * testaddInitialSetAllGoodShouldReturnfalse
    *
    * @return void
    */
   public function testaddInitialSetAllGoodShouldReturnfalse(){
      $db = self::$freepbx->Database();
      $sql = $db->prepare("DELETE FROM `ampusers` where username like ?");
      $sql->execute(array("%test%"));
      $pass = rand();

      $response = $this->request("mutation {
        addInitialSetup(input: { 
         username: \"test\" 
         password: \"{$pass}\"
         notificationEmail: \"test@gmail.com\"
         systemIdentifier: \"VOIP Server\"
         autoModuleUpdate: \"enabled\"
         autoModuleSecurityUpdate: \"enabled\"
         securityEmailUnsignedModules: \"test@test.com\"
         updateDay: \"monday\"
         updatePeriod: \"0to4\"

         }) { status message }
        }
      ");

		  $json = (string)$response->getBody();
      $this->assertEquals('{"data":{"addInitialSetup":{"status":true,"message":"Initial Setup is completed"}}}', $json);
      $this->assertEquals(200, $response->getStatusCode());

      $sth = $db->prepare("DELETE FROM `ampusers` where username like ?");
		  $sql->execute(array("%test%"));
   }
}