<?php

namespace App\Controllers;

use App\Models\SanitationInspectionsModel;

class SanitationInspections extends BaseController
{
    public function index()
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }
        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }
        if (! $this->AuthenticateToken()) {
            $this->setError($this->invalidToken, 401);
            return $this->response();
        }
        if (! $this->CheckUserTypePermissions('inspection:view')) {
            return $this->response();
        }

        $model   = new SanitationInspectionsModel();
        $page    = (int) $this->getParam('page', 1);
        $length  = (int) $this->getParam('per_page', 25);
        $assetId = $this->getParam('asset_id', '');
        $allocationId = $this->getParam('allocation_id', '');
        $shiftId = $this->getParam('shift_id', '');
        $swachhagrahiId = $this->getParam('swachhagrahi_id', '');
        $overallStatus = $this->getParam('overall_status', '');
        $dateFrom = $this->getParam('inspection_date_from', '');
        $dateTo   = $this->getParam('inspection_date_to', '');
        $orderCol = $this->getParam('order_by_col', 'inspection_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();
        if ($assetId !== '') {
            $builder->where('asset_id', (int) $assetId);
        }
        if ($allocationId !== '') {
            $builder->where('allocation_id', (int) $allocationId);
        }
        if ($shiftId !== '') {
            $builder->where('shift_id', (int) $shiftId);
        }
        if ($swachhagrahiId !== '') {
            $builder->where('swachhagrahi_id', (int) $swachhagrahiId);
        }
        if ($overallStatus !== '') {
            $builder->where('overall_status', $overallStatus);
        }
        if ($dateFrom !== '') {
            $builder->where('inspection_date >=', $dateFrom);
        }
        if ($dateTo !== '') {
            $builder->where('inspection_date <=', $dateTo);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'sanitation_inspections' => $rows]);
        return $this->response();
    }

    public function view($id)
    {
        if (! $this->isGet()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }
        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }
        if (! $this->AuthenticateToken()) {
            $this->setError($this->invalidToken, 401);
            return $this->response();
        }
        if (! $this->CheckUserTypePermissions('inspection:view')) {
            return $this->response();
        }

        $inspectionId = (int) $id;
        if ($inspectionId < 1) {
            $this->setError('Invalid inspection id.', 400);
            return $this->response();
        }

        $model = new SanitationInspectionsModel();
        $row   = $model->find($inspectionId);
        if (! $row) {
            $this->setError('Sanitation inspection not found.', 404);
            return $this->response();
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($row);
        return $this->response();
    }

    /** for mobile app */
    public function create()
    {
        if (! $this->isPost()) {
            $this->setError($this->methodNotAllowed, 405);
            return $this->response();
        }
        if (! $this->AuthenticateApikey()) {
            $this->setError($this->invalidApiKey, 401);
            return $this->response();
        }
        if (! $this->AuthenticateToken()) {
            $this->setError($this->invalidToken, 401);
            return $this->response();
        }
        if (! $this->CheckUserTypePermissions('inspection:create')) {
            return $this->response();
        }

        $allocationId    = (int) $this->getPost('allocation_id', 0);
        $assetId         = (int) $this->getPost('asset_id', 0);
        $assetTypeId     = (int) $this->getPost('asset_type_id', 0);
        $vendorId        = (int) $this->getPost('vendor_id', 0);
        $sectorId        = (int) $this->getPost('sector_id', 0);
        $circleId        = (int) $this->getPost('circle_id', 0);
        $shiftId         = (int) $this->getPost('shift_id', 0);
        $swachhagrahiId  = (int) $this->_userData['user_id'];
        $swachhagrahiName = $this->_userData['full_name'];
        $inspectionDate  = date('Y-m-d');
        $questionsData   = $this->getPost('questions_answers_data', []);
        $overallStatus   = $this->getPost('overall_status', 'PARTIAL');
        $complianceScore = $this->getPost('compliance_score', null);
        $notes           = $this->getPost('notes', '');
        $latitude        = $this->getPost('latitude', null);
        $longitude       = $this->getPost('longitude', null);

        if ($allocationId < 1 || $assetId < 1 || $shiftId < 1) {
            $this->setError('allocation id, asset id and shift id are required.', 400);
            return $this->response();
        }

        if ($assetTypeId < 1 || $vendorId < 1 || $sectorId < 1 || $circleId < 1) {
            $this->setError('asset type id, vendor id, sector id and circle id are required.', 400);
            return $this->response();
        }

        if ($latitude === null || $longitude === null) {
            $this->setError('latitude and longitude are required.', 400);
            return $this->response();
        }

        if ($complianceScore === null || $complianceScore < 0 || $complianceScore > 100) {
            $this->setError('compliance score must be between 0 and 100.', 400);
            return $this->response();
        }

        if (! is_array($questionsData) || empty($questionsData)) {
            $this->setError('questions answers data is required and must be a non-empty array of { que, ans, photo }.', 400);
            return $this->response();
        }

        $validStatus = ['COMPLIANT', 'NON_COMPLIANT', 'PARTIAL'];
        if (! in_array($overallStatus, $validStatus, true)) {
            $overallStatus = 'PARTIAL';
        }

        $normalized = [];
        foreach ($questionsData as $idx => $item) {
            if (! is_array($item)) {
                $this->setError('questions_answers_data[' . $idx . '] must be an object with que, ans and photo (photo is mandatory).', 400);
                return $this->response();
            }
            $que  = isset($item['que']) ? $item['que'] : '';
            $ans  = isset($item['ans']) ? $item['ans'] : '';
            $photo = isset($item['photo']) ? trim((string) $item['photo']) : '';
            if ($photo === '') {
                $this->setError('questions_answers_data[' . $idx . ']: photo is mandatory for every question.', 400);
                return $this->response();
            }
            $normalized[] = [
                'que'   => $que,
                'ans'   => $ans,
                'photo' => $photo,
            ];
        }

        $questionsAnswersJson = json_encode($normalized);
        $totalQuestions       = count($normalized);
        $questionsAnswered    = $totalQuestions;

        $data = [
            'allocation_id'         => $allocationId,
            'asset_id'              => $assetId,
            'asset_type_id'         => $assetTypeId,
            'vendor_id'             => $vendorId,
            'sector_id'             => $sectorId,
            'circle_id'             => $circleId,
            'shift_id'              => $shiftId,
            'swachhagrahi_id'       => $swachhagrahiId,
            'inspection_date'       => $inspectionDate,
            'total_questions'       => $totalQuestions,
            'questions_answered'     => $questionsAnswered,
            'questions_answers_data' => $questionsAnswersJson,
            'compliance_score'       => $complianceScore,
            'overall_status'        => $overallStatus,
            'notes'                 => $notes,
            'latitude'              => $latitude,
            'longitude'             => $longitude,
        ];

        $model = new SanitationInspectionsModel();
        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create inspection.', 500);
            return $this->response();
        }

        // Validate questions against configured conditions and send grouped notifications if needed
        if (function_exists('inspectQuestionsAndNotify')) {
            try {
                inspectQuestionsAndNotify($normalized, (int) $id, $assetId, $swachhagrahiId, $swachhagrahiName);
            } catch (\Throwable $e) {
                // Notification failures must not break the API response
            }
        }

        $this->setSuccess('Inspection created successfully.');
        $this->setOutput($id);
        return $this->response();
    }
}
