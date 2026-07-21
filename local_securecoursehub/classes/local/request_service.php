<?php
namespace local_securecoursehub\local;

defined('MOODLE_INTERNAL') || die();

class request_service {

    /**
     * Get requests for a specific course. 
     * If a user ID is provided, filter to only show their requests.
     */
    public static function get_requests($courseid, $userid = null) {
        global $DB;
        
        $conditions = ['courseid' => $courseid];
        if ($userid !== null) {
            $conditions['userid'] = $userid;
        }
        
        // Use Moodle's Database API to safely fetch records
        return $DB->get_records('local_securecoursehub', $conditions, 'timecreated DESC');
    }

    /**
     * Create a new help request.
     */
    public static function create_request($courseid, $userid, $title, $description) {
        global $DB;
        
        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->userid = $userid;
        $record->title = $title;
        $record->description = $description;
        $record->status = 'open';
        $record->timecreated = time();
        $record->timemodified = time();
        
        return $DB->insert_record('local_securecoursehub', $record);
    }

    /**
     * Update the status of an existing request (Teacher operation).
     */
    public static function update_status($id, $newstatus, $response = '') {
        global $DB;
        
        // Verify the record exists first
        $record = $DB->get_record('local_securecoursehub', ['id' => $id], '*', MUST_EXIST);
        
        $record->status = $newstatus;
        $record->response = $response;
        $record->timemodified = time();
        
        return $DB->update_record('local_securecoursehub', $record);
    }

    /**
     * Delete an open request (Student operation).
     */
    public static function delete_request($id, $userid) {
        global $DB;
        
        // Ensure the record exists, belongs to the user, and is still open
        $record = $DB->get_record('local_securecoursehub', ['id' => $id, 'userid' => $userid]);
        
        if ($record && $record->status === 'open') {
            return $DB->delete_records('local_securecoursehub', ['id' => $id]);
        }
        
        return false;
    }
}