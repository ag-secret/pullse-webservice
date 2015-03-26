<?php

namespace App\Model;

use App\Model\User;

class Heart extends AppModel
{
	public $tableName = 'hearts';
	public $tableAlias = 'Heart';

	public function beforeSave($data, $type)
	{
		return $this->setCurrentTimestamp($data, $type);
	}

	public function getHearts(array $options, $limit, $flag)
	{
		if ($flag == 1) {
			$condition = 'Heart.user_id1 = User.id 
				AND Heart.user_id2 = :user_id 
				AND Heart.event_id = :event_id 
				AND Heart.id > :last_id';
		} elseif($flag == 2) {
			$condition = 'Heart.user_id2 = User.id 	
				AND Heart.user_id1 = :user_id 
				AND Heart.event_id = :event_id 
				AND Heart.id > :last_id';
		}

		$User = new User;

		$query = $User->find();
		$query
			->cols([
				'User.id',
				'User.name',
				'User.facebook_uid',
				'Heart.created',
				'Heart.id AS heart_id'
			])
			->join(
				'INNER',
				'hearts Heart',
				$condition
			)
			->limit($limit)
			->bindValues($options);

		return $User->all($query);
	}

	public function getRelationshipStatus(array $fields, array $valuesToBind)
	{
		$query = $this->find();
		$query
			->cols($fields)
			->where('(Heart.user_id1 = :user_id AND Heart.user_id2 = :profile_id)')
			->orWhere('(Heart.user_id2 = :user_id AND Heart.user_id1 = :profile_id)')
			->where('Heart.event_id = :event_id')
			->bindValues($valuesToBind);

		return $this->all($query);
	}
}