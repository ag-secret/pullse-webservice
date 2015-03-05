<?php

namespace App\Model;

use App\Model\AppModel;

use Datetime;

class Club extends AppModel
{
	public $tableName = 'clubs';
	public $tableAlias = 'Club';

	// public function customRules() {
	// 	$rules = [
	// 		'igualTey' => [
	// 			'rule' => function($field, $value, array $param){
	// 				if ($value == 'tey') {
	// 					return true;
	// 				}
	// 				return false;
	// 			}, 
	// 			'message' => 'O nome da boate deve ser tey ahhaha'
	// 		]
	// 	];

	// 	return $rules;
	// }

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

	public function defaultRules(){
		$validations = [
			'name' => [
				'required'
			]
		];

		return $validations;
	}
}