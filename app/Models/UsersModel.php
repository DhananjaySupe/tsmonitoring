<?php namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'user_id';
    protected $returnType = 'array';

    /** @var array<string, string> Validation rules for insert and update */
  /*  protected $validationRules = [
        'code'         => 'permit_empty|max_length[50]|is_unique[users.code,user_id,{user_id}]',
        'password_hash'=> 'permit_empty|min_length[60]|max_length[255]',
        'full_name'    => 'required|max_length[100]',
        'email'        => 'permit_empty|valid_email|max_length[100]|is_unique[users.email,user_id,{user_id}]',
        'phone'        => 'permit_empty|max_length[15]|is_unique[users.phone,user_id,{user_id}]',
        'user_type_id' => 'required|integer|greater_than_equal_to[0]',
        'vendor_id'    => 'permit_empty|integer|greater_than_equal_to[0]',
        'is_active'   => 'permit_empty|in_list[0,1]',
        'lang'        => 'permit_empty|in_list[en,hi,mr]',
    ];

    protected $validationMessages = [
        'code' => [
            'max_length' => 'User code must not exceed 50 characters.',
            'is_unique'  => 'This user code is already registered.',
        ],
        'password_hash' => [
            'min_length' => 'Invalid password hash.',
            'max_length' => 'Invalid password hash.',
        ],
        'full_name' => [
            'required'  => 'Full name is required.',
            'max_length'=> 'Full name must not exceed 100 characters.',
        ],
        'email' => [
            'valid_email'=> 'Please provide a valid email address.',
            'max_length' => 'Email must not exceed 100 characters.',
            'is_unique'  => 'This email is already registered.',
        ],
        'phone' => [
            'max_length'=> 'Phone must not exceed 15 characters.',
            'is_unique' => 'This phone number is already registered.',
        ],
        'user_type_id' => [
            'required'            => 'User type is required.',
            'integer'             => 'User type must be a number.',
            'greater_than_equal_to'=> 'User type must be 0 or greater.',
        ],
        'vendor_id' => [
            'integer'             => 'Vendor ID must be a number.',
            'greater_than_equal_to'=> 'Vendor ID must be 0 or greater.',
        ],
        'is_active' => [
            'in_list' => 'Active status must be 0 or 1.',
        ],
    ];*/

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
        'lang',
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

