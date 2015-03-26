<?php

namespace App\Controller;

use App\Model\Checkin;
use App\Model\User;
use App\Model\Heart;

use PHP_GCM\Sender;
use PHP_GCM\Message;

use App\Controller\Component\GCMPushMessage;

use Datetime;

class CheckinsController extends AppController
{

	public $Checkin;

	public function sendIt()
	{
		$deviceRegistrationId = 'APA91bFkL5J8_e8QH_qN22wimL4WbHatIXhmsm04JmSXBI8aI2I7yFAbWtWAMDkTJZ6SM0oNvXSJrzHDZWmb_OzSxNOjfMxa0JPerYWs2ZLMPUlr5AF61I5b-9YRNnwCV7tP3GAkB0EB_ol7bsMZkKBBaC3FnwV47As6d4WLZSkPAEi6KjBHdb0';
		$message = ['title' => 'Primeira', 'message' => 'Primeira'];
		GCMPushMessage::sendNotification($deviceRegistrationId, $message);

		$message = ['title' => 'Mudou Segunda', 'message' => 'Segunda'];
		GCMPushMessage::sendNotification($deviceRegistrationId, $message);
	}

	public function sendGCM()
	{
		$gcmApiKey = 'AIzaSyABl7lMCnSgjF9EltyXm5MWi3SaAJW0D1o';
		$deviceRegistrationId = 'APA91bFkL5J8_e8QH_qN22wimL4WbHatIXhmsm04JmSXBI8aI2I7yFAbWtWAMDkTJZ6SM0oNvXSJrzHDZWmb_OzSxNOjfMxa0JPerYWs2ZLMPUlr5AF61I5b-9YRNnwCV7tP3GAkB0EB_ol7bsMZkKBBaC3FnwV47As6d4WLZSkPAEi6KjBHdb0';
		$numberOfRetryAttempts = 5;

		$collapseKey = 1;
		$payloadData = ['title' => 'First Message Title', 'message' => 'First message'];

		$sender = new Sender($gcmApiKey);
		$message = new Message($collapseKey, $payloadData);
		
		$result = $sender->send($message, $deviceRegistrationId, $numberOfRetryAttempts);

		// Sending Second message
		$collapseKey = 1;
		$payloadData = ['title' => 'Mudei Second Message Title', 'message' => 'Second Message'];
		
		$sender = new Sender($gcmApiKey);
		$message = new Message($collapseKey, $payloadData);

		$result = $sender->send($message, $deviceRegistrationId, $numberOfRetryAttempts);
	}

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Checkin = new Checkin;
	}

	public function add()
	{
		$user_id = $this->Auth->user->id;

		$event_id = $this->Request->json('event_id');
		$lat = $this->Request->json('lat');
		$lng = $this->Request->json('lng');

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
			return $this->Response->error(400, 'Checkin já foi feito neste evento para este usuário');
		}

		$dataToSave = [
			'event_id' => $event_id,
			'user_id' => $user_id,
			'lat' => $lat,
			'lng' => $lng
		];
		if ($this->Checkin->save($dataToSave)) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, $validationErros);
		}
	}

	/**
	 * Usado para pegar os perfis que fizeram checkin em um determinado evento
	 * @return Array - Perfis 
	 */
	public function getPerfis()
	{
		$limit = 15;
		$User = new User;

		$user_id = $this->Auth->user->id;
		$club_id = $this->Auth->user->club_id;

		$last_id = $this->Request->query('last_id');
		$event_id = $this->Request->query('event_id');

		$query = $User->find();
		$query
			->cols([
				'User.id',
				'User.name',
				'User.facebook_uid',
				'Checkin.id AS checkinId',
				'Checkin.created'
			])
			->join(
				'INNER',
				'checkins Checkin',
				'Checkin.user_id = User.id AND Checkin.event_id = :event_id'
			)
			->where('User.id <> :user_id')
			->where('User.club_id = :club_id')
			->where('Checkin.id > :last_id')
			->limit($limit)
			->bindValues([
				'last_id' => $last_id,
				'user_id' => $user_id,
				'event_id' => $event_id,
				'club_id' => $club_id
			]);

		$perfis = $this->Checkin->all($query);

		return $this->Response->success($perfis);
	}

	public function getHearts()
	{
		$limit = 15;

		$user_id = $this->Auth->user->id;

		$event_id = $this->Request->query('event_id');
		$last_id = $this->Request->query('last_id');
		/**
		 * Flag diz se vai pegar quem me curtiu ou quem eu curtiu
		 * @var Number (0 ou 1)
		 */
		$flag = $this->Request->query('flag');

		$Heart = new Heart;

		$result = $Heart->getHearts([
			'user_id' => $user_id,
			'event_id' => $event_id,
			'last_id' => $last_id,
		], $limit, $flag);

		return $this->Response->success($result);
	}

	public function getCombinations()
	{
		$limit = 15;

		$user_id = $this->Auth->user->id;

		$event_id = $this->Request->query('event_id');
		$last_id = $this->Request->query('last_id');

		$User = new User;

		$query = $User->find();
		$query
			->cols([
				'User.id',
				'User.name',
				'User.facebook_uid',
				'Heart.id AS heart_id',
				'Heart.message',
				'Heart.message_sender',
				'Heart.combination_created'
			])
			->join(
				'INNER',
				'hearts Heart',
				'User.id = Heart.user_id2 AND Heart.user_id1 = :user_id AND Heart.combination = 1 AND Heart.event_id = :event_id AND Heart.id > :last_id'
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

	/**
	 * Verifica o relacionamento do usuario em relação ao perfil passado
	 */
	public function getProfileStatus()
	{
		$user_id = $this->Auth->user->id;

		$event_id = $this->Request->query('event_id');
		$profile_id = $this->Request->query('profile_id');

		$Heart = new Heart;
		$result = $Heart->getRelationshipStatus(
			[
				'Heart.user_id1',
				'Heart.message',
				'Heart.message_sender',
				'Heart.combination_created AS message_created'
			],
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
		$user_id = $this->Auth->user->id;

		$profile_id = $this->Request->query('profile_id');
		$event_id = $this->Request->query('event_id');

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
		$user_id = $this->Auth->user->id;

		$profile_id = $this->Request->json['profile_id'];
		$event_id = $this->Request->json['event_id'];
		$message = $this->Request->json['message'];

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
			$data['message_sender'] = $user_id;
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
				$now = new Datetime;
				$data['combination'] = 1;
				$data['combination_created'] = $now->format('Y-m-d H:i:s');
				if ($Heart->save($data)) {
					$upd = [
						'id' => $result[0]->id,
						'combination' => 1,
						'combination_created' => $data['combination_created']
					];
					if ($message) {
						$upd['message'] = $message;
						$upd['message_sender'] = $user_id;
					}
					if ($Heart->update($upd)) {
						return $this->Response->success(['combination' => true]);
					}
				}
			}
		}
	}
}

?>