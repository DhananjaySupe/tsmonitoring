<?php

namespace App\Controllers;

use App\Models\SanitationIncidentsArchiveModel;
use App\Models\SanitationIncidentsModel;
use App\Models\UsersModel;

class SanitationIncidents extends BaseController
{
    private const INCIDENT_STATUSES = ['OPEN', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'REOPENED'];

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

        $page     = (int) $this->getParam('page', 1);
        $length   = (int) $this->getParam('per_page', 25);
        $assetId  = $this->getParam('asset_id', '');
        $vendorId = $this->getParam('vendor_id', '');
        $inspectionId = $this->getParam('inspection_id', '');
        $incidentStatus = $this->getParam('incident_status', '');
        $severity = $this->getParam('severity', '');
        $dateFrom = trim($this->getParam('created_at_from', ''));
        $dateTo   = trim($this->getParam('created_at_to', ''));
        $orderCol = $this->getParam('order_by_col', 'incident_id');
        $orderDir = $this->getParam('order_by', 'DESC');

        $validation = validateDateRange($dateFrom, $dateTo);
        if (! $validation['valid']) {
            $this->setError($validation['error'], 400);
            return $this->response();
        }

        $today = date('Y-m-d');
        $useCurrentTable = ($dateTo === $today && $dateFrom === $today);
        if ($useCurrentTable) {
            $model = new SanitationIncidentsModel();
        } else {
            $model = new SanitationIncidentsArchiveModel();
            if ($dateTo === '') {
                $dateTo = date('Y-m-d', strtotime('-1 day'));
            }
        }

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
            $archiveModel = new SanitationIncidentsArchiveModel();
            $row = $archiveModel->find($incidentId);
        }
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
            $model = new SanitationIncidentsArchiveModel();
            $row   = $model->find($incidentId);
        }
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
        if (! in_array($incidentStatus, self::INCIDENT_STATUSES, true)) {
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
     * Update incident status only. Allowed: OPEN, ASSIGNED, IN_PROGRESS, RESOLVED, CLOSED, REOPENED.
     * POST incident_status (required). When CLOSED, sets resolved_by, resolved_date, closed_date.
     * Vendor users can only set CLOSED for incidents of their vendor_id.
     */
    public function updateStatus($id)
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
        $newStatus = trim($this->getPost('incident_status', ''));
        if ($newStatus === '' || ! in_array($newStatus, self::INCIDENT_STATUSES, true)) {
            $this->setError('incident_status is required and must be one of: ' . implode(', ', self::INCIDENT_STATUSES) . '.', 400);
            return $this->response();
        }
        list($model, $row) = $this->findIncidentModelAndRow($incidentId);
        if ($model === null || $row === null) {
            $this->setError('Sanitation incident not found.', 404);
            return $this->response();
        }

        $result = $this->applyIncidentStatusUpdate($incidentId, $newStatus);
        if ($result === null) {
            $this->setError('Sanitation incident not found.', 404);
            return $this->response();
        }
        $this->setSuccess('Incident status updated successfully.');
        $this->setOutput($result['row']);
        return $this->response();
    }

    /**
     * Bulk update incident status. Date determines table: if date is today use SanitationIncidentsModel, else SanitationIncidentsArchiveModel.
     * POST: date (required, Y-m-d), incident_status (required), incident_ids (required, array of ints).
     */
    public function bulkUpdateStatus()
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

        $dateParam = trim($this->getPost('date', ''));
        if ($dateParam === '') {
            $this->setError('date is required (Y-m-d).', 400);
            return $this->response();
        }
        $dateObj = \DateTime::createFromFormat('Y-m-d', $dateParam);
        if (! $dateObj || $dateObj->format('Y-m-d') !== $dateParam) {
            $this->setError('date must be valid Y-m-d.', 400);
            return $this->response();
        }

        $newStatus = trim($this->getPost('incident_status', ''));
        if ($newStatus === '' || ! in_array($newStatus, self::INCIDENT_STATUSES, true)) {
            $this->setError('incident_status is required and must be one of: ' . implode(', ', self::INCIDENT_STATUSES) . '.', 400);
            return $this->response();
        }

        $incidentIdsRaw = $this->getPost('incident_ids', []);
        if (! is_array($incidentIdsRaw)) {
            $incidentIdsRaw = [];
        }
        $incidentIds = array_values(array_unique(array_filter(array_map('intval', $incidentIdsRaw))));
        if (empty($incidentIds)) {
            $this->setError('incident_ids is required and must be a non-empty array of incident ids.', 400);
            return $this->response();
        }

        $today = date('Y-m-d');
        if ($dateParam === $today) {
            $model = new SanitationIncidentsModel();
        } else {
            $model = new SanitationIncidentsArchiveModel();
        }

        $userId = (int) $this->_userData['user_id'];
        $now    = date('Y-m-d H:i:s');
        $data   = ['incident_status' => $newStatus];
        if ($newStatus === 'CLOSED') {
            $data['resolved_by']   = $userId;
            $data['resolved_date'] = $now;
            $data['closed_date']   = $now;
        }

        $model->builder()->whereIn('incident_id', $incidentIds)->update($data);
        $affected = $model->db->affectedRows();

        $this->setSuccess('Bulk status update completed.');
        $this->setOutput([
            'updated_count' => $affected,
            'date'          => $dateParam,
            'incident_status' => $newStatus,
            'incident_ids'  => $incidentIds,
        ]);
        return $this->response();
    }

    private function findIncidentModelAndRow(int $incidentId): array
    {
        $model = new SanitationIncidentsModel();
        $row   = $model->find($incidentId);
        if ($row) {
            return [$model, $row];
        }
        $archiveModel = new SanitationIncidentsArchiveModel();
        $row = $archiveModel->find($incidentId);
        if ($row) {
            return [$archiveModel, $row];
        }
        return [null, null];
    }

    private function applyIncidentStatusUpdate(int $incidentId, string $newStatus): ?array
    {
        if (! in_array($newStatus, self::INCIDENT_STATUSES, true)) {
            return null;
        }
        [$model, $row] = $this->findIncidentModelAndRow($incidentId);
        if ($model === null || $row === null) {
            return null;
        }
        $userId = (int) $this->_userData['user_id'];
        $now    = date('Y-m-d H:i:s');
        $data   = ['incident_status' => $newStatus];
        if ($newStatus === 'CLOSED') {
            $data['resolved_by']   = $userId;
            $data['resolved_date'] = $now;
            $data['closed_date']   = $now;
        }
        $model->update($incidentId, $data);
        $updated = $model->find($incidentId);
        return $updated ? ['model' => $model, 'row' => $updated] : null;
    }
}
