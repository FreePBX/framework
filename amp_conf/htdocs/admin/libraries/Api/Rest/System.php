<?php

namespace FreePBX\Api\Rest;
use FreePBX\modules\Api\Rest\Base;
class System extends Base {
	protected $module = 'framework';
	public static function getScopes() {
		return [
			'read:system' => [
				'description' => _('Read system information'),
			]
		];
	}

	public function setupRoutes($app) {
		$freepbx = $this->freepbx;
		$app->get('/version', function ($request, $response, $args) {
			$data = array('status' => true, 'version' => getVersion());
			$response->getBody()->write(json_encode($data));
			return $response->withHeader('Content-Type', 'application/json');
		})->add($this->checkReadScopeMiddleware('system'));

		$app->get('/engine', function ($request, $response, $args) {
			$data = array('status' => true, 'engine' => engine_getinfo()['version']);
			$response->getBody()->write(json_encode($data));
			return $response->withHeader('Content-Type', 'application/json');
		})->add($this->checkReadScopeMiddleware('system'));

		$app->get('/needreload', function ($request, $response, $args) {
			$data = array('status' => true, 'needreload' => check_reload_needed(true));
			$response->getBody()->write(json_encode($data));
			return $response->withHeader('Content-Type', 'application/json');
		})->add($this->checkReadScopeMiddleware('system'));

		//rest api to perform doreload/apply config 
		$app->put('/reload', function($request, $response, $args) use($freepbx) {
			$txnId = $freepbx->api->addTransaction("Processing","Framework","rest-do-reload");
			$freepbx->api->setGqlApiHelper()->doreload($txnId);
			$data = ['status' => true,
				'message' => 'Doreload/apply config has been initiated. Please check the status using /apistatus/'.$txnId.' api with the following transaction id '.$txnId];
			$response->getBody()->write(json_encode($data));
			return $response->withHeader('Content-Type', 'application/json');
		})->add($this->checkReadScopeMiddleware('system'));

		//fetch the api status for asyc task
		$app->get('/apistatus/{txnId}', function($request, $response, $args) use($freepbx) {
			//fetch the api status
			$message = $freepbx->api->getTransactionStatus($args['txnId'] ?? '');
			//return the api status
			$data = ['status' => true, 'message' => ($message['event_status'] ?? '')];
			$response->getBody()->write(json_encode($data));
			return $response->withHeader('Content-Type', 'application/json');
		})->add($this->checkReadScopeMiddleware('system'));
	}
}
