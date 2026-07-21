<?php
// local_securecoursehub/index.php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/securecoursehub/classes/local/request_service.php');

// 1. Moodle Page Setup & Authentication
$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);

require_login($course);
$context = context_course::instance($courseid);
require_capability('local/securecoursehub:viewown', $context);

$PAGE->set_url(new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_securecoursehub'));
$PAGE->set_heading(format_string($course->fullname));

// 2. Handle Form Submission (Create Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('action', '', PARAM_ALPHANUMEXT) === 'create') {
    // CSRF Protection: Validate the sesskey
    require_sesskey(); 
    require_capability('local/securecoursehub:createrequest', $context);

    // Input Validation: Get parameters safely
    $title = required_param('title', PARAM_TEXT);
    $description = required_param('description', PARAM_TEXT);

    \local_securecoursehub\local\request_service::create_request($courseid, $USER->id, $title, $description);
    
    // Redirect to prevent duplicate form submissions on refresh
    redirect(new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]), 'Request created successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
}

// 3. Check Teacher Capabilities
$isteacher = has_capability('local/securecoursehub:managecourserequests', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_securecoursehub'));

// 4. Render the Creation Form
if (has_capability('local/securecoursehub:createrequest', $context)) {
    echo $OUTPUT->box_start('generalbox mb-4');
    echo '<h3>Create a New Help Request</h3>';
    echo '<form method="post" action="index.php?courseid=' . $courseid . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<input type="hidden" name="action" value="create">';
    echo '<div class="form-group"><label>Title:</label><br><input type="text" name="title" class="form-control" required></div>';
    echo '<div class="form-group"><label>Description:</label><br><textarea name="description" class="form-control" required></textarea></div>';
    echo '<button type="submit" class="btn btn-primary mt-2">Submit Request</button>';
    echo '</form>';
    echo $OUTPUT->box_end();
}

// 5. Fetch and Display Records
// Enforce Ownership: Teachers get all, students get only their own
$requests = $isteacher 
    ? \local_securecoursehub\local\request_service::get_requests($courseid) 
    : \local_securecoursehub\local\request_service::get_requests($courseid, $USER->id);

echo '<h3>' . ($isteacher ? 'Course Requests' : 'Your Requests') . '</h3>';

if (empty($requests)) {
    echo html_writer::tag('p', 'No requests found.');
} else {
    echo '<table class="generaltable w-100">';
    echo '<thead><tr><th>ID</th><th>Title</th><th>Status</th><th>Date</th>';
    if ($isteacher) { echo '<th>User ID</th><th>Actions</th>'; }
    echo '</tr></thead><tbody>';
    
    foreach ($requests as $req) {
        echo '<tr>';
        echo '<td>' . (int)$req->id . '</td>';
        // XSS Prevention: Use s() to safely escape untrusted output
        echo '<td>' . s($req->title) . '</td>'; 
        echo '<td><span class="badge badge-info">' . s($req->status) . '</span></td>';
        echo '<td>' . userdate($req->timecreated) . '</td>';
        
        if ($isteacher) {
            echo '<td>' . (int)$req->userid . '</td>';
            // Placeholder button for the AJAX dynamic update
            echo '<td><button class="btn btn-sm btn-secondary update-status-btn" data-id="' . (int)$req->id . '">Mark Resolved</button></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}

echo $OUTPUT->footer();