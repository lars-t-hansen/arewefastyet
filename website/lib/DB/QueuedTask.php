<?php

require_once("DB.php");

class QueuedTask extends DB {

    public static $db = "control_task_queue";

    function __construct($id) {
        $this->id = $id;
    }

    function setStarted() {
        $this->updateRaw("start", "UNIX_TIMESTAMP()");
    }

    function setFinished() {
        $this->updateRaw("finish", "UNIX_TIMESTAMP()");
    }
    
    function reportError($error) {
        $this->setFinished();
        $this->updateString("error", empty($error) ? "unknown error" : $error);
    }

    function task() {
        return $this->select("task");
    }
}

