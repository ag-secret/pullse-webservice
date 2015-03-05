<?php

namespace App\Controller;

use App\Model\Test;

use Valitron\Validator;
use Mayhem\Validation\ValitronAdapter;

use Exception;

class TestsController extends AppController
{

	public function beforeFilter()
	{
		$this->Test = new Test();
	}

	public function teste()
	{
		$this->Response->success('oi');
	}

	public function saveOk()
	{
		$data = $this->Test->data;

		if ($this->Test->save($data)) {
			$this->response(200, 'ok', 'Dados salvos com sucesso');
		} else {
			$this->response(200, 'Data validation errors', $this->Test->validationErrors);
		}
		
	}

	public function saveValidationError()
	{
		$data = $this->Test->dataEmpty;

		if ($this->Test->save($data)) {
			$this->response(200, 'ok', 'Dados salvos com sucesso');
		} else {
			$this->response(200, 'Data validation errors', $this->Test->validationErrors);
		}
		
	}

	public function saveOkCustomInsert()
	{
		$data = $this->Test->data;

		$validator = new Validator($data);
		$validator = ValitronAdapter::AdaptRules($this->Test->validations, $validator, 'create');

		if ($validator->validate()) {
			unset($data['password_confirmation']);

			$insert = $this->Test->customInsert();
			$insert->cols($data);
			$this->Test->executeQuery($insert);

			$this->response(200, 'ok', 'Dados salvos com sucesso!');
		} else {
			$this->response(200, 'Data validation errors', $validator->errors());
		}
	}

	public function saveValidationErrorCustomInsert()
	{
		$data = $this->Test->dataEmpty;

		$validator = new Validator($data);
		$validator = ValitronAdapter::AdaptRules($this->Test->validations, $validator, 'create');

		if ($validator->validate()) {
			unset($data['password_confirmation']);

			$insert = $this->Test->customInsert();
			$insert->cols($data);
			$this->Test->executeQuery($insert);

			$this->response(200, 'ok', 'Dados salvos com sucesso!');
		} else {
			$this->response(200, 'Data validation errors', $validator->errors());
		}
	}

	public function updateOk($id = null)
	{
		$data = $this->Test->data;
		$data['id'] = $id;
		$data['name'] = 'Jonas Brother';

		if ($this->Test->update($data)) {
			$this->response(200, 'ok', "#{$this->Test->rowsAffected} linha(s) fo(i)ram afetada(s), dados do registro #{$id} editado com sucesso");
		} else {
			$this->response(200, 'Data validation errors', $this->Test->validationErrors);
		}
		
	}

	public function updateValidationError($id = null)
	{
		$data = $this->Test->dataEmpty;

		if ($this->Test->update($data)) {
			$this->response(200, 'ok', "Dados do registro #{$id} editado com sucesso");
		} else {
			$this->response(200, 'Data validation errors', $this->Test->validationErrors);
		}
		
	}

	public function updateOkCustomUpdate($id = null)
	{
		$data = $this->Test->data;

		$validator = new Validator($data);
		$validator = ValitronAdapter::AdaptRules($this->Test->validations, $validator, 'update');

		if ($validator->validate()) {
			$update = $this->Test->customUpdate();
			$update->cols($data)->where('id = :id')->bindValues(['id' => $id]);
			$this->Test->executeQuery($update);

			$this->response(200, 'ok', "Dados do registro #{$id} editado com sucesso");
		} else {
			$this->response(200, 'Data validation errors', $validator->errors());
		}
	}

	public function updateValidationErrorCustomUpdate($id = null)
	{
		$data = $this->Test->dataEmpty;

		$validator = new Validator($data);
		$validator = ValitronAdapter::AdaptRules($this->Test->validations, $validator, 'update');

		if ($validator->validate()) {
			unset($data['password_confirmation']);

			$update = $this->Test->customUpdate();
			$update->cols($data);
			$this->Test->executeQuery($update);

			$this->response(200, 'ok', "Dados do registro #{$id} editado com sucesso");
		} else {
			$this->response(400, 'Data validation errors', $validator->errors());
		}
	}

	public function delete($id = null)
	{
		if (!$id) {
			throw new Exception("Você deve informar o ID a ser deletado", 1);			
		}

		if ($this->Test->delete($id)) {
			$this->response(400, "ok", "#{$this->Test->rowsAffected} linhas afetadas pelo delte");
		} else {
			$this->response(400, "error", 'Erro ao deletar');
		}
	}

	public function customDelete($id = null)
	{
		if (!$id) {
			throw new Exception("Você deve informar o ID a ser deletado", 1);			
		}

		$delete = $this->Test->customDelete();

		$delete->where('id IN (30, 31, 32)');
		$this->Test->executeQuery($delete);
		$this->response(400, "ok", "#{$this->Test->rowsAffected} linhas afetadas pelo delte");
	}

	public function select()
	{
		$select = $this->Test->find();
		$select
			->cols(['*']);

		$values = $this->Test->all($select);
		print_r($values);
	}

}