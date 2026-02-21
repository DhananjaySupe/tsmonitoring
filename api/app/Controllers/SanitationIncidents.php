<?php

namespace App\Controllers;

use App\Models\SanitationIncidentsModel;
use App\Models\UsersModel;

class SanitationIncidents extends BaseController
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
        if (! $this->CheckUserTypePermissions('incident:view')) {
            return $this->response();
        }

        $model    = new SanitationIncidentsModel();
        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $assetId  = $this->getParam('asset_id', '');
        $vendorId = $this->getParam('vendor_id', '');
        $inspectionId = $this->getParam('inspection_id', '');
        $incidentStatus = $this->getParam('incident_status', '');
        $severity = $this->getParam('severity', '');
        $dateFrom = $this->getParam('created_at_from', '');
        $dateTo   = $this->getParam('created_at_to', '');
        $orderCol = $this->getParam('order_by_col', 'incident_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();
        if ($assetId !== '') {
            $builder->where('asset_id', (int) $assetId);
        }
        if ($vendorId !== '') {
            $builder->where('vendor_id', (int) $vendorId);
        }
        if ($inspectionId !== '') {
            $builder->where('inspection_id', (int) $inspectionId);
        }
        if ($incidentStatus !== '') {
            $builder->where('incident_status', $incidentStatus);
        }
        if ($severity !== '') {
            $builder->where('severity', $severity);
        }
        if ($dateFrom !== '') {
            $builder->where('created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $builder->where('created_at <=', $dateTo . ' 23:59:59');
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'sanitation_incidents' => $rows]);
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
        if (! $this->CheckUserTypePermissions('incident:view')) {
            return $this->response();
        }

        $incidentId = (int) $id;
        if ($incidentId < 1) {
            $this->setError('Invalid incident id.', 400);
            return $this->response();
        }

        $model = new SanitationIncidentsModel();
        $row   = $model->find($incidentId);
        if (! $row) {
            $this->setError('Sanitation incident not found.', 404);
            return $this->response();
        }

        $this->setSuccess($this->successMessage);
        $this->setOutput($row);
        return $this->response();
    }

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
        if (! $this->CheckUserTypePermissions('incident:create')) {
            return $this->response();
        }

        $inspectionId = (int) $this->getPost('inspection_id', 0);
        $assetId      = (int) $this->getPost('asset_id', 0);
        $questionId   = (int) $this->getPost('question_id', 0);
        $vendorId     = (int) $this->getPost('vendor_id', 0);
        $severity     = $this->getPost('severity', 'MEDIUM');
        $description  = $this->getPost('description', '');
        $responseId   = (int) $this->getPost('response_id', 0);

        if ($inspectionId < 1 || $assetId < 1 || $questionId < 1 || $vendorId < 1) {
            $this->setError('inspection_id, asset_id, question_id and vendor_id are required.', 400);
            return $this->response();
        }

        $validSeverity = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
        if (! in_array($severity, $validSeverity, true)) {
            $severity = 'MEDIUM';
        }

        $incidentCode = 'INC' . date('YmdHis') . '_' . $inspectionId . '_' . $questionId;
        $data = [
            'incident_code'   => $incidentCode,
            'inspection_id'   => $inspectionId,
            'response_id'     => $responseId,
            'asset_id'        => $assetId,
            'question_id'     => $questionId,
            'reported_by'     => (int) $this->_userData['user_id'],
            'vendor_id'       => $vendorId,
            'severity'        => $severity,
            'description'     => $description,
            'incident_status' => 'OPEN',
        ];

        $model = new SanitationIncidentsModel();
        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create incident.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Incident created successfully.');
        $this->setOutput($row);
        return $this->response();
    }

    public function edit($id)
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
        if (! $this->CheckUserTypePermissions('incident:edit')) {
            return $this->response();
        }

        $incidentId = (int) $id;
        if ($incidentId < 1) {
            $this->setError('Invalid incident id.', 400);
            return $this->response();
        }

        $model = new SanitationIncidentsModel();
        $row   = $model->find($incidentId);
        if (! $row) {
            $this->setError('Sanitation incident not found.', 404);
            return $this->response();
        }

        $severity     = $this->getPost('severity', $row['severity'] ?? 'MEDIUM');
        $description  = $this->getPost('description', $row['description'] ?? '');
        $incidentStatus = $this->getPost('incident_status', $row['incident_status'] ?? 'OPEN');
        $dueDate      = $this->getPost('due_date', $row['due_date'] ?? null);

        $validSeverity = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
        if (! in_array($severity, $validSeverity, true)) {
            $severity = $row['severity'] ?? 'MEDIUM';
        }
        $validStatus = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'REOPENED'];
        if (! in_array($incidentStatus, $validStatus, true)) {
            $incidentStatus = $row['incident_status'] ?? 'OPEN';
        }

        $data = [
            'severity'        => $severity,
            'description'     => $description,
            'incident_status' => $incidentStatus,
            'due_date'        => $dueDate !== '' && $dueDate !== null ? $dueDate : null,
        ];

        $model->update($incidentId, $data);
        $updated = $model->find($incidentId);
        $this->setSuccess('Sanitation incident updated successfully.');
        $this->setOutput($updated);
        return $this->response();
    }

    /**
     * Close incident: set resolved_by, resolved_date, incident_status=CLOSED.
     * Allowed for vendor or vendor supervisor only for their vendor's incidents;
     * admins with incident:close can close any incident.
     */
    public function close($id)
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
        if (! $this->CheckUserTypePermissions('incident:edit')) {
            return $this->response();
        }

        $incidentId = (int) $id;
        if ($incidentId < 1) {
            $this->setError('Invalid incident id.', 400);
            return $this->response();
        }

        $model = new SanitationIncidentsModel();
        $row   = $model->select('incident_id, incident_status, vendor_id')->where('incident_id', $incidentId)->first();
        if (! $row) {
            $this->setError('Sanitation incident not found.', 404);
            return $this->response();
        }

        if (in_array($row['incident_status'] ?? '', ['CLOSED'], true)) {
            $this->setError('Incident is already closed.', 400);
            return $this->response();
        }

        $userId = (int) $this->_userData['user_id'];
        $usersModel = new UsersModel();
        $userRow = $usersModel->select('vendor_id')->where('user_id', $userId)->first();
        $userVendorId = isset($userRow['vendor_id']) && $userRow['vendor_id'] !== null && $userRow['vendor_id'] !== ''
            ? (int) $userRow['vendor_id']
            : null;

        if ($userVendorId !== null && $userVendorId > 0) {
            $incidentVendorId = (int) ($row['vendor_id'] ?? 0);
            if ($incidentVendorId !== $userVendorId) {
                $this->setError('You can only close incidents assigned to your vendor.', 403);
                return $this->response();
            }
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'resolved_by'     => $userId,
            'resolved_date'   => $now,
            'incident_status' => 'CLOSED',
            'closed_date'     => $now,
        ];

        $model->update($incidentId, $data);
        $updated = $model->find($incidentId);
        $this->setSuccess('Incident closed successfully.');
        $this->setOutput($updated);
        return $this->response();
    }
}
