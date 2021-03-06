<?php

class Task {

    // The build function contains the 'source' it needs to fetch
    // the code from. We need to know which source is which engine.
    // This makes the transition from source to engine. 
    // Note: an engine can have multiple sources.
    public function source_rules() {
        return Array(
            "mozilla" => "firefox",
            "mozilla-inbound" => "firefox",
            "v8" => "chrome",
            "chrome" => "chrome",
            "webkit" => "webkit"
        );
    }

    // The execute function looks at engine+config to decide which
    // mode it should send this data to. These contain the default rules.
    // Though it is possible to add some extra rules in the task
    // itself. These are not accounted for (TODO).
    public function mode_rules() {
        return Array(
            "firefox,default" => "jmim",
            "firefox,noasmjs" => "noasmjs",
            "firefox,unboxedobjects" => "unboxedobjects",
            "firefox,testbedregalloc" => "testbed",
            "chrome,default" => "v8",
            "chrome,turbofan" => "v8-turbofan",
            "webkit,default" => "jsc",
            "native,default" => "clang",
            "servo,default" => "servo"
        );
    }

    public function __construct($task, $available_at = 0) {
        $this->task = $task;
		$this->available_at = $available_at;
    }

    public function task() {
        return $this->task;
    }

    public function available_at() {
		return $this->available_at;
	}

    public function configs() {
        $configs = Array();
        $commands = BashInterpreter::matchCommand($this->task, "python execute.py");
        foreach ($commands as $command) {
            $config_matches = BashInterpreter::matchFlag($command, "-c");
            foreach ($config_matches as $match) {
                $configs[] = $match;
            }
        }
        return array_unique($configs);
    }

    public function engines() {
        $engines = Array();

        // Fetch all engines that have been build.
        $commands = BashInterpreter::matchCommand($this->task, "python build.py");
        foreach ($commands as $command) {
            $source_matches = BashInterpreter::matchFlag($command, "-s");
            if (count($source_matches) != 1)
                throw new Error("Expected one match.");

			$source_rules = $this->source_rules();
            $engines[] = $source_rules[$source_matches[0]];
        }

        // Fetch all engines that have been downloaded.
        $commands = BashInterpreter::matchCommand($this->task, "python download.py");
        foreach ($commands as $command) {
            $source_matches = BashInterpreter::matchFlag($command, "--repo");
            if (count($source_matches) != 1)
                throw new Error("Expected one match.");

			$source_rules = $this->source_rules();
            $engines[] = $source_rules[$source_matches[0]];
        }

        return array_unique($engines);
    }

    public function modes() {
        $configs = $this->configs();
        $engines = $this->engines();
        $mode_rules = $this->mode_rules();

        $modes = Array();
        foreach ($configs as $config) {
            foreach ($engines as $engine) {
                $rule = $engine.",".$config;
                if (isset($mode_rules[$rule])) {
                    $modes[] = $mode_rules[$rule];
                }
            }
        }
        return $modes;
    }

    public function benchmarks() {
        $configs = Array();
        $commands = BashInterpreter::matchCommand($this->task, "python execute.py");
        foreach ($commands as $command) {
            $config_matches = BashInterpreter::matchFlag($command, "-b");
            foreach ($config_matches as $match) {
                $configs[] = $match;
            }
        }
        return array_unique($configs);
    }
}
