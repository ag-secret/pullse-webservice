<?php

namespace App\Model;

use Datetime;

class Checkin extends AppModel
{
	public $tableName = 'checkins';
	public $tableAlias = 'Checkin';

	public function beforeSave($data, $type)
	{
		$now = new DateTime;
		if ($type == 'create') {
			$data['created'] = $now->format('Y-m-d H:i:s');
		}
		
		return $data;
	}

}