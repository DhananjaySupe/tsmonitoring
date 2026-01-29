<?php

namespace App\Controllers;

use App\Models\InspectionsModel;

class Inspections extends BaseController
{
    /**
     * Create inspection.
     * POST api/inspections/new
     * Body: allocation_id, asset_id, shift_id, swachhagrahi_id, inspection_date,
     *       questions_answers_data (array of { que, ans, photo } - photo mandatory),
     *       overall_status, compliance_score, notes, latitude, longitude
     */
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
        $shiftId         = (int) $this->getPost('shift_id', 0);
        $swachhagrahiId  = (int) $this->_userData['user_id'];
        $inspectionDate  = date('Y-m-d');
        $questionsData   = $this->getPost('questions_answers_data', []);
        $overallStatus   = $this->getPost('overall_status', 'PARTIAL');
        $complianceScore = $this->getPost('compliance_score', null);
        $notes           = $this->getPost('notes', '');
        $latitude        = $this->getPost('latitude', null);
        $longitude       = $this->getPost('longitude', null);

        if ($allocationId < 1 || $assetId < 1 || $shiftId < 1) {
            $this->setError('allocation_id, asset_id, shift_id are required.', 400);
            return $this->response();
        }

        if ($latitude === null || $longitude === null) {
            $this->setError('latitude and longitude are required.', 400);
            return $this->response();
        }

        if ($complianceScore === null || $complianceScore < 0 || $complianceScore > 100) {
            $this->setError('compliance_score must be between 0 and 100.', 400);
            return $this->response();
        }

        if (! is_array($questionsData) || empty($questionsData)) {
            $this->setError('questions_answers_data is required and must be a non-empty array of { que, ans, photo }.', 400);
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

        $model = new InspectionsModel();
        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create inspection.', 500);
            return $this->response();
        }

        $this->setSuccess('Inspection created successfully.');
        $this->setOutput($id);
        return $this->response();
    }
}
