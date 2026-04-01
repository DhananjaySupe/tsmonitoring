<?php

namespace App\Controllers;

class ZeroDev extends BaseController
{
    private const DEFAULT_ASSETS = 200;
    private const GSD_USER_TYPE = 13;
    private const SWACHHAGRAHI_USER_TYPE = 14;
    private const DUMMY_PHOTO_PLACEHOLDER = 'dummy.jpg';
    private const INSERTASSET = false;

    /**
     * Generate dummy data: assets, allocations (per asset per shift), inspections (one per asset per shift per date).
     * Uses existing users only.
     *
     * Optional params (GET or POST): date (Y-m-d), assets (count).
     */
    public function generateDummyData()
    {
        $db = \Config\Database::connect();

        $dateParam = $this->getParam('date', '2026-02-21');
        $date      = $dateParam !== '' ? $dateParam : date('Y-m-d');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1 || strtotime($date) === false) {
            return $this->respond([
                'status'  => false,
                'message' => 'Invalid date. Use Y-m-d (e.g. 2025-02-20).',
            ], 400);
        }

        $assetCount = (int) $this->getParam('assets', (string) self::DEFAULT_ASSETS);
        if ($assetCount < 1 || $assetCount > 5000) {
            return $this->respond([
                'status'  => false,
                'message' => 'assets must be between 1 and 5000.',
            ], 400);
        }

        try {
            $db->transBegin();

            $shifts = $this->getActiveShifts($db);
            if (empty($shifts)) {
                $db->transRollback();
                return $this->respond([
                    'status'  => false,
                    'message' => 'No active shifts found. Create shifts first.',
                ], 400);
            }
            $shiftIds = array_column($shifts, 'shift_id');

            $vendorIds = $this->getActiveVendorIds($db);
            if (empty($vendorIds)) {
                $db->transRollback();
                return $this->respond([
                    'status'  => false,
                    'message' => 'No active vendors found. Create vendors first.',
                ], 400);
            }
            $vendorId = $vendorIds[array_rand($vendorIds)];

            [$gsdUserIds, $swachhUserIds] = $this->getExistingUserIds($db);
            if (empty($gsdUserIds) || empty($swachhUserIds)) {
                $db->transRollback();
                return $this->respond([
                    'status'  => false,
                    'message' => 'Not enough existing users. Need at least one GSD (user_type_id=' . self::GSD_USER_TYPE . ') and one Swachhagrahi (user_type_id=' . self::SWACHHAGRAHI_USER_TYPE . '). Create users first.',
                ], 400);
            }

            if (self::INSERTASSET) {
                [$assetIds, $assetDetails] = $this->seedAssets($db, $gsdUserIds, $assetCount, $vendorId);
            } else {
                [$assetIds, $assetDetails] = $this->getExistingAssets($db, $assetCount);
                if (empty($assetIds)) {
                    $db->transRollback();
                    return $this->respond([
                        'status'  => false,
                        'message' => 'No existing assets found. Create assets first or set INSERTASSET=true to create new assets.',
                    ], 400);
                }
            }
            $allocationByAssetShift = $this->seedAllocations($db, $assetIds, $shiftIds, $swachhUserIds, $gsdUserIds, $date);

            $questions = $db->table('questions')->get()->getResultArray();
            if (empty($questions)) {
                $db->transRollback();
                return $this->respond(['status' => false, 'message' => 'No questions found. Seed questions first.'], 400);
            }
            $questionCount = count($questions);

            $inspectionCount = $this->seedInspectionsShiftWise($db, $assetIds, $assetDetails, $allocationByAssetShift, $shifts, $swachhUserIds, $questions, $questionCount, $date, $vendorId);

            $db->transCommit();

            return $this->respond([
                'status'  => true,
                'message' => 'Dummy data generated successfully.',
                'counts'  => [
                    'assets'        => count($assetIds),
                    'assets_created' => self::INSERTASSET ? count($assetIds) : 0,
                    'allocations'   => count($assetIds) * count($shiftIds),
                    'inspections'   => $inspectionCount,
                    'date'          => $date,
                    'shifts_used'   => count($shiftIds),
                ],
            ]);
        } catch (\Throwable $e) {
            if ($db->transStatus()) {
                $db->transRollback();
            }
            return $this->respond([
                'status'  => false,
                'message' => 'Failed to generate dummy data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return list<int>
     */
    private function getActiveVendorIds(\CodeIgniter\Database\BaseConnection $db): array
    {
        $rows = $db->table('vendors')
            ->select('vendor_id')
            ->where('status', 'ACTIVE')
            ->get()
            ->getResultArray();
        return array_map(static fn ($r) => (int) $r['vendor_id'], $rows);
    }

    /**
     * @return list<array{shift_id: int, start_time: string, end_time: string}>
     */
    private function getActiveShifts(\CodeIgniter\Database\BaseConnection $db): array
    {
        $rows = $db->table('shifts')
            ->select('shift_id, start_time, end_time')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();
        return array_map(static function ($r) {
            return [
                'shift_id'   => (int) $r['shift_id'],
                'start_time' => (string) $r['start_time'],
                'end_time'   => (string) $r['end_time'],
            ];
        }, $rows);
    }

    /**
     * Random datetime on $date between shift start_time and end_time.
     * end_time 00:00:00 (e.g. Evening shift ending at midnight) => use 23:59:59 same day.
     */
    private function randomSubmittedAt(string $date, string $startTime, string $endTime): string
    {
        $startTs = strtotime($date . ' ' . $startTime);
        $endNorm = ($endTime === '00:00:00' || $endTime === '00:00') ? '23:59:59' : $endTime;
        $endTs   = strtotime($date . ' ' . $endNorm);
        if ($endTs <= $startTs) {
            $endTs = strtotime($date . ' 23:59:59');
        }
        $ts = $startTs + (int) (rand(0, $endTs - $startTs));
        return date('Y-m-d H:i:s', $ts);
    }

    /**
     * Get existing user IDs split by role: GSD (allocators) and Swachhagrahi (inspectors).
     *
     * @return array{0: list<int>, 1: list<int>} [gsdUserIds, swachhUserIds]
     */
    private function getExistingUserIds(\CodeIgniter\Database\BaseConnection $db): array
    {
        $rows = $db->table('users')
            ->select('user_id, user_type_id')
            ->where('is_active', 1)
            ->get()
            ->getResultArray();

        $gsd = [];
        $swachh = [];
        foreach ($rows as $row) {
            $id = (int) $row['user_id'];
            $type = (int) $row['user_type_id'];
            if ($type === self::GSD_USER_TYPE) {
                $gsd[] = $id;
            } elseif ($type === self::SWACHHAGRAHI_USER_TYPE) {
                $swachh[] = $id;
            }
        }

        if (empty($gsd) || empty($swachh)) {
            $all = array_column($rows, 'user_id');
            $all = array_map('intval', $all);
            if (count($all) >= 2) {
                $half = (int) floor(count($all) / 2);
                return [
                    array_slice($all, 0, $half),
                    array_slice($all, $half),
                ];
            }
        }

        return [$gsd, $swachh];
    }

    /**
     * Get existing assets from DB (no new assets created). Used when INSERTASSET is false.
     *
     * @return array{0: list<int>, 1: array<int, array{asset_type_id: int, sector_id: int, circle_id: int, vendor_id: int}>}
     */
    private function getExistingAssets(\CodeIgniter\Database\BaseConnection $db, int $limit): array
    {
        $result = $db->table('sanitation_assets')
            ->select('sanitation_asset_id, asset_type_id, sector_id, circle_id, vendor_id')
            ->limit($limit)
            ->get();
        if ($result === false) {
            return [[], []];
        }
        $rows = $result->getResultArray();

        $assetIds     = [];
        $assetDetails = [];
        foreach ($rows as $row) {
            $id = (int) $row['sanitation_asset_id'];
            $assetIds[] = $id;
            $assetDetails[$id] = [
                'asset_type_id' => (int) $row['asset_type_id'],
                'sector_id'     => (int) $row['sector_id'],
                'circle_id'     => (int) $row['circle_id'],
                'vendor_id'     => (int) $row['vendor_id'],
            ];
        }
        return [$assetIds, $assetDetails];
    }

    /**
     * @param list<int> $gsdUserIds
     * @param int $vendorId From vendors table
     * @return array{0: list<int>, 1: array<int, array{asset_type_id: int, sector_id: int, circle_id: int, vendor_id: int}>}
     */
    private function seedAssets(\CodeIgniter\Database\BaseConnection $db, array $gsdUserIds, int $count, int $vendorId): array
    {
        $assetIds     = [];
        $assetDetails = [];
        $genders      = ['MALE', 'FEMALE', 'UNISEX'];
        $baseTime     = time();

        for ($i = 1; $i <= $count; $i++) {
            $assetTypeId = rand(1, 5);
            $sectorId    = rand(1, 3);
            $circleId    = rand(1, 4);
            $db->table('sanitation_assets')->insert([
                'asset_type_id' => $assetTypeId,
                'qr_code'       => 'QR' . $baseTime . rand(10000, 99999) . $i,
                'asset_name'    => 'Toilet Asset ' . $i,
                'gender'        => $genders[array_rand($genders)],
                'vendor_id'     => $vendorId,
                'sector_id'     => $sectorId,
                'circle_id'     => $circleId,
                'latitude'      => 18.50 + (rand(0, 1000) / 10000),
                'longitude'     => 73.85 + (rand(0, 1000) / 10000),
                'created_by'    => $gsdUserIds[array_rand($gsdUserIds)],
            ]);
            $id = $db->insertID();
            $assetIds[] = $id;
            $assetDetails[$id] = [
                'asset_type_id' => $assetTypeId,
                'sector_id'     => $sectorId,
                'circle_id'     => $circleId,
                'vendor_id'     => $vendorId,
            ];
        }
        return [$assetIds, $assetDetails];
    }

    /**
     * One allocation per (asset, shift) for the given date.
     *
     * @param list<int> $assetIds
     * @param list<int> $shiftIds
     * @param list<int> $swachhUserIds
     * @param list<int> $gsdUserIds
     * @return array<int, array<int, int>> asset_id => [shift_id => allocation_id]
     */
    private function seedAllocations(
        \CodeIgniter\Database\BaseConnection $db,
        array $assetIds,
        array $shiftIds,
        array $swachhUserIds,
        array $gsdUserIds,
        string $date
    ): array {
        $allocationByAssetShift = [];
        foreach ($assetIds as $assetId) {
            $allocationByAssetShift[$assetId] = [];
            foreach ($shiftIds as $shiftId) {
                $db->table('sanitation_asset_allocations')->insert([
                    'asset_id'         => $assetId,
                    'swachhagrahi_id'  => $swachhUserIds[array_rand($swachhUserIds)],
                    'shift_id'         => $shiftId,
                    'allocated_by'     => $gsdUserIds[array_rand($gsdUserIds)],
                    'allocation_date'  => $date,
                    'status'           => 'ACTIVE',
                ]);
                $allocationByAssetShift[$assetId][$shiftId] = $db->insertID();
            }
        }
        return $allocationByAssetShift;
    }

    /**
     * One inspection per (asset, shift) for the given date; respects unique (asset_id, shift_id, inspection_date).
     * submitted_at is set to a random time within the shift on the date; sanitation_incidents.created_at matches.
     *
     * @param list<int> $assetIds
     * @param array<int, array{asset_type_id: int, sector_id: int, circle_id: int, vendor_id: int}> $assetDetails
     * @param array<int, array<int, int>> $allocationByAssetShift
     * @param list<array{shift_id: int, start_time: string, end_time: string}> $shifts
     * @param list<int> $swachhUserIds
     * @param array<int, array> $questions
     * @param int $defaultVendorId From vendors table (fallback when asset details missing)
     * @return int Number of inspections created
     */
    private function seedInspectionsShiftWise(
        \CodeIgniter\Database\BaseConnection $db,
        array $assetIds,
        array $assetDetails,
        array $allocationByAssetShift,
        array $shifts,
        array $swachhUserIds,
        array $questions,
        int $questionCount,
        string $date,
        int $defaultVendorId
    ): int {
        $incidentCodeSeq = 0;
        $created         = 0;

        foreach ($assetIds as $assetId) {
            $details = $assetDetails[$assetId] ?? [
                'asset_type_id' => 1,
                'sector_id'     => 1,
                'circle_id'     => 1,
                'vendor_id'     => $defaultVendorId,
            ];
            foreach ($shifts as $shift) {
                $shiftId   = $shift['shift_id'];
                $allocationId = $allocationByAssetShift[$assetId][$shiftId] ?? null;
                if ($allocationId === null) {
                    continue;
                }
                $swachhId = $swachhUserIds[array_rand($swachhUserIds)];
                $submittedAt = $this->randomSubmittedAt($date, $shift['start_time'], $shift['end_time']);

                $answersForJson = [];
                $incidentRows   = [];

                foreach ($questions as $q) {
                    $answer = (rand(0, 100) < 30) ? 'NO' : 'YES';
                    $answersForJson[] = [
                        'que'   => (string) $q['question_id'],
                        'ans'   => $answer,
                        'photo' => self::DUMMY_PHOTO_PLACEHOLDER,
                    ];

                    if (
                        isset($q['condition_type'], $q['condition_value']) &&
                        $q['condition_type'] === 'EQUALS' &&
                        (string) $answer === (string) $q['condition_value']
                    ) {
                        $incidentCodeSeq++;
                        $sla = (int) ($q['sla'] ?? 120);
                        $incidentRows[] = [
                            'incident_code'  => 'INC' . $date . '-' . $incidentCodeSeq . '-' . rand(1000, 9999),
                            'inspection_id'  => 0,
                            'response_id'    => 0,
                            'asset_id'       => $assetId,
                            'question_id'    => $q['question_id'],
                            'reported_by'    => $swachhId,
                            'vendor_id'      => $details['vendor_id'],
                            'severity'       => $q['severity'] ?? 'MEDIUM',
                            'description'    => 'Dummy auto incident',
                            'due_date'       => date('Y-m-d H:i:s', strtotime($submittedAt . ' +' . $sla . ' minutes')),
                            'created_at'     => $submittedAt,
                        ];
                    }
                }

                $overallStatus = ! empty($incidentRows) ? 'NON_COMPLIANT' : (rand(0, 10) === 0 ? 'PARTIAL' : 'COMPLIANT');

                $db->table('sanitation_inspections')->insert([
                    'allocation_id'           => $allocationId,
                    'asset_id'                => $assetId,
                    'asset_type_id'           => $details['asset_type_id'],
                    'vendor_id'               => $details['vendor_id'],
                    'sector_id'               => $details['sector_id'],
                    'circle_id'               => $details['circle_id'],
                    'shift_id'                => $shiftId,
                    'swachhagrahi_id'         => $swachhId,
                    'inspection_date'         => $date,
                    'total_questions'        => $questionCount,
                    'questions_answered'     => $questionCount,
                    'questions_answers_data' => json_encode($answersForJson),
                    'compliance_score'        => rand(60, 100),
                    'overall_status'          => $overallStatus,
                    'latitude'                => '18.5204',
                    'longitude'               => '73.8567',
                    'submitted_at'            => $submittedAt,
                ]);
                $inspectionId = $db->insertID();
                $created++;

                foreach ($incidentRows as $row) {
                    $row['inspection_id'] = $inspectionId;
                    $row['created_at']   = $submittedAt;
                    $db->table('sanitation_incidents')->insert($row);
                }
            }
        }

        return $created;
    }
}
