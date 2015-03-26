<?php

namespace App\Model;

use App\Model\AppModel;
use App\Model\Event;

use Datetime;

class VipListSubscription extends AppModel
{
	public $tableName = 'vip_list_subscriptions';
	public $tableAlias = 'VipListSubscription';

	public function genderIsAvailable($gender, $event_id)
	{
		$Event = new Event;
		$query = $Event->find();

		$this->setGenderCol($query, $gender);

		$query
			->where('Event.id = :event_id')
			->bindValues(['event_id' => $event_id]);

		$result = $Event->one($query);

		if ($result->qtd == 0) {
			return false;
		}

		return true;
	}

	public function setGenderCol($query, $gender)
	{
		if ($gender == 'm') {
			return $query->cols(['Event.lista_vip_qtd_masc AS qtd']);
		} else {
			return $query->cols(['Event.lista_vip_qtd_fem AS qtd']);
		}
	}

	public function hasSubscriptionsLeft($gender, $event_id)
	{
		
		$query = $this->find();
		$query
			->cols(['count(*) AS total'])
			->where('event_id = (?)', $event_id)
			->where('sexo = (?)', $gender);

		$totalSubscriptions = $this->one($query)->total;
		
		if ($gender == 'm') {
			$fieldName = 'Event.lista_vip_qtd_masc';
		} else {
			$fieldName = 'Event.lista_vip_qtd_fem';
		}

		$Event = new Event;
		$query = $Event->find();
		$query
			->cols([$fieldName . ' AS limit'])
			->where('Event.id = (?)', $event_id);

		$limit = $Event->one($query)->limit;

		return $totalSubscriptions >= $limit ? false : true;
	}

	public function isSubscribed($user_id, $event_id)
	{
		$query = $this->find();
		$query
			->cols(['count(*) AS total'])
			->where('user_id = :user_id')
			->where('event_id = :event_id')
			->bindValues([
				'user_id' => $user_id,
				'event_id' => $event_id
			]);

		$result = $this->one($query);
		
		if ($result->total > 0) {
			return true;
		}
		return false;
	}

	public function defaultRules()
	{
		$rules = [
			'event_id' => [
				'required',
				'numeric'
			],
			'user_id' => [
				'required',
				'numeric'
			],
			'sexo' => [
				'required',
				['in', ['f', 'm']]
			]
		];
		return $rules;
	}
}