<?php

namespace App\Controller;

use App\Model\GuestList;
use App\Model\GuestListsUser;
use App\Model\Event;

class GuestListsController extends AppController
{

	public $GuestList;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->GuestList = new GuestList;
	}

	public function index()
	{
		$Event = new Event;

		$user_id = 1;
		$club_id = 1;

		$GuestListsUser = new GuestListsUser;

		$subQuery = $GuestListsUser->find();
		$subQuery
			->cols(['GuestListsUser.guest_list_id'])
			->where('GuestListsUser.user_id = :user_id')
			->where('GuestListsUser.guest_list_id = GuestList.id');

		$query = $this->GuestList->find();
		$query
			->cols([
				'GuestList.id',
				'GuestList.name',
				'Event.name AS event_name',
			])
			->join(
				'INNER',
				'events AS Event',
				'Event.id = GuestList.event_id'
			)
			->where('Event.club_id = :club_id')
			->where('GuestList.data_fim > CURRENT_TIMESTAMP')
			->where('GuestList.id NOT IN ('.$subQuery.')')
			->bindValues([
				'user_id' => $user_id,
				'club_id' => $club_id
			]);



		$result = $this->GuestList->all($query);

		$events = [];
		$i = 0;
		if ($result) {
			foreach ($result as $key => $row) {
				$events[$i]['id'] = $row->id;
				$events[$i]['name'] = $row->name;
				$events[$i]['event']['name'] = $row->event_name;
				$i++;
			}
		}

		return $this->Response->success($events);
	}

}