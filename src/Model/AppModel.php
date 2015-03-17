<?php

namespace App\Model;

use Mayhem\Model\Model;

use Datetime;

class AppModel extends Model
{

	public function setCurrentTimestamp($data, $type)
	{
		$now = new DateTime;
		if ($type == 'create') {
			$data['created'] = $now->format('Y-m-d H:i:s');
		}
		
		return $data;
	}

}