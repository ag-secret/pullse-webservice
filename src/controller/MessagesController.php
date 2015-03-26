<?php

namespace App\Controller;

use App\Model\Message;

class MessagesController extends AppController
{

	public $Message;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Message = new Message;
	}

	/**
	 * Salva a mensagem vinda da view "Contato" do aplicativo
	 */
	public function add()
	{
		$data = [
			'user_id' => $this->Auth->user->id,
			'mensagem' => $this->Request->json('mensagem'),
		];

		if ($this->Message->save($data)) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, $this->Message->validationErrors);
		}
	}

}