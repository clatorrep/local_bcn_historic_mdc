<?php
namespace local_bcn_historic_mdc\task;

use context_system;

class cron_task extends \core\task\scheduled_task
{
    public function get_name() {
        return get_string('pluginname', 'local_bcn_historic_mdc');
    }

    public function execute() {
        // Test funcional
        global $DB;

        $table = 'local_bcn_historic_mdc';

        $data = (object) array(
            'userid' => 2,
            'courseid' => 23,
            'coursestart'=> time(),
            'progress' => '2 de 2',
            'progressperc' => 100.0,
            'progressquiz' => '1 de 1',
            'finalgrade' => 100.0,
            'quizdate' => time(),
            'status' => 'Aprobado',
            'timecreated' => time()
        );

        foreach ($data as $key => $value) {
            mtrace("$key => $value \n");
        }

        $DB->insert_record($table, $data);
    }
}
