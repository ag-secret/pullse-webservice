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

	public function add()
	{
		$data = $this->request->headerBodyJson;
		$data['user_id'] = 1;

		if ($this->Message->save($data)) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, $this->Message->validationErrors);
		}
	}

}