<?php

namespace App\Model;

class Heart extends AppModel
{
	public $tableName = 'hearts';
	public $tableAlias = 'Heart';

	public function beforeSave($data, $type)
	{
		return $this->setCurrentTimestamp($data, $type);
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