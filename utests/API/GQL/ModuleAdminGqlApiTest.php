<?php 

namespace FreepPBX\framework\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\framework;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

class ModuleAdminGqlApiTest extends ApiBaseTestCase {
    protected static $sysadmin;
    
    public static function setUpBeforeClass() {
      parent::setUpBeforeClass();
    }
    
    public static function tearDownAfterClass() 
    {
      parent::tearDownAfterClass();
    }
  
    public function testModuleOperationswhenHookExcuteShoudReturnTrue()
    {
      $module = 'core';
      $action = 'install';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        moduleOperations(input: { 
          module: \"{$module}\" 
          action: \"{$action}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

      $this->assertEquals('{"data":{"moduleOperations":{"status":true,"message":"Action['.$action.'] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
      
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testModuleOperationswhenActionParamNotSentWillReturnErrors()
    {
      $module = 'core';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        moduleOperations(input: { 
          module: \"{$module}\"  }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"errors":[{"message":"Field moduleOperationsInput.action of required type String! was not provided.","status":false}]}', $json);
      
      $this->assertEquals(400, $response->getStatusCode());
    }

    public function testModuleOperationsWhenModuleParamNotSentWillReturnErrors()
    {
      $action = 'install';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);   

      $response = $this->request("mutation {
        moduleOperations(input: { 
          action: \"{$action}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

      $this->assertEquals('{"errors":[{"message":"Field moduleOperationsInput.module of required type String! was not provided.","status":false}]}', $json);
      
      $this->assertEquals(400, $response->getStatusCode());
    }

    public function testInstallModuleShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        installModule(input: { 
          module: \"{$module}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"installModule":{"status":true,"message":"Action[install] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
      
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInstallModuleWithDownloadShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        installModule(input: { 
          module: \"{$module}\"
          forceDownload:true}) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"installModule":{"status":true,"message":"Action[downloadinstall] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
      
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInstallModuleWithDownloadSetFalseShoudReturnTrueWithOnlyInstall(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        installModule(input: { 
          module: \"{$module}\"
          forceDownload:false}) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"installModule":{"status":true,"message":"Action[install] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
      
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUninstallModuleShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        uninstallModule(input: { 
          module: \"{$module}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"uninstallModule":{"status":true,"message":"Action[uninstall] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
    
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUninstallModuleWithRemoveSetToTrueShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        uninstallModule(input: { 
          module: \"{$module}\"
          RemoveCompletely:true}) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"uninstallModule":{"status":true,"message":"Action[remove] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
   
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUninstallModuleWithRemoveSetToFalseShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        uninstallModule(input: { 
          module: \"{$module}\"
          RemoveCompletely:false}) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"uninstallModule":{"status":true,"message":"Action[uninstall] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
   
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testEnableModuleShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        enableModule(input: { 
          module: \"{$module}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"enableModule":{"status":true,"message":"Action[enable] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
    
      $this->assertEquals(200, $response->getStatusCode());
    }

     public function testDisableModuleShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        disableModule(input: { 
          module: \"{$module}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"disableModule":{"status":true,"message":"Action[disable] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
   
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUgradeModuleShoudReturnTrue(){
      
      $module = 'xmpp';

      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        upgradeModule(input: { 
          module: \"{$module}\" }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"upgradeModule":{"status":true,"message":"Action[upgrade] on module['.$module.'] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
   
      $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUgradeAllModulesShoudReturnTrue(){
  
      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('initiateGqlAPIProcess'))
       ->getMock();

      $mockGqlHelper->method('initiateGqlAPIProcess')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        upgradeAllModules(input: { }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"upgradeAllModules":{"status":true,"message":"Action[upgradeAll] on module[] has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
    
      $this->assertEquals(200, $response->getStatusCode());
    }
    
    /**
     * testDoreoadShouldReturnTrue
     *
     * @return void
     */
    public function testDoreoadShouldReturnTrue(){
  
      $mockGqlHelper = $this->getMockBuilder(Freepbx\api\Api::class)
       ->disableOriginalConstructor()
       ->setMethods(array('doreload'))
       ->getMock();

      $mockGqlHelper->method('doreload')->willReturn(true);

     self::$freepbx->Api()->setObj($mockGqlHelper);  

      $response = $this->request("mutation {
        doreload(input: { }) 
          { status message }
        }
      ");

		  $json = (string)$response->getBody();

	  	$this->assertEquals('{"data":{"doreload":{"status":true,"message":"Doreload\/apply config has been initiated. Please check the status using fetchApiStatus api with the returned transaction id"}}}', $json);
    
      $this->assertEquals(200, $response->getStatusCode());
    }
        
    /**
     * testYumUpgradeAllTrueShoudReturnTrue
     *
     * @return void
     */
    public function testYumUpgradeWhenTrueShoudReturnTrue(){
      $mockHelper = $this->getMockBuilder(Freepbx\Builtin\SystemUpdate::class)
       ->disableOriginalConstructor()
       ->setMethods(array('startYumUpdate'))
       ->getMock();

      $mockHelper->method('startYumUpdate')
      ->willReturn(true);

      self::$freepbx->Framework()->setSystemObj($mockHelper);  

      $response = $this->request("mutation {
        updateSystemRPM(input: {}){
          status
          message   
        }
      }");

    $json = (string)$response->getBody();
  
    $this->assertEquals('{"data":{"updateSystemRPM":{"status":true,"message":"Yum Upgrade has been initiated. Please check the status using yumUpgradeStatus api"}}}',$json);
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  /**
   * testYumUpgradeWhenFalseShoudReturnFasle
   *
   * @return void
   */
  public function testYumUpgradeWhenFalseShoudReturnFasle(){
    $mockHelper = $this->getMockBuilder(Freepbx\Builtin\SystemUpdate::class)
       ->disableOriginalConstructor()
       ->setMethods(array('startYumUpdate'))
       ->getMock();

      $mockHelper->method('startYumUpdate')
      ->willReturn(false);

      $response = $this->request("mutation {
        updateSystemRPM(input: {}){
          status
          message   
        }
      }");

      self::$freepbx->Framework()->setSystemObj($mockHelper);  

    $json = (string)$response->getBody();
  
    $this->assertEquals('{"data":{"updateSystemRPM":{"status":true,"message":"Yum Upgrade is already running"}}}',$json);
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  /**
   * testYumUpgradeStausWhencompleteShoudReturnTrue
   *
   * @return void
   */
  public function testYumUpgradeStausWhencompleteShoudReturnTrue(){
    $mockHelper = $this->getMockBuilder(Freepbx\Builtin\SystemUpdate::class)
       ->disableOriginalConstructor()
       ->setMethods(array('getYumUpdateStatus'))
       ->getMock();

      $mockHelper->method('getYumUpdateStatus')
      ->willReturn(array('status'=>'complete'));

      self::$freepbx->Framework()->setSystemObj($mockHelper);  

      $response = $this->request("query{
          fetchRPMUpgradeStatus{
          status
          message
        }
      }");

    $json = (string)$response->getBody();
  
    $this->assertEquals('{"data":{"fetchRPMUpgradeStatus":{"status":true,"message":"Yum upgrade is completed"}}}',$json);
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  /**
   * testYumUpgradeStausWhenInprogressShoudReturnTrue
   *
   * @return void
   */
  public function testYumUpgradeStausWhenInprogressShoudReturnTrue(){
    $mockHelper = $this->getMockBuilder(Freepbx\Builtin\SystemUpdate::class)
       ->disableOriginalConstructor()
       ->setMethods(array('getYumUpdateStatus'))
       ->getMock();

      $mockHelper->method('getYumUpdateStatus')
      ->willReturn(array('status'=>'inprogress'));

      self::$freepbx->Framework()->setSystemObj($mockHelper); 

      $response = $this->request("query{
          fetchRPMUpgradeStatus{
          status
          message
        }
      }");

    $json = (string)$response->getBody();
  
    $this->assertEquals('{"data":{"fetchRPMUpgradeStatus":{"status":true,"message":"Yum upgrade is in progress"}}}',$json);
    $this->assertEquals(200, $response->getStatusCode());
  }
  
  /**
   * testYumUpgradeStausWhenFailedShoudReturnFalse
   *
   * @return void
   */
  public function testYumUpgradeStausWhenFailedShoudReturnFalse(){
    $mockHelper = $this->getMockBuilder(Freepbx\Builtin\SystemUpdate::class)
       ->disableOriginalConstructor()
       ->setMethods(array('getYumUpdateStatus'))
       ->getMock();

      $mockHelper->method('getYumUpdateStatus')
      ->willReturn(false);

      self::$freepbx->Framework()->setSystemObj($mockHelper); 

      $response = $this->request("query{
          fetchRPMUpgradeStatus{
          message
          status
        }
      }");

    $json = (string)$response->getBody();
  
    $this->assertEquals('{"errors":[{"message":"Sorry, yum upgrade has failed","status":false}]}',$json);
    $this->assertEquals(400, $response->getStatusCode());
  }
}