<?php
namespace local_bcn_historic_mdc\task;

class cron_task extends \core\task\scheduled_task
{
    public function get_name() {
        return get_string('pluginname', 'local_bcn_historic_mdc');
    }

    public function execute() {
        mtrace($this->get_name());
    }
}
