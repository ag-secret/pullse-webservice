<?php

namespace App\Model;

use App\Model\AppModel;
use Datetime;

class Message extends AppModel
{
	public $tableName = 'messages';
	public $tableAlias = 'Message';

	public function beforeSave($data, $type)
	{
		$now = new Datetime;
		if ($type == 'create') {
			$data['created'] = $now->format('Y-m-d H:i:s');
		}
		
		return $data;
	}

	public function defaultRules()
	{
		$rules = [
			'mensagem' => [
				'required'
			],
			'user_id' => [
				'required',
				'numeric'
			]
		];

		return $rules;
	}

}