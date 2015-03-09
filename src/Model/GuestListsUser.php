<?php

namespace App\Model;

use Datetime;

class GuestListsUser extends AppModel
{
	public $tableName = 'guest_lists_users';
	public $tableAlias = 'GuestListsUser';

	public function beforeSave($data, $type)
	{
		$now = new DateTime;
		if ($type == 'create') {
			$data['created'] = $now->format('Y-m-d H:i:s');
		} else {
			$data['modified'] = $now->format('Y-m-d H:i:s');
		}
		
		return $data;
	}
	
}