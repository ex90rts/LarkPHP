<?php
namespace Flexper\Model;

interface AdapterInterface{
	public function insert(array $record);

	public function update(array $query, array $record);

	public function delete(array $query, $limit=0);

	public function query(array $query, array $orders, $offset=0, $limit=0);
}