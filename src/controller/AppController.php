<?php

namespace App\Controller;

use App\Model\User;

use App\Controller\Component\Auth;

use Mayhem\Controller\Controller;

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
			['controller' => 'users', 'action' => 'getAccessByFacebook'],
			['controller' => 'users', 'action' => 'getUserTeste']
		];
		foreach ($allowed as $value) {
			if ($this->request->controller == $value['controller'] AND $this->request->action == $value['action']) {
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
		$this->Auth->setCredentials($this->request, $this->slim);
		$this->Auth->authorize();
		
		if (!$this->Auth->user) {
			return $this->slim->halt(401, 'Acesso não permitido');
		}
	}

}