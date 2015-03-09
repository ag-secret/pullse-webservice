<?php

namespace App\Controller;

use App\Model\Checkin;
use App\Model\User;

class CheckinsController extends AppController
{

	public $Checkin;

	public function beforeFilter()
	{
		$this->Checkin = new Checkin;
	}

	public function add()
	{
		$event_id = $this->request->get['event_id'];
		$user_id = 1;

		if ($this->Checkin->save(['event_id' => $event_id, 'user_id' => $user_id])) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, $validationErros);
		}
	}

	public function getPerfis()
	{
		$limit = 2;
		$page = $this->request->get['page'];
		$offset = $page * $limit;
		$User = new User;

		$user_id = $this->request->get['id'];
		$event_id = $this->request->get['event_id'];

		$query = $User->find();
		$query
			->cols([
				'User.id',
				'User.name'
			])
			->where('User.id IN (SELECT Checkin.user_id FROM checkins Checkin WHERE Checkin.event_id = :event_id AND Checkin.user_id != :user_id)')
			->offset($offset)
			->limit($limit)
			->bindValues([
				'user_id' => $user_id,
				'event_id' => $event_id
			]);

		$perfis = $this->Checkin->all($query);

		return $this->Response->success($perfis);
	}
}

?>