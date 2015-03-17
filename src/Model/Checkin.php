<?php

namespace App\Model;

use Datetime;

class Checkin extends AppModel
{
	public $tableName = 'checkins';
	public $tableAlias = 'Checkin';

	public function beforeSave($data, $type)
	{
		return $this->setCurrentTimestamp($data, $type);
	}

}