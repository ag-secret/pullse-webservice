<?php

namespace App\Controller\Component;

use App\Model\User;

class Auth
{
	public $user;
	public $Model;

	function __construct($model, array $options = [])
	{
		$this->Model = $model;

		if ($options) {
			$this->options = $options;
		}

	}

	public function authorize()
	{
		$this->user =  $this->defaultStrategy();	
	}

	public function defaultStrategy()
	{
		$idFieldName = $this->options['fields']['id'];
		$tokenFieldName = $this->options['fields']['token'];

		$fields = $this->options['data'];
		$fields[] = $tokenFieldName;
		foreach ($fields as $key => $value) {
			$fields[$key] = $this->Model->tableAlias . '.' . $value;
		}

		$query = $this->Model->find();
		$query
			->cols($fields)
			->where($this->options['fields']['id'] . ' = :id')
			->bindValues([
				'id' => $this->Model->$idFieldName,
			]);

		$result = $this->Model->one($query);

		if (!$result) {
			return null;
		}
		if ($result->$tokenFieldName != $this->Model->$tokenFieldName) {
			return null;
		}
		
		foreach ($result as $key => $value) {
			$this->Model->$key = $value;
		}

		return $this->Model;
	}

	public function setCredentials($request, $slim)
	{
		$id = $request->query($this->options['fields']['id']);
		$token = $request->query($this->options['fields']['token']);
		
		if (!$id) {
			return $slim->halt(400, 'Você não informou o ID');
		}
		if (!$token) {
			return $slim->halt(400, 'Você não informou o access token');
		}

		$idFieldName = $this->options['fields']['id'];
		$tokenFieldName = $this->options['fields']['token'];
		$this->Model->$idFieldName = $id;
		$this->Model->$tokenFieldName = $token;
	}

}