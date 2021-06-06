<?php

include 'DatabaseWrapper.php';

$db = new DatabaseWrapper();

var_dump($db->testConnection());