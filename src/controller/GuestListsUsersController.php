<?php

namespace App\Controller;

use App\Model\GuestListsUser;
use App\Model\Event;

class GuestListsUsersController extends AppController
{

	public $GuestListsUser;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->GuestListsUser = new GuestListsUser;
	}

	public function add()
	{
		$data = $this->request->headerBodyJson;
		$data['user_id'] = 1;

		if ($this->GuestListsUser->save($data)) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->errors(400, $this->GuestListsUser->validationErrors);
		}
		
	}

}