<?php namespace App\Models;

use CodeIgniter\Model;

class SanitationAssetAllocationsModel extends Model
{
    protected $table      = 'sanitation_asset_allocations';
    protected $primaryKey = 'allocation_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'allocation_id',
        'asset_id',
        'swachhagrahi_id',
        'shift_id',
        'allocated_by',
        'allocation_date',
        'status',
        'created_at',
    ];



    public function getAllocations(int $swachhagrahiId, array $options = []): array
    {
        $db     = $this->db;
        $page   = isset($options['page']) ? (int) $options['page'] : 1;
        $length = isset($options['per_page']) ? (int) $options['per_page'] : 25;
        $page   = $page < 1 ? 1 : $page;
        $length = $length < 1 ? 25 : $length;

        $builder = $db->table('sanitation_asset_allocations a')
            ->select(
                'a.allocation_id, a.asset_id, a.swachhagrahi_id, a.shift_id, a.allocated_by, a.allocation_date, a.status AS allocation_status, a.created_at AS allocation_created_at, ' .
                'sa.sanitation_asset_id, sa.asset_type_id, sa.qr_code, sa.asset_name, sa.short_url, sa.description, sa.gender, sa.vendor_id, sa.vendor_asset_code, ' .
                'sa.status AS asset_status, sa.sector_id, sa.circle_id, sa.latitude, sa.longitude, sa.photo, ' .
                'at.asset_type_id AS type_asset_type_id, at.name AS type_name, at.description AS type_description'
            )
            ->join('sanitation_assets sa', 'a.asset_id = sa.sanitation_asset_id')
            ->join('asset_types at', 'sa.asset_type_id = at.asset_type_id')
            ->where('a.swachhagrahi_id', $swachhagrahiId);

        if (! empty($options['status'])) {
            $builder->where('a.status', $options['status']);
        }
        if (! empty($options['allocation_date_from'])) {
            $builder->where('a.allocation_date >=', $options['allocation_date_from']);
        }
        if (! empty($options['allocation_date_to'])) {
            $builder->where('a.allocation_date <=', $options['allocation_date_to']);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);

        $dataBuilder = $db->table('sanitation_asset_allocations a')
            ->select(
                'a.allocation_id, a.asset_id, a.swachhagrahi_id, a.shift_id, a.allocated_by, a.allocation_date, a.status AS allocation_status, a.created_at AS allocation_created_at, ' .
                'sa.sanitation_asset_id, sa.asset_type_id, sa.qr_code, sa.asset_name, sa.short_url, sa.description, sa.gender, sa.vendor_id, sa.vendor_asset_code, ' .
                'sa.status AS asset_status, sa.sector_id, sa.circle_id, sa.latitude, sa.longitude, sa.photo, ' .
                'at.asset_type_id AS type_asset_type_id, at.name AS type_name, at.description AS type_description'
            )
            ->join('sanitation_assets sa', 'a.asset_id = sa.sanitation_asset_id')
            ->join('asset_types at', 'sa.asset_type_id = at.asset_type_id')
            ->where('a.swachhagrahi_id', $swachhagrahiId);
        if (! empty($options['status'])) {
            $dataBuilder->where('a.status', $options['status']);
        }
        if (! empty($options['allocation_date_from'])) {
            $dataBuilder->where('a.allocation_date >=', $options['allocation_date_from']);
        }
        if (! empty($options['allocation_date_to'])) {
            $dataBuilder->where('a.allocation_date <=', $options['allocation_date_to']);
        }
        $dataBuilder->orderBy('a.allocation_date', 'DESC')->orderBy('a.allocation_id', 'DESC');
        $dataBuilder->limit($paging['length'], $paging['offset']);
        $rows = $dataBuilder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        if (empty($rows)) {
            return ['paging' => $paging, 'allocations' => []];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'allocation' => [
                    'allocation_id'   => (int) $row['allocation_id'],
                    'asset_id'        => (int) $row['asset_id'],
                    'swachhagrahi_id' => (int) $row['swachhagrahi_id'],
                    'shift_id'        => (int) $row['shift_id'],
                    'allocated_by'    => (int) $row['allocated_by'],
                    'allocation_date' => $row['allocation_date'],
                    'status'          => $row['allocation_status'],
                    'created_at'      => $row['allocation_created_at'],
                ],
                'asset' => [
                    'sanitation_asset_id' => (int) $row['sanitation_asset_id'],
                    'asset_type_id'       => (int) $row['asset_type_id'],
                    'qr_code'             => $row['qr_code'],
                    'asset_name'           => $row['asset_name'],
                    'short_url'            => $row['short_url'],
                    'description'          => $row['description'],
                    'gender'               => $row['gender'],
                    'vendor_id'            => (int) $row['vendor_id'],
                    'vendor_asset_code'    => $row['vendor_asset_code'],
                    'status'               => $row['asset_status'],
                    'sector_id'            => (int) $row['sector_id'],
                    'circle_id'             => (int) $row['circle_id'],
                    'latitude'              => $row['latitude'],
                    'longitude'             => $row['longitude'],
                    'photo'     => $row['photo'],
                ],
                'asset_type' => [
                    'asset_type_id' => (int) $row['type_asset_type_id'],
                    'name'          => $row['type_name'],
                    'description'   => $row['type_description'],
                ],
            ];
        }

        return ['paging' => $paging, 'allocations' => $result];
    }

    public function getAllocationDetails(int $allocationId): ?array
    {
        $db = $this->db;

        $builder = $db->table('sanitation_asset_allocations a')
            ->select(
                'a.allocation_id, a.asset_id, a.swachhagrahi_id, a.shift_id, a.allocated_by, a.allocation_date, a.status AS allocation_status, a.created_at AS allocation_created_at, ' .
                'sa.sanitation_asset_id, sa.asset_type_id, sa.qr_code, sa.asset_name, sa.short_url, sa.description, sa.gender, sa.vendor_id, sa.vendor_asset_code, ' .
                'sa.status AS asset_status, sa.sector_id, sa.circle_id, sa.latitude, sa.longitude, sa.photo, ' .
                'at.asset_type_id AS type_asset_type_id, at.name AS type_name, at.description AS type_description, at.questions AS type_questions'
            )
            ->join('sanitation_assets sa', 'a.asset_id = sa.sanitation_asset_id')
            ->join('asset_types at', 'sa.asset_type_id = at.asset_type_id')
            ->where('a.allocation_id', $allocationId);

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return null;
        }
        $row = $rows[0];

        $questionIds = [];
        if (! empty($row['type_questions'])) {
            $questionIds = array_map('intval', array_filter(explode(',', (string) $row['type_questions'])));
        }
        $questionsMap = [];
        if ($questionIds !== []) {
            $questionsRows = $db->table('questions')
                ->whereIn('question_id', $questionIds)
                ->where('is_active', 1)
                ->orderBy('sequence', 'ASC')
                ->orderBy('question_id', 'ASC')
                ->get()
                ->getResultArray();
            foreach ($questionsRows as $q) {
                $questionsMap[$q['question_id']] = $q;
            }
        }
        $inspectionQuestions = [];
        foreach ($questionIds as $qid) {
            if (isset($questionsMap[$qid])) {
                $inspectionQuestions[] = $questionsMap[$qid];
            }
        }

        return [
            'allocation' => [
                'allocation_id'    => (int) $row['allocation_id'],
                'asset_id'         => (int) $row['asset_id'],
                'swachhagrahi_id'  => (int) $row['swachhagrahi_id'],
                'shift_id'         => (int) $row['shift_id'],
                'allocated_by'     => (int) $row['allocated_by'],
                'allocation_date'  => $row['allocation_date'],
                'status'          => $row['allocation_status'],
                'created_at'       => $row['allocation_created_at'],
            ],
            'asset' => [
                'sanitation_asset_id'  => (int) $row['sanitation_asset_id'],
                'asset_type_id'        => (int) $row['asset_type_id'],
                'qr_code'              => $row['qr_code'],
                'asset_name'           => $row['asset_name'],
                'short_url'            => $row['short_url'],
                'description'          => $row['description'],
                'gender'               => $row['gender'],
                'vendor_id'            => (int) $row['vendor_id'],
                'vendor_asset_code'    => $row['vendor_asset_code'],
                'status'               => $row['asset_status'],
                'sector_id'            => (int) $row['sector_id'],
                'circle_id'            => (int) $row['circle_id'],
                'latitude'             => $row['latitude'],
                'longitude'            => $row['longitude'],
                'photo'    => $row['photo'],
            ],
            'asset_type' => [
                'asset_type_id' => (int) $row['type_asset_type_id'],
                'name'          => $row['type_name'],
                'description'   => $row['type_description'],
            ],
            'inspection_questions' => $inspectionQuestions,
        ];
    }
}

