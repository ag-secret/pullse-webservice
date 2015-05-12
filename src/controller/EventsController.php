<?php

namespace App\Controller;

use App\Model\Event;
use App\Model\VipListSubscription;

class EventsController extends AppController
{

	public $Event;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->Event = new Event;
	}

	/**
	 * Carrega todos os eventos ativos e que ainda não expiraram
	 */
	public function index()
	{
		$club_id = $this->Auth->user->club_id;

		$query = $this->Event->find();
		$query
			->cols([
				'Event.id',
				'Event.name',
				'Event.descricao',
				'Event.data_inicio',
				'Event.data_fim',
				'Event.imagem_capa',
				'Event.facebook_img'
			])
			->where('Event.is_active = 1')
			->where('Event.club_id = :club_id')
			->where('Event.data_fim > CURRENT_TIMESTAMP')
			->bindValues(['club_id' => $club_id]);

		$events = $this->Event->all($query);

		return $this->Response->success($events);
	}

	public function getLists()
	{
		$limit = 20;
		$club_id = $this->Auth->user->club_id;

		$query = $this->Event->find();
		$query
			->cols([
				'Event.id',
				'Event.name',
				'Event.descricao_lista_vip',
				'Event.imagem_capa',
				'Event.facebook_img',
				'Event.data_inicio',
				'Event.lista_vip_dt_fim'
			])
			->where('Event.club_id = :club_id')
			->where('Event.is_active = 1')
			->where('Event.lista_vip_dt_inicio <= CURRENT_TIMESTAMP')
			->where('Event.lista_vip_dt_fim >= CURRENT_TIMESTAMP')
			->where('(Event.lista_vip_qtd_fem > 0 OR Event.lista_vip_qtd_masc > 0)')
			->limit($limit)
			->bindValues(['club_id' => $club_id]);

		$eventos = $this->Event->all($query);

		return $this->Response->success($eventos);
	}

	/**
	 * Inscreve o usuario na no id do evento enviado 
	 */
	public function addVipListSubscription()
	{
		$VipListSubscription = new VipListSubscription;

		$user_id = $this->Auth->user->id;
		$user_gender = $this->Auth->user->sexo;

		$data = [];
		$data['event_id'] = $this->Request->json('event_id');
		$data['user_id'] = $user_id;
		/**
		 * É necessario salvar o sexo do usuario na tabela de inscrições para
		 * agilizar futuras consultas e nao precisar fazer JOIN na tabela users
		 */
		$data['sexo'] = $user_gender;

		/**
		 * As tres verificações abaixo devem retornar erro 403 para o app conseguir diferenciar um eventual erro de servidor
		 * com estes 3 erros. Vale lembrar també que estas mensagens serão exatamente as que o app irá mostrar
		 * no alert.
		 */
		
		/**
		 * Verifica se esta lista está disponível para o genero do usuario que requisitou a inscrição
		 */
		if (!$VipListSubscription->genderIsAvailable($user_gender, $data['event_id'])) {
			return $this->Response->error(403, 'Você não pode se cadastrar nesta lista devido a restrição de gênero.');
		}
		/**
		 * Verifica se o usuario já está cadastrado na lista
		 */
		if ($VipListSubscription->isSubscribed($data['user_id'], $data['event_id'])){
			return $this->Response->error(403, 'Você já está cadastrado nesta lista');
		}
		/**
		 * Verifica se a lista requisita ainda tem vagas disponíveis para o genero do usuario
		 */
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
		$query = $this->Event->find();

		$query
			->cols([
				'Event.id',
				'Event.name',
				'(
					SELECT count(*) AS total 
					FROM checkins Checkin
					WHERE Checkin.event_id = Event.id AND Checkin.user_id = :user_id
				) AS hasCheckin'
			])
			->where('Event.data_inicio <= CURRENT_TIMESTAMP')
			->where('Event.data_fim >= CURRENT_TIMESTAMP')
			->where('Event.is_active = 1')
			->where('club_id = :club_id')
			->bindValues([
				'user_id' => $this->Auth->user->id,
				'club_id' => $this->Auth->user->club_id
			]);

		$event = $this->Event->one($query);

		return $this->Response->success($event);
	}
}

?>