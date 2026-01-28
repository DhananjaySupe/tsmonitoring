<?php namespace App\Models;
	use CodeIgniter\Model;
	class SessionsModel extends Model
	{
		protected $table = 'session';
		protected $primaryKey = 'session_id';
		protected $returnType = 'array';
		protected $allowedFields = ['session_id', 'user_id', 'session_token', 'logged_in', 'logged_out', 'status'];
		protected $createdField = 'created_at';
		protected $updatedField = 'updated_at';

		public function findByToken($token)
		{
			$builder = $this->db->table($this->table);
			$builder->select($this->table . '.session_id, ' . $this->table . '.session_token, ' . $this->table . '.user_id, ' . $this->table . '.status');
			$builder->where($this->table . '.session_token', $token);
			$builder->limit(1);
			$result = $builder->get()->getResultArray();
			return $result ? $result[0] : null;
		}
	}