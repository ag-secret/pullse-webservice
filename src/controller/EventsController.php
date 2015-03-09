<?php

namespace App\Controller;

use App\Model\Event;

use App\Model\VipListSubscription;

/**
 * Controller for test, dont't care about.
 */
class EventsController extends AppController
{

	public $Event;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Event = new Event;
	}

	public function index($value='')
	{
		$club_id = 1;

		$query = $this->Event->find();
		$query
			->cols([
				'Event.id',
				'Event.name',
				'Event.descricao',
				'Event.data_inicio',
				'Event.data_fim',
				'Event.imagem_capa'
			])
			->where('Event.club_id = :club_id')
			->where('Event.data_fim > CURRENT_TIMESTAMP')
			->bindValues(['club_id' => $club_id]);

		$events = $this->Event->all($query);

		return $this->Response->success($events);
	}

	public function getLists()
	{
		$page = $this->request->get['page'];
		$limit = 20;
		$offset = $page * $limit;
		$club_id = 1;

		$query = $this->Event->find();
		$query
			->cols([
				'Event.id',
				'Event.name',
				'Event.descricao_lista_vip'
			])
			->where('Event.club_id = :club_id')
			->where('(Event.lista_vip_qtd_fem > 0 OR Event.lista_vip_qtd_masc > 0)')
			->offset($offset)
			->limit($limit)
			->bindValues(['club_id' => $club_id]);

		$eventos = $this->Event->all($query);

		return $this->Response->success($eventos);
	}

	public function addVipListSubscription()
	{
		$VipListSubscription = new VipListSubscription;

		$user_id = 1;
		$user_gender = 'f';

		$data = $this->request->headerBodyJson;
		$data['user_id'] = $user_id;
		$data['sexo'] = $user_gender;

		if (!$VipListSubscription->genderIsAvailable($user_gender, $data['event_id'])) {
			return $this->Response->error(403, 'Você não pode se cadastrar nesta lista devido a restrição de gênero.');
		}

		if ($VipListSubscription->isSubscribed($data['user_id'], $data['event_id'])){
			return $this->Response->error(403, 'Você já está cadastrado nesta lista');
		}

		if (!$VipListSubscription->hasSubscriptionsLeft($data['sexo'], $data['event_id'])) {
			return $this->Response->error(403, 'As vagas para esta lista já se esgotaram.');
		}

		if ($VipListSubscription->save($data)) {
			return $this->Response->success('ok');
		} else {
			return $this->Response->error(400, $VipListSubscription->validationErrors);
		}
	}

	public function getCurrent()
	{
		$club_id = 1;

		$query = $this->Event->find();

		$query
			->cols(['*'])
			->where('Event.data_inicio <= CURRENT_TIMESTAMP')
			->where('Event.data_fim >= CURRENT_TIMESTAMP')
			->where('club_id = :club_id')
			->bindValues([
				'club_id' => $club_id
			]);

		$event = $this->Event->one($query);

		return $this->Response->success($event);
	}
}

?>