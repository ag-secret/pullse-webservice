<?php

namespace App\Controller;

use App\Model\Club;

class ClubsController extends AppController
{

	public $Club;

	public function index()
	{
		$this->Club = new Club;
		$data = ['name' => 'Pullse', 'descricao' => 'Uma descrição aqui'];

		if ($this->Club->save($data)) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, $this->Club->validationErrors);
		}
		
	}

}