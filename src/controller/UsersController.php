<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Club;

use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphUser;

use Datetime;

/**
 * Controller for test, dont't care about.
 */
class UsersController extends AppController
{

	const APP_ID = '401554549993450';
	const APP_SECRET = '682b42929b63e9ee6e9f8559465d49dd';

	public $User;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->User = new User;
	}

	public function getUserTeste()
	{
		$id = $this->Request->query('id');
		$clubId = $this->Request->query('club_id');
		$user = $this->User->getById($id, $clubId);

		return $this->Response->success($user);
	}

	public function getAccessByFacebook()
	{
		$accessToken = $this->request->headerBodyJson['access_token'];
		$regid = $this->request->headerBodyJson['push_reg_id'];
		$clubId = $this->request->headerBodyJson['club_id'];
		$platform = $this->request->headerBodyJson['platform'];

		$club = new Club;
		$query = $club->find();
		$query
			->cols(['count(*) as total'])
			->where('Club.id = :club_id')
			->where('Club.is_active = 1')
			->bindValues([
				'club_id' => $clubId
			]);
			
		$result = $club->one($query);

		if ($result->total == 0) {
			return $this->Response->error(400, 'Boate inativa ou não cadastrada');
		}
		
		FacebookSession::enableAppSecretProof(false);
		FacebookSession::setDefaultApplication(self::APP_ID, self::APP_SECRET);
		
		$session = new FacebookSession($accessToken);

		try {
			$me = $this->User->getFacebookMe($session);
		} catch (Exception $e) {
			return $this->Response->error(400, $e->getMessage());
		}
		
		$account = $this->User->getByFacebookId($me->getId(), $clubId);
		$appAccessToken = $this->_appAccessTokenGenerator();

		if ($account) {
			$account->app_access_token = $appAccessToken;
			
			$data = [
				'id' => $account->id,
				'app_access_token' => $appAccessToken,
				'android_gcm_device_regid' => $regid, // Mesmo se o usuario existir a gente deve fazer o upd do regid pq ele pode estar logando de outro device
				'platform' => $platform
			];
			
			if (!$this->User->update($data)) {
				return $this->Response->error(400, $this->Account->validationErrors());
			}
		} else {
			$mysqlDateTimeFormat = 'Y-m-d H:i:s';
			$now = new DateTime();
			$data = [
				'facebook_uid' => $me->getId(),
				'name' => $me->getName(),
				'android_gcm_device_regid' => $regid,
				'email' => $me->getEmail(),
				'facebook_access_token' => $accessToken,
				'platform' => $platform,
				'app_access_token' =>$appAccessToken,
				'created' => $now->format($mysqlDateTimeFormat),
				'club_id' => $clubId,
				'dt_nascimento' => $now->format($mysqlDateTimeFormat),
				'sexo' => $this->User->formatGender($me->getGender())
			];
			
			if ($this->User->save($data)) {
				$account = $data;
				$account['id'] = $this->User->lastInsertId;
			} else {
				return $this->Response->error(400, $this->Account->validationErrors());
			}
		}

		return $this->Response->success($account);			

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
	public function _appAccessTokenGenerator()
	{
		return rand(1000, 1999);
	}
}