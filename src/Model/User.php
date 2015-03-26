<?php

namespace App\Model;

use App\Model\AppModel;

use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;

use Exception;
use Datetime;

class User extends AppModel
{
	public $tableName = 'users';
	public $tableAlias = 'User';

	public function getFacebookMe($session)
	{
		$params = ['fields' => 'id,email,name,gender,birthday'];
		try {
			$me = (new FacebookRequest(
				$session,
				'GET',
				'/me',
				$params
			))->execute()->getGraphObject(GraphUser::className());
		} catch (FacebookRequestException $e) {
			throw new Exception($e->getMessage());
		} catch (\Exception $e) {
			throw new Exception($e->getMessage());
		}
		return $me;
	}

	public function getFacebookAccessToken($account_id)
	{
		$query = $this->find();
		$query
			->cols(['facebook_access_token'])
			->where('id = :account_id')
			->bindValues(['account_id' => $account_id]);
		$account = $this->one($query);
		return $account->facebook_access_token;
	}

	public function getByFacebookId($facebookId, $clubId)
	{
		$query = $this->find();
		$query
			->cols(['*'])
			->where('User.facebook_uid = :facebook_id')
			->where('User.club_id = :club_id')
			->bindValues([
				'facebook_id' => $facebookId,
				'club_id' => $clubId
			]);

		return $this->one($query);
	}

	public function getById($id, $clubId)
	{
		$query = $this->find();
		$query
			->cols(['*'])
			->where('User.id = :id')
			->where('User.club_id = :club_id')
			->bindValues([
				'id' => $id,
				'club_id' => $clubId
			]);

		return $this->one($query);
	}

	public function formatGender($gender)
	{
		if ($gender == 'male') {
			return 'm';
		} else {
			return 'f';
		}
	}

}