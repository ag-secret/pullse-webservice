<?php

namespace App\Controller;

use App\Model\Checkin;
use App\Model\User;
use App\Model\Heart;

use PHP_GCM\Sender;
use PHP_GCM\Message;

use App\Model\Club;

Use Mayhem\Database\Datasource;

use App\Controller\Component\GCMPushMessage;

use Sly\NotificationPusher\PushManager,
    Sly\NotificationPusher\Adapter\Apns as ApnsAdapter,
    Sly\NotificationPusher\Adapter\Gcm  as GcmAdapter,
    Sly\NotificationPusher\Collection\DeviceCollection,
    Sly\NotificationPusher\Model\Device,
    Sly\NotificationPusher\Model\Message as MessageNew,
    Sly\NotificationPusher\Model\Push;

use Datetime;

class CheckinsController extends AppController
{

	public $Checkin;

	public function sendAPNS()
	{
		$iosDeviceToken = '4debb8c4a7bb7aaf4e2e4b5f03be8f6f7cdaefa4524bf15a916a694bb4eaf6a0';
		$androidDeviceToken = 'APA91bHjwYZ9cAOG9JQs1pefuMWZMIVPpLA8vqmHvm9FwEQ_3h7lW1d806olTe5tizRydyDnvPedy2X0kn-QxdN0eLNMk9SjPVmxFNS97kKu0FigLw0GRQSSB_SZHOSzP5XEYa67TlVDGhPQxMXHwAUDv0U5KVK64A';
		$pushManager    = new PushManager();

		$platform = 'ios';

		if ($platform == 'android') {
			$adapter = new GcmAdapter([
				'apiKey' => 'AIzaSyABl7lMCnSgjF9EltyXm5MWi3SaAJW0D1o'
			]);
			$deviceToken = $androidDeviceToken;
		} else {
			$adapter = new ApnsAdapter([
				'certificate' => WEBROOT . '/ios_certificate/ck.pem',
				'passPhrase' => '123mudar'
			]);
			$deviceToken = $iosDeviceToken;
		}

		$devices = new DeviceCollection([
			new Device ($deviceToken)
		]);

		// Then, create the push skel.
		$message = new MessageNew('This is an example.',
			[
				'title' => 'Agora com título haha'
			]);

		// Finally, create and add the push to the manager, and push it!
		$push = new Push($adapter, $devices, $message);
		$pushManager->add($push);
		$pushManager->push();
	}

	public function saveIosToken()
	{
		$token = $this->Request->query('token');
		$conn = Datasource::getConnection('default');
		$stmt = $conn->prepare("INSERT INTO teste_ios_push (token_key) VALUES ('".$token."')");
		$stmt->execute();

	}

	public function sendIt()
	{
		$deviceRegistrationId = 'APA91bFkL5J8_e8QH_qN22wimL4WbHatIXhmsm04JmSXBI8aI2I7yFAbWtWAMDkTJZ6SM0oNvXSJrzHDZWmb_OzSxNOjfMxa0JPerYWs2ZLMPUlr5AF61I5b-9YRNnwCV7tP3GAkB0EB_ol7bsMZkKBBaC3FnwV47As6d4WLZSkPAEi6KjBHdb0';
		$message = ['title' => 'Primeira', 'message' => 'Primeira', 'id' => 2];
		GCMPushMessage::sendNotification($deviceRegistrationId, $message);

		$message = ['title' => 'Mudou Mesmoo', 'message' => 'Segunda', 'id' => 2];
		GCMPushMessage::sendNotification($deviceRegistrationId, $message);
	}

	public function sendGCM()
	{
		$gcmApiKey = 'AIzaSyABl7lMCnSgjF9EltyXm5MWi3SaAJW0D1o';
		$deviceRegistrationId = 'APA91bHjwYZ9cAOG9JQs1pefuMWZMIVPpLA8vqmHvm9FwEQ_3h7lW1d806olTe5tizRydyDnvPedy2X0kn-QxdN0eLNMk9SjPVmxFNS97kKu0FigLw0GRQSSB_SZHOSzP5XEYa67TlVDGhPQxMXHwAUDv0U5KVK64A';
		$numberOfRetryAttempts = 5;

		$collapseKey = 'combination';
		$payloadData = ['title' => 'First Message Title', 'message' => 'First message', 'notId' => 1];

		$sender = new Sender($gcmApiKey);
		$message = new Message($collapseKey, $payloadData);
		
		$result = $sender->send($message, $deviceRegistrationId, $numberOfRetryAttempts);

		// Sending Second message
		$collapseKey = 'combination';
		$payloadData = ['title' => 'Essa é separada', 'message' => 'Second Message', 'notId' => 2];
		
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
			->cols([
				'count(*) AS total'
			])
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

		// Pega os dados de distancia da Boate para calcular mais a frente
		// Tb faz um JOIN com events para saber se o evento que o usuario passou
		// realmente pertence a boate que ele está logado
		$Club = new Club;
		$queryClub = $Club->find();
		$queryClub
			->cols([
				'Club.name',
				'Club.lat',
				'Club.lng',
				'Club.raio'
			])
			->where('Club.id = :club_id')
			->join(
				'INNER',
				'events Event',
				'Event.club_id = :club_id AND Event.id = :event_id'
			)
			->bindValues([
				'club_id' => $this->Auth->user->club_id,
				'event_id' => $event_id,
			]);

		$club = $Club->one($queryClub);

		if (!$club) {
			return $this->Response->error(400, 'Este evento não pertence a boate a qual você está cadastrado.');
		}

		// Pega a distancia entre o usuario e a boate
		$distance = $this->_vincentyGreatCircleDistance($lat, $lng, $club->lat, $club->lng);
		$diff = $distance - ($club->raio + 10); // + 10 é um extra
		if ($diff > 0) {
			return $this->Response->error(405, 'Você não está no raio da ' . $club->name . '.');
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

	public function getDistance()
	{
		return $this->Response->success(round($this->_vincentyGreatCircleDistance(
			-22.527132, -44.106209, -22.525079, -44.094884
		)) . ' Metros');
		
	}

	public function _vincentyGreatCircleDistance(
		$latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
	{
		// convert from degrees to radians
		$latFrom = deg2rad($latitudeFrom);
		$lonFrom = deg2rad($longitudeFrom);
		$latTo = deg2rad($latitudeTo);
		$lonTo = deg2rad($longitudeTo);

		$lonDelta = $lonTo - $lonFrom;
		$a = pow(cos($latTo) * sin($lonDelta), 2) +
		pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
		$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

		$angle = atan2(sqrt($a), $b);
		return $angle * $earthRadius;
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
			->orderBy(['Checkin.id'])
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
		$user_regid = $this->Auth->user->android_gcm_device_regid;
		// $user_id = 22;
		$profile_id = $this->Request->json('profile_id');
		// $profile_id = 20;
		$event_id = $this->Request->json('event_id');
		// $event_id = 1;
		$message = $this->Request->json('message');

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

		$User = new User;
		$query = $User->find();
		$query
			->cols(['User.android_gcm_device_regid'])
			->where('User.id = :profile_id')
			->bindValues([
				'profile_id' => $profile_id
			]);
		$result_target_id = $User->one($query);

		if (!$result_target_id) {
			throw new Exception('Usuário a ser curtido não existe');
		}

		$target_reg_id = $result_target_id->android_gcm_device_regid;

		$numberOfRetryAttempts = 5;
		if (!$result) {
			if ($Heart->save($data)) {
				$collapseKey = 'like';
				$payloadData = ['title' => 'Curtiram você!', 'message' => 'Você acaba de receber uma nova curtida', 'notId' => 1];

				$sender = new Sender($this->gcmApiKey);
				$message = new Message($collapseKey, $payloadData);
				
				/*
				/ Manda notificação para apessoa que foi curtida
				 */
				try {
					$sender->send($message, $target_reg_id, $numberOfRetryAttempts);	
				} catch (Exception $e) {
					
				}
				

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

						$title = 'Combinação!';
						$body = 'Você acaba de combinar com alguém.';
						$collapseKey = 'combination';
						$payloadData = ['title' => $title, 'message' => $body, 'notId' => 2];

						$sender = new Sender($this->gcmApiKey);
						$message = new Message($collapseKey, $payloadData);
						
						/*
						/ Manda notificação para apessoa que foi curtida e combinada
						 */
						try {
							$sender->send($message, $target_reg_id, $numberOfRetryAttempts);	
						} catch (Exception $e) {
							
						}
						
						return $this->Response->success(['combination' => true]);
					}
				}
			}
		}
	}
}

?>