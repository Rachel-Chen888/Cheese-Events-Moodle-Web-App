<?php
// local_securecoursehub/ajax.php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/securecoursehub/classes/local/request_service.php');

use local_securecoursehub\local\request_service;

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Authenticate user session
    require_login();

    // 2. Decode raw JSON payload from fetch()
    $rawinput = file_get_contents('php://input');
    $data = json_decode($rawinput, true);

    if (!$data) {
        throw new invalid_parameter_exception('Invalid JSON body');
    }

    // 3. CSRF Protection (sesskey check)
    $sesskey = $data['sesskey'] ?? '';
    if (!confirm_sesskey($sesskey)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid session key (CSRF protection)']);
        exit;
    }

    $action = $data['action'] ?? '';
    $id = (int)($data['id'] ?? 0);
    $service = new request_service();

    // Fetch original record to identify course and ownership
    $request = $service->get_request($id);
    if (!$request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Request not found']);
        exit;
    }

    $context = context_course::instance($request->courseid);

    // 4. Action Processing
    if ($action === 'update_teacher_request') {
        // Enforce Teacher Management Capability
        require_capability('local/securecoursehub:managecourserequests', $context);

        // Sanitize and validate inputs
        $status = clean_param($data['status'] ?? '', PARAM_ALPHA);
        $response = clean_param($data['response'] ?? '', PARAM_TEXT);

        // Validate allowed statuses
        $allowedstatuses = ['open', 'inprogress', 'resolved'];
        if (!in_array($status, $allowedstatuses, true)) {
            throw new invalid_parameter_exception('Invalid status value');
        }

        // Validate max response length (max 500 chars)
        if (core_text::strlen($response) > 500) {
            throw new invalid_parameter_exception('Response text exceeds 500 character limit.');
        }

        // Perform Database Update
        $success = $service->update_teacher_request($id, $status, $response);

        if ($success) {
            echo json_encode([
                'success' => true,
                'id' => $id,
                'status' => $status,
                'response' => s($response),
                'timemodified' => userdate(time())
            ]);
        } else {
            throw new moodle_exception('updatefailed', 'local_securecoursehub');
        }

    } else if ($action === 'delete_student_request') {
        // Enforce Student Ownership
        if ((int)$request->userid !== (int)$USER->id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ownership check failed: You can only delete your own requests.']);
            exit;
        }

        // Students can only delete OPEN requests
        if ($request->status !== 'open') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Cannot delete requests that are in-progress or resolved.']);
            exit;
        }

        $success = $service->delete_request($id);
        echo json_encode(['success' => $success, 'id' => $id]);

    } else {
        throw new invalid_parameter_exception('Unknown action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}