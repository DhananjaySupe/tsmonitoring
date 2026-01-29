<?php

namespace App\Controllers;

use App\Models\QuestionsModel;

class Questions extends BaseController
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
        if (! $this->CheckUserTypePermissions('question:view')) {
            return $this->response();
        }

        $model        = new QuestionsModel();
        $page         = (int) $this->getParam('page', 1);
        $length       = (int) $this->getParam('per_page', 25);
        $keywords     = $this->getParam('keywords', '');
        $questionType = $this->getParam('question_type', '');
        $severity     = $this->getParam('severity', '');
        $isActive     = $this->getParam('is_active', '');
        $orderCol     = $this->getParam('order_by_col', 'question_id');
        $orderDir     = $this->getParam('order_by', 'DESC');

        $builder = $model->builder();

        if ($keywords !== '') {
            $k = $model->db->escapeLikeString($keywords);
            $builder->groupStart()
                ->like('question_text', $k)
                ->orLike('expected_answer', $k)
                ->groupEnd();
        }
        if ($questionType !== '') {
            $builder->where('question_type', $questionType);
        }
        if ($severity !== '') {
            $builder->where('severity', $severity);
        }
        if ($isActive !== '') {
            $builder->where('is_active', (int) $isActive);
        }

        $totalRecords = $builder->countAllResults(false);

        $paging = paging($page, $totalRecords, $length);
        $builder->orderBy($orderCol, $orderDir);
        $builder->limit($paging['length'], $paging['offset']);

        $rows = $builder->get()->getResultArray();
        $paging['remainingrecords'] = $totalRecords - ($paging['offset'] + count($rows));

        $this->setSuccess($this->successMessage);
        $this->setOutput(['paging' => $paging, 'questions' => $rows]);
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
        if (! $this->CheckUserTypePermissions('question:view')) {
            return $this->response();
        }

        $questionId = (int) $id;
        if ($questionId < 1) {
            $this->setError('Invalid question id.', 400);
            return $this->response();
        }

        $model = new QuestionsModel();
        $row   = $model->find($questionId);
        if (! $row) {
            $this->setError('Question not found.', 404);
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
        if (! $this->CheckUserTypePermissions('question:create')) {
            return $this->response();
        }

        $text            = $this->getPost('question_text', '');
        $type            = $this->getPost('question_type', '');
        $options         = $this->getPost('options', null);
        $expected        = $this->getPost('expected_answer', null);
        $conditionType   = $this->getPost('condition_type', null);
        $conditionValue  = $this->getPost('condition_value', null);
        $severity        = $this->getPost('severity', 'MEDIUM');
        $isMandatory     = (int) $this->getPost('is_mandatory', 1);
        $isPhotoMandatory= (int) $this->getPost('is_photo_mandatory', 0);
        $sequence        = (int) $this->getPost('sequence', 0);
        $isActive        = (int) $this->getPost('is_active', 1);

        if ($text === '' || $type === '') {
            $this->setError('question_text and question_type are required.', 400);
            return $this->response();
        }

        $allowedTypes = ['YES_NO', 'RATING', 'TEXT', 'NUMBER', 'MULTIPLE_CHOICE'];
        if (! in_array($type, $allowedTypes, true)) {
            $this->setError('Invalid question_type.', 400);
            return $this->response();
        }

        $allowedSeverity = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
        if (! in_array($severity, $allowedSeverity, true)) {
            $this->setError('Invalid severity.', 400);
            return $this->response();
        }

        $model = new QuestionsModel();
        $data  = [
            'question_text'       => $text,
            'question_type'       => $type,
            'options'             => $options,
            'expected_answer'     => $expected,
            'condition_type'      => $conditionType,
            'condition_value'     => $conditionValue,
            'severity'            => $severity,
            'is_mandatory'        => $isMandatory,
            'is_photo_mandatory'  => $isPhotoMandatory,
            'sequence'            => $sequence,
            'is_active'           => $isActive,
        ];

        $id = $model->insert($data, true);
        if (! $id) {
            $this->setError('Failed to create question.', 500);
            return $this->response();
        }

        $row = $model->find($id);
        $this->setSuccess('Question created successfully.');
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
        if (! $this->CheckUserTypePermissions('question:edit')) {
            return $this->response();
        }

        $questionId = (int) $id;
        if ($questionId < 1) {
            $this->setError('Invalid question id.', 400);
            return $this->response();
        }

        $model = new QuestionsModel();
        $row   = $model->find($questionId);
        if (! $row) {
            $this->setError('Question not found.', 404);
            return $this->response();
        }

        $text            = $this->getPost('question_text', $row['question_text'] ?? '');
        $type            = $this->getPost('question_type', $row['question_type'] ?? '');
        $options         = $this->getPost('options', $row['options'] ?? null);
        $expected        = $this->getPost('expected_answer', $row['expected_answer'] ?? null);
        $conditionType   = $this->getPost('condition_type', $row['condition_type'] ?? null);
        $conditionValue  = $this->getPost('condition_value', $row['condition_value'] ?? null);
        $severity        = $this->getPost('severity', $row['severity'] ?? 'MEDIUM');
        $isMandatory     = (int) $this->getPost('is_mandatory', $row['is_mandatory'] ?? 1);
        $isPhotoMandatory= (int) $this->getPost('is_photo_mandatory', $row['is_photo_mandatory'] ?? 0);
        $sequence        = (int) $this->getPost('sequence', $row['sequence'] ?? 0);
        $isActive        = (int) $this->getPost('is_active', $row['is_active'] ?? 1);

        if ($text === '' || $type === '') {
            $this->setError('question_text and question_type are required.', 400);
            return $this->response();
        }

        $allowedTypes = ['YES_NO', 'RATING', 'TEXT', 'NUMBER', 'MULTIPLE_CHOICE'];
        if (! in_array($type, $allowedTypes, true)) {
            $this->setError('Invalid question_type.', 400);
            return $this->response();
        }

        $allowedSeverity = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
        if (! in_array($severity, $allowedSeverity, true)) {
            $this->setError('Invalid severity.', 400);
            return $this->response();
        }

        $data  = [
            'question_text'       => $text,
            'question_type'       => $type,
            'options'             => $options,
            'expected_answer'     => $expected,
            'condition_type'      => $conditionType,
            'condition_value'     => $conditionValue,
            'severity'            => $severity,
            'is_mandatory'        => $isMandatory,
            'is_photo_mandatory'  => $isPhotoMandatory,
            'sequence'            => $sequence,
            'is_active'           => $isActive,
        ];

        $model->update($questionId, $data);
        $updated = $model->find($questionId);
        $this->setSuccess('Question updated successfully.');
        $this->setOutput($updated);
        return $this->response();
    }

    public function delete($id)
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
        if (! $this->CheckUserTypePermissions('question:delete')) {
            return $this->response();
        }

        $questionId = (int) $id;
        if ($questionId < 1) {
            $this->setError('Invalid question id.', 400);
            return $this->response();
        }

        $model = new QuestionsModel();
        $row   = $model->find($questionId);
        if (! $row) {
            $this->setError('Question not found.', 404);
            return $this->response();
        }

        $model->delete($questionId);
        $this->setSuccess('Question deleted successfully.');
        $this->setOutput([]);
        return $this->response();
    }
}

