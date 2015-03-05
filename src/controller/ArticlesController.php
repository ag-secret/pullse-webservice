<?php

namespace App\Controller;

use App\Model\Article;

/**
 * Controller for test, dont't care about.
 */
class ArticlesController extends AppController
{

	function __construct()
	{
		parent::__construct();
		$this->Article = new Article;
	}

	public function view($id = null, $a = null)
	{
		echo $id;
		return false;
	}

	public function add()
	{
		//$data = ['title' => 'title aqui', 'idade' => 5, 'password' => '123', 'confirm_password' => '10'];
		$data = ['title' => 'dasdsa', 'text' => 'dsdsds'];

		if ($this->Article->save($data, ['validate' => false])) {
			$this->response(201, 'ok', 'Dados salvos com sucesso.');
		} else {
			$this->response(400, 'error', $this->Article->validationErrors);
		}
	}
}

?>