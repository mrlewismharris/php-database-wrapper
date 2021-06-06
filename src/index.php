<?php

include 'DatabaseWrapper.php';

$db = new DatabaseWrapper\DatabaseWrapper();

var_dump($db->testConnection());