<?php
namespace local_bcn_historic_mdc\task;

use context_system;
use stdClass;

require_once($CFG->dirroot . '/local/bcn_historic_mdc/lib.php');

class cron_task extends \core\task\scheduled_task
{
    public function get_name() {
        return get_string('pluginname', 'local_bcn_historic_mdc');
    }

    public function execute() {
        global $DB;

        $mdccourseids = array(24,25,26);
           
        $datareport = get_report_records($mdccourseids);
        
        $countrecords = save_report_records($datareport);

        mtrace($countrecords);

        foreach ($mdccourseids as $mdccourseid) {
            $data = new stdClass();
            $data->reset_start_date = 0;
            $data->reset_end_date = 0;
            $data->reset_events = "1";
            $data->reset_notes = "1";
            $data->reset_comments = "1";
            $data->reset_completion = "1";
            $data->delete_blog_associations = "1";
            $data->reset_competency_ratings = "1";
            $data->unenrol_users = array();
            $data->reset_roles_local = "1";
            $data->reset_gradebook_items = "1";
            $data->reset_groups_remove = "1";
            $data->reset_groupings_remove = "1";
            $data->reset_customcert = "1";
            $data->reset_quiz_attempts = "1";
            $data->reset_quiz_user_overrides = "1";
            $data->reset_quiz_group_overrides = "1";
            $data->reset_scorm = "1";
            $data->id = $mdccourseid;
            $data->submitbutton = "Reiniciar curso";
            $data->reset_end_date_old = "0";
            
            $transaction = $DB->start_delegated_transaction();
    
            try {
                reset_course_userdata($data);
                $transaction->allow_commit();
            } catch (\Throwable $th) {
                $transaction->rollback(new moodle_exception($th->getMessage()));
                // return array('status' => false, 'message' => $th->getMessage());
            }
        }

    }
}
