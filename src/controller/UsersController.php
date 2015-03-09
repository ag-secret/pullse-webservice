<?php

namespace App\Controller;

use App\Model\User;

/**
 * Controller for test, dont't care about.
 */
class UsersController extends AppController
{

	public $User;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->User = new User;
	}

	public function doLogin()
	{
		$id = $this->request->get['id'];

		$query = $this->User->find();
		$query
			->cols(['*'])
			->where('id = :id')
			->bindValues(['id' => $id]);

		$user = $this->User->one($query);

		return $this->Response->success($user);
	}
	
}