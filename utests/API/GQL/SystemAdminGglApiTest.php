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

      $response = $this->request("mutation {
        addInitialSetup(input: { 
         username: \"test\" 
         password: \"test\" 
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

      $this->assertEquals('{"errors":[{"message":"Admin user already exists","status":false}]}', $json);
      
      $this->assertEquals(400, $response->getStatusCode());
    }

   public function testaddInitialSetAllGoodShouldReturnfalse(){

      $response = $this->request("mutation {
        addInitialSetup(input: { 
         username: \"testuser\" 
         password: \"testuser\" 
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
   }
}