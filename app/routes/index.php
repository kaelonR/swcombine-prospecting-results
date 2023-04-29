<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'init.php';
runRoute(\SWCPR\Controllers\HomeController::class, 'index');