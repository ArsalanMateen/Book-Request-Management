<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
session_unset();
session_destroy();

// Redirect everyone to user login
require_once __DIR__ . '/config/db.php';
app_redirect('user/login.php');
