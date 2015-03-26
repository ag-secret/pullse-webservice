<?php

namespace App\Controller;

use Mayhem\Controller\Controller;

use App\Model\User;

use App\Controller\Component\Auth;

/**
 * App controller, lógica implementada aqui será visivel para todos os controllers
 */
class AppController extends Controller
{

	public $Auth;

	public function beforeFilter()
	{
		$this->handleAuth();
	}

	public function handleAuth()
	{
		$allowed = [
			['users', 'getAccessByFacebook'],
			['users', 'getUserTeste'],
			['checkins', 'sendGCM'],
			['checkins', 'sendIt']
		];
		foreach ($allowed as $value) {
			if ($this->Request->controller() == $value[0] AND $this->Request->action() == $value[1]) {
				return true;
			}
		}

		$options = [
			'fields' => [
				'id' => 'id',
				'token' => 'app_access_token'
			],
			'data' => ['id', 'name', 'club_id', 'sexo']
		];

		$this->Auth = new Auth(new User, $options);
		$this->Auth->setCredentials($this->Request, $this->slim);
		$this->Auth->authorize();
		
		if (!$this->Auth->user) {
			return $this->slim->halt(401, 'Acesso não permitido');
		}
	}

}