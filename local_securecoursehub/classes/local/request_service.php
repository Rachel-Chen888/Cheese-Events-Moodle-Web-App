<?php

namespace local_securecoursehub\local;

defined('MOODLE_INTERNAL') || die();

class request_service {

#Create---------------------------------------------------------------------------------------------------------------
    public function create_request($courseid, $userid, $title, $description) {
        global $DB;

        #Creates new object
        $request = new \stdClass();

        #Fills the new object
        $request->courseid = $courseid;
        $request->userid = $userid;
        $request->title = $title;
        $request->description = $description;
        $request->status = "open";
        $request->response = "";
        $request->timecreated = time();
        $request->timemodified = time();

        #Return statement
        return $DB->insert_record('local_securecoursehub', $request);
    }

    #Student requests---------------------------------------------------------------------------------------------------------------
    public function get_student_requests($userid) {
        global $DB;

        return $DB->get_records(
            'local_securecoursehub',
            ['userid' => $userid],
            'timecreated DESC'
        );
    }

    #Teacher requests-------------------------------------------------------------------------------------------
    public function get_course_requests($courseid) {
        global $DB;

        return $DB->get_records(
            'local_securecoursehub',
            ['courseid' => $courseid],
            'timecreated DESC'
        );
    }

    #Admin Requests (Assuming they can do everything the students AND teachers can do)-----------------------------------------------
    public function get_all_requests() {
        global $DB;

        return $DB->get_records(
            'local_securecoursehub',
            null,
            'timecreated DESC'
        );
    }

    public function get_request($id) {
        global $DB;

        return $DB->get_record(
            'local_securecoursehub',
            ['id' => $id]
        );
    }

    #Delete -------------------------------------------------------------------------------------------
    public function delete_request($id) {
        global $DB;

        return $DB->delete_records(
            'local_securecoursehub',
            ['id' => $id]
        );
    }

    #Update Student requests-----------------------------------------------------------------------------
        #TODO: ADD OWNERSHIP
    public function update_student_request($id, $title, $description) {
        global $DB;

        $request = $DB->get_record(
            'local_securecoursehub',
            ['id' => $id]
        );

        if (!$request) {
            return false;
        }

        $request->title = $title;
        $request->description = $description;
        $request->timemodified = time();

        return $DB->update_record(
            'local_securecoursehub',
            $request
        );
    }

    #Teacher Update-----------------------------------------------------------------------------------------------
    public function update_teacher_request($id, $status, $response) {
        global $DB;

        $request = $DB->get_record(
            'local_securecoursehub',
            ['id' => $id]
        );

        if (!$request) {
            return false;
        }

        $request->status = $status;
        $request->response = $response;
        $request->timemodified = time();

        return $DB->update_record(
            'local_securecoursehub',
            $request
        );
    }
}

#TODO: ownership checks (students can only edit/delete their own requests), capability checks, validation, and allowed status transitions