<?php

namespace App\Model;

class Test extends AppModel
{
	public $tableName = 'test_fake_table';

	public $data = [
		'name' => 'Daniel de Faria Pedro',
		'idade' => 26,
		'birthdate' => '1988/02/09',
		'password' => '123mudar',
		'password_confirmation' => '123mudar',
		'sex' => 'm'
	];

	public $dataEmpty = [''];

	public function beforeSave(array $data, $type)
	{
		unset($data['password_confirmation']);
		return $data;
	}

	public $validations = [
		'name' => [
			'required',
			['lengthBetween', 3, 120],
		],
		'idade' => [
			'required',
			'numeric',
		],
		'birthdate' => [
			'required',
			['dateBefore', '01/09/2015']
		],
		'password' => [
			['required', 'on' => 'create'],
			['lengthBetween', 6, 20],
			['equals', 'password_confirmation']
		],
		'sex' => [
			['in', ['m', 'f']]
		]
	];
}