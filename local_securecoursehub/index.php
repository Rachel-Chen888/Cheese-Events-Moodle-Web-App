<?php
// local_securecoursehub/index.php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/securecoursehub/classes/local/request_service.php');

use local_securecoursehub\local\request_service;

// 1. Context & Authentication Checks
$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);

require_login($course);
$context = context_course::instance($courseid);
require_capability('local/securecoursehub:viewown', $context);

$PAGE->set_url(new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_securecoursehub'));
$PAGE->set_heading(format_string($course->fullname));

// Instantiate your peer's service class
$service = new request_service();

// 2. Handle Form Submission (Create Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('action', '', PARAM_ALPHANUMEXT) === 'create') {
    require_sesskey(); // CSRF protection
    require_capability('local/securecoursehub:createrequest', $context);

    $title = required_param('title', PARAM_TEXT);
    $description = required_param('description', PARAM_TEXT);

    $service->create_request($courseid, $USER->id, $title, $description);
    
    redirect(
        new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]),
        'Request created successfully!',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// 3. Capability Checks
$isteacher = has_capability('local/securecoursehub:managecourserequests', $context);
$cancreate = has_capability('local/securecoursehub:createrequest', $context);

$PAGE->requires->js_call_amd('local_securecoursehub/dashboard', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_securecoursehub'));

// 4. Render the Creation Form
// Debug: Print capabilities to screen to see why the form might be hidden
echo "<p>Debug Info: ViewOwn: " . (has_capability('local/securecoursehub:viewown', $context) ? 'Yes' : 'No') . "</p>";
echo "<p>Debug Info: CreateRequest: " . (has_capability('local/securecoursehub:createrequest', $context) ? 'Yes' : 'No') . "</p>";

if (has_capability('local/securecoursehub:createrequest', $context)) {
    echo $OUTPUT->box_start('generalbox mb-4');
    echo '<h3>Create a New Help Request</h3>';
    echo '<form method="post" action="index.php?courseid=' . $courseid . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<input type="hidden" name="action" value="create">';
    echo '<div class="form-group"><label for="req_title">Title:</label><input type="text" id="req_title" name="title" class="form-control" required></div>';
    echo '<div class="form-group"><label for="req_desc">Description:</label><textarea id="req_desc" name="description" class="form-control" required></textarea></div>';
    echo '<button type="submit" class="btn btn-primary mt-2">Submit Request</button>';
    echo '</form>';
    echo $OUTPUT->box_end();
} else {
    echo '<p style="color: red;">You do not have the capability to create requests.</p>';
}


// 5. Fetch Records using peer's service methods
if ($isteacher) {
    $requests = $service->get_course_requests($courseid);
} else {
    $requests = $service->get_student_requests($USER->id);
}

echo '<h3>' . ($isteacher ? 'Course Requests' : 'Your Requests') . '</h3>';

if (empty($requests)) {
    echo html_writer::tag('p', 'No requests found.');
} else {
    echo '<table class="generaltable w-100">';
    echo '<thead><tr><th>ID</th><th>Title</th><th>Description</th><th>Status</th><th>Response</th><th>Date</th>';
    if ($isteacher) {
        echo '<th>User ID</th><th>Actions</th>';
    }
    echo '</tr></thead><tbody>';
    
    foreach ($requests as $req) {
        echo '<tr>';
        echo '<td>' . (int)$req->id . '</td>';
        echo '<td>' . s($req->title) . '</td>';
        echo '<td>' . s($req->description) . '</td>';
        echo '<td><span class="badge badge-info" id="status-' . (int)$req->id . '">' . s($req->status) . '</span></td>';
        echo '<td id="response-' . (int)$req->id . '">' . s($req->response ?? '') . '</td>';
        echo '<td>' . userdate($req->timecreated) . '</td>';
        
        if ($isteacher) {
            echo '<td>' . (int)$req->userid . '</td>';
            echo '<td><button class="btn btn-sm btn-secondary update-status-btn" data-id="' . (int)$req->id . '">Update Status</button></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

echo $OUTPUT->footer();