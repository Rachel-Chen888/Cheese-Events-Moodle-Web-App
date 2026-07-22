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

// Load Javascript AMD module
$PAGE->requires->js_call_amd('local_securecoursehub/dashboard', 'init');

$service = new request_service();

// 2. Handle Synchronous Form Submission (Create Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('action', '', PARAM_ALPHANUMEXT) === 'create') {
    require_sesskey();
    require_capability('local/securecoursehub:createrequest', $context);

    $title = required_param('title', PARAM_TEXT);
    $description = required_param('description', PARAM_TEXT);

    $result = $service->create_request($courseid, $USER->id, $title, $description);

    //DOUBLE CHECK THIS
    if(!$result){
        throw new moodle_exception(
            'invalidrequest',
            'local_coursehub',
            //TODO: POSSIBLY PRESENT ERROR MESSAGE
        )
    }

    redirect(
        new moodle_url('/local/securecoursehub/index.php', ['courseid' => $courseid]),
        'Help request submitted successfully!',
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
    //TODO: Program error messages for invalid submissions.
}

// 3. Capability Flags
$isteacher = has_capability('local/securecoursehub:managecourserequests', $context);
$cancreate = has_capability('local/securecoursehub:createrequest', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_securecoursehub'));

// 4. Student Form
if ($cancreate && !$isteacher) {
    echo $OUTPUT->box_start('generalbox mb-4 p-3 border rounded');
    echo '<h3>Create a New Help Request</h3>';
    echo '<form method="post" action="index.php?courseid=' . $courseid . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<input type="hidden" name="action" value="create">';
    echo '<div class="form-group mb-2"><label for="title_in"><strong>Title:</strong></label><input type="text" id="title_in" name="title" class="form-control" maxlength="255" required></div>';
    echo '<div class="form-group mb-2"><label for="desc_in"><strong>Description:</strong></label><textarea id="desc_in" name="description" class="form-control" rows="3" required></textarea></div>';
    echo '<button type="submit" class="btn btn-primary">Submit Request</button>';
    echo '</form>';
    echo $OUTPUT->box_end();
}

// 5. Fetch & Display Request Table
$requests = $isteacher 
    ? $service->get_course_requests($courseid) 
    : $service->get_student_requests($USER->id);

echo '<h3 class="mt-4">' . ($isteacher ? 'Course Requests Queue' : 'Your Submitted Requests') . '</h3>';

if (empty($requests)) {
    echo html_writer::tag('p', 'No help requests found.', ['class' => 'alert alert-info']);
} else {
    echo '<table class="generaltable w-100 table-striped table-bordered mt-2">';
    echo '<thead><tr>';
    echo '<th>ID</th><th>Title</th><th>Description</th><th>Status</th><th>Response</th><th>Submitted Date</th>';
    if ($isteacher) {
        echo '<th>Student ID</th><th>Manage</th>';
    } else {
        echo '<th>Actions</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($requests as $req) {
        $statusclass = ($req->status === 'resolved') ? 'badge-resolved' : (($req->status === 'inprogress') ? 'badge-inprogress' : 'badge-open');
        
        echo '<tr id="request-row-' . (int)$req->id . '">';
        echo '<td>' . (int)$req->id . '</td>';
        echo '<td>' . s($req->title) . '</td>';
        echo '<td>' . nl2br(s($req->description)) . '</td>';
        echo '<td><span class="badge ' . $statusclass . '" id="badge-status-' . (int)$req->id . '">' . s($req->status) . '</span></td>';
        echo '<td id="response-cell-' . (int)$req->id . '">' . s($req->response ?? '-') . '</td>';
        echo '<td>' . userdate($req->timecreated) . '</td>';

        if ($isteacher) {
            echo '<td>' . (int)$req->userid . '</td>';
            echo '<td style="min-width:220px;">';
            echo '<select id="status-select-' . (int)$req->id . '" class="form-control form-control-sm mb-1">';
            echo '<option value="open"' . ($req->status === 'open' ? ' selected' : '') . '>open</option>';
            echo '<option value="inprogress"' . ($req->status === 'inprogress' ? ' selected' : '') . '>inprogress</option>';
            echo '<option value="resolved"' . ($req->status === 'resolved' ? ' selected' : '') . '>resolved</option>';
            echo '</select>';
            echo '<textarea id="response-input-' . (int)$req->id . '" class="form-control form-control-sm sch-response-input mb-1" placeholder="Type response (max 500 chars)..." maxlength="500">' . s($req->response ?? '') . '</textarea>';
            echo '<button class="btn btn-sm btn-success sch-update-btn w-100" data-id="' . (int)$req->id . '">Save Updates</button>';
            echo '<div id="feedback-' . (int)$req->id . '"></div>';
            echo '</td>';
        } else {
            echo '<td>';
            if ($req->status === 'open') {
                echo '<button class="btn btn-sm btn-danger sch-delete-btn" data-id="' . (int)$req->id . '">Delete</button>';
            } else {
                echo '<small class="text-muted">Locked</small>';
            }
            echo '</td>';
        }

        echo '</tr>';
    }
    echo '</tbody></table>';
}

echo $OUTPUT->footer();