<?php namespace App\Models;

use CodeIgniter\Model;

class UserTypePermissionsModel extends Model
{
    protected $table      = 'user_type_permissions';
    protected $primaryKey = 'permission_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'permission_id',
        'user_type_id',
        'permission',
        'can_create',
        'can_view',
        'can_edit',
        'can_delete',
        'created_at',
    ];

    public function getList(array $params = [])
    {
        $builder = $this->builder();

        if (isset($params['user_type_id']) && $params['user_type_id'] !== '') {
            $builder->where('user_type_id', (int) $params['user_type_id']);
        }
        if (isset($params['permission']) && $params['permission'] !== '') {
            $builder->where('permission', $params['permission']);
        }

        if (! empty($params['count'])) {
            return (int) $builder->countAllResults(false);
        }

        $builder->orderBy('permission_id', 'DESC');

        if (! empty($params['limit']['length'])) {
            $builder->limit((int) $params['limit']['length'], (int) ($params['limit']['offset'] ?? 0));
        }

        return $builder->get()->getResultArray();
    }
}

