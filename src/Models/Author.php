<?php

require_once __DIR__ . '/../Database.php';

class Author
{

	protected $db;
	protected $table = 'authors';

	public function __construct($db) {
		$this->db = $db;
	}


	public function addAuthor($data){
		if (!isset($data['name']) || !isset($data['email'])){
			return ['success'=>0, 'error' => 'Missing required fields: name, email'];
		}
		$sql = "INSERT INTO {$this->table} (name, email) VALUES (:name, :email)";
		$this->db->execute($sql, [':name' => $data['name'], ':email' => $data['email']]);
		$id = $this->db->lastInsertId();
		return ['success'=> 1, 'id'=>$id];
	}

	public function getAuthor($id) {
		$sql = "SELECT * FROM {$this->table} WHERE id = :id";
		$author = $this->db->fetchOne($sql, [':id' => $id]);
		return isset($author) ? ['success'=>1, 'author'=>$author] : ['success'=>0, 'error'=>'Author not found'];
	}
}