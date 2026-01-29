<?php namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'user_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'user_id',
        'code',
        'password_hash',
        'email',
        'phone',
        'full_name',
        'user_type_id',
        'vendor_id',
        'is_active',
        'otp',
        'otp_expiry',
        'otp_attempts',
        'created_at',
        'updated_at',
    ];

    public function getList(array $params = [])
    {
        $builder = $this->builder();
        $builder->select('user_id, email, phone, full_name, user_type_id, vendor_id, is_active, created_at, updated_at');

        if (! empty($params['keywords'])) {
            $k = $this->db->escapeLikeString($params['keywords']);
            $builder->groupStart()
                ->like('phone', $k)
                ->orLike('full_name', $k)
                ->orLike('email', $k)
                ->groupEnd();
        }

        if (isset($params['user_type_id']) && $params['user_type_id'] !== '') {
            $builder->where('user_type_id', (int) $params['user_type_id']);
        }
        if (isset($params['is_active']) && $params['is_active'] !== '') {
            $builder->where('is_active', (int) $params['is_active']);
        }

        if (! empty($params['count'])) {
            return (int) $builder->countAllResults(false);
        }

        if (! empty($params['sort']['column'])) {
            $col = $params['sort']['column'];
            $order = strtoupper($params['sort']['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
            $builder->orderBy($col, $order);
        } else {
            $builder->orderBy('user_id', 'DESC');
        }

        if (! empty($params['limit']['length'])) {
            $builder->limit((int) $params['limit']['length'], (int) ($params['limit']['offset'] ?? 0));
        }

        return $builder->get()->getResultArray();
    }

    public function getForView($userId)
    {
        $row = $this->select('user_id, email, phone, full_name, user_type_id, vendor_id, is_active, created_at, updated_at')
            ->find($userId);
        return $row;
    }
}

