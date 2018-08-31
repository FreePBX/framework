<?php

namespace FreePBX\Api\Rest;
use FreePBX\modules\Api\Rest\Base;
class System extends Base {
	public static function getScopes() {
		return [
			'read:system' => [
				'description' => _('Read system information'),
			]
		];
	}

	public function setupRoutes($app) {
		$app->get('/version', function ($request, $response, $args) {
			$data = array('status' => true, 'version' => getVersion());
			return $response->withJson($data);
		})->add($this->checkReadScopeMiddleware('system'));

		$app->get('/engine', function ($request, $response, $args) {
			$data = array('status' => true, 'engine' => engine_getinfo()['version']);
			return $response->withJson($data);
		})->add($this->checkReadScopeMiddleware('system'));

		$app->get('/needreload', function ($request, $response, $args) {
			$data = array('status' => true, 'needreload' => check_reload_needed());
			return $response->withJson($data);
		})->add($this->checkReadScopeMiddleware('system'));
	}
}
