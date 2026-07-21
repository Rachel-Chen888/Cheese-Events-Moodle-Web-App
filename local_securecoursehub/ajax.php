<?php
// local_securecoursehub/ajax.php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/securecoursehub/classes/local/request_service.php');

// 1. Verify authentication
require_login();

// 2. Decode JSON body
$input = json_decode(file_get_contents('php://input'), true);

// 3. Validate sesskey to prevent CSRF attacks
if (!$input || !confirm_sesskey($input['sesskey'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid session key.']);
    exit;
}

$action = $input['action'] ?? '';
$id = (int)($input['id'] ?? 0);
$service = new \local_securecoursehub\local\request_service();

try {
    if ($action === 'update_teacher_request') {
        // Retrieve the record to establish the correct course context
        $requestrecord = $service->get_request($id);
        if (!$requestrecord) {
            throw new moodle_exception('Request not found.');
        }
        
        $context = context_course::instance($requestrecord->courseid);
        
        // 4. Verify authorization capability
        require_capability('local/securecoursehub:managecourserequests', $context);
        
        // 5. Clean untrusted input
        $status = clean_param($input['status'], PARAM_ALPHA);
        $resp_text = clean_param($input['response'], PARAM_TEXT);
        
        // 6. Update the database
        $service->update_teacher_request($id, $status, $resp_text);
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    }
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}