<?php

namespace local_securecoursehub\local;

defined('MOODLE_INTERNAL') || die();

class request_service {

#Create---------------------------------------------------------------------------------------------------------------
    public function create_request($courseid, $userid, $title, $description) {
        #Validation steps:
        $title = trim($title);
        $description = trim($description);

        if($title === '' || core_text::strlen($title) > 255){
            return false;
        }

        if($description === ''){
            return false;
        }


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
    public function delete_request($id,$userid) {
        global $DB;
        #find request using ID
        $request = $this->get_request($id);
        if(!$request){
            return false;
        }

        #Ownership
        if($request->userid != $userid){
            return false;
        }

        #Status
        if($request->status !== 'open'){
            return false;
        }

        return $DB->delete_records(
            'local_securecoursehub',
            ['id' => $id],
        );
    }

    #Update Student requests-----------------------------------------------------------------------------
        #TODO: ADD OWNERSHIP
    public function update_student_request($id,$userid, $title, $description) {
        global $DB;
        
        #Exists?
        $request = $this->get_request($id);
        if(!$request){
            return false;
        }

        #Ownership
        if($request->userid != $userid){
            return false;
        }

        #Status
        if($request->status !== 'open'){
            return false;
        }

        #Validate
        $title = trim($title);
        $description = trim($description);

        if($title === '' || core_text::strlen($title) > 255){
            return false;
        }
        
        if($description === ''){
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

        #Need to check statuses
        $allowed = [
            'open',
            'inprogress',
            'resolved'
        ];

        if(!in_array($status, $allowed)){
            return false;
        }

        #Response validation
        $response = trim($response);
        if(core_text::strlen($response) > 500) {
            return false;
        }
        
        
        $request = $this->get_request($id);
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