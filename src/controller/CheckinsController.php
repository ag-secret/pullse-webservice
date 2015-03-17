<?php

namespace App\Controller;

use App\Model\Checkin;
use App\Model\User;
use App\Model\Heart;

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

		$query = $this->Checkin->find();
		$query
			->cols(['count(*) AS total'])
			->where('Checkin.user_id = :user_id')
			->where('Checkin.event_id = :event_id')
			->bindValues([
				'user_id' => $user_id,
				'event_id' => $event_id
			]);

		$result = $this->Checkin->one($query);
		if ($result->total > 0) {
			return $this->Response->error(400, 'Checkin já foi feito neste evento para este usuario');
		}

		if ($this->Checkin->save(['event_id' => $event_id, 'user_id' => $user_id])) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, $validationErros);
		}
	}

	public function getPerfis()
	{
		$limit = 3;
		// $page = $this->request->get['page'];
		// $offset = $page * $limit;
		$User = new User;

		$user_id = $this->request->get['id'];
		$last_id = $this->request->get['last_id'];
		$event_id = $this->request->get['event_id'];

		$query = $User->find();
		$query
			->cols([
				'User.id',
				'User.name',
				'Checkin.id AS checkinId',
				'Checkin.created'
			])
			->join(
				'INNER',
				'checkins Checkin',
				'Checkin.user_id = User.id AND Checkin.event_id = :event_id'
			)
			->where('User.id <> :user_id')
			->where('Checkin.id > :last_id')
			->limit($limit)
			->bindValues([
				'last_id' => $last_id,
				'user_id' => $user_id,
				'event_id' => $event_id
			]);

		$perfis = $this->Checkin->all($query);

		return $this->Response->success($perfis);
	}

	public function addLike()
	{
		$user_id1 = $this->request->get['id'];
		$user_id2 = $this->request->headerBodyJson['user_id'];

		$Like = new Like;

		if ($Like->save(['user_id1' => $user_id1, 'user_id2' => $user_id2])) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, 'Erro ao salvar o like');
		}
	}

	public function getPeopleHeart()
	{
		$limit = 4;

		$user_id = $this->request->get['id'];
		$event_id = $this->request->get['event_id'];
		$last_id = $this->request->get['last_id'];
		$me = $this->request->get['me'];

		$User = new User;

		$result = $User->getHearts([
			'user_id' => $user_id,
			'event_id' => $event_id,
			'last_id' => $last_id,
		], $limit, $me);

		return $this->Response->success($result);
	}

	public function getCombinations()
	{
		$limit = 4;

		$user_id = $this->request->get['id'];
		$event_id = $this->request->get['event_id'];
		$last_id = $this->request->get['last_id'];

		$User = new User;

		$query = $User->find();
		$query
			->cols([
				'User.id',
				'User.name',
				'Heart.id AS heart_id'
			])
			->join(
				'INNER',
				'hearts Heart',
				'User.id = Heart.user_id2  AND Heart.user_id1 = :user_id AND Heart.combination = 1 AND Heart.event_id = :event_id AND Heart.id > :last_id'
			)
			->limit($limit)
			->bindValues([
				'user_id' => $user_id,
				'event_id' => $event_id,
				'last_id' => $last_id
			]);

		$result = $User->all($query);

		return $this->Response->success($result);
	}

	public function getProfileStatus()
	{
		$event_id = $this->request->get['event_id'];
		$user_id = $this->request->get['id'];
		$profile_id = $this->request->get['profile_id'];

		$Heart = new Heart;
		$result = $Heart->getRelationshipStatus(
			['Heart.user_id1'],
			[
				'user_id' => $user_id,
				'profile_id' => $profile_id,
				'event_id' => $event_id
			]
		);

		return $this->Response->success($result);
	}

	public function addHeartPreflight()
	{
		$user_id = $this->request->get['id'];
		$profile_id = $this->request->get['profile_id'];
		$event_id = $this->request->get['event_id'];

		$Heart = new Heart;
		$query = $Heart->find();
		$query
			->cols(['count(*) AS total'])
			->where('(Heart.user_id1 = :profile_id AND Heart.user_id2 = :user_id)')
			->where('Heart.event_id = :event_id')
			->bindValues(
				[
					'user_id' => $user_id,
					'profile_id' => $profile_id,
					'event_id' => $event_id
				]
			);

		$result = $Heart->one($query);

		return $this->Response->success($result->total);
	}

	public function addHeart()
	{
		$user_id = $this->request->get['id'];
		$profile_id = $this->request->headerBodyJson['profile_id'];
		$event_id = $this->request->headerBodyJson['event_id'];
		$message = $this->request->headerBodyJson['message'];

		$Heart = new Heart;
		$result = $Heart->getRelationshipStatus(
			['Heart.id', 'Heart.user_id1'],
			[
				'user_id' => $user_id,
				'profile_id' => $profile_id,
				'event_id' => $event_id
			]
		);

		$data = [
			'user_id1' => $user_id,
			'user_id2' => $profile_id,
			'event_id' => $event_id,
		];

		if ($message) {
			$data['message'] = $message;
		}

		if (!$result) {
			if ($Heart->save($data)) {
				return $this->Response->success(['combination' => false]);
			}
		} else {
			if (count($result) == 2 || $result[0]->user_id1 == $user_id) {
				return $this->Response->error(400, 'Você já curtiu este perfil');
			}

			if ($result[0]->user_id1 == $profile_id) {
				$data['combination'] = 1;
				if ($Heart->save($data)) {
					if ($Heart->update(['id' => $result[0]->id, 'combination' => 1])) {
						return $this->Response->success(['combination' => true]);
					}
				}
			}
		}
	}
}

?>