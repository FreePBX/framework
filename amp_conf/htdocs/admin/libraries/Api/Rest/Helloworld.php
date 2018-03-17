<?php

namespace FreePBX\Api\Rest;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Rest\Base;

class Helloworld extends Base {
	public function setupRoutes($app) {
		$app->get('/test', function ($request, $response, $args) {
			$data = array('name' => 'Bob', 'age' => 40);
			return $response->withJson($data);
		});
	}
}
