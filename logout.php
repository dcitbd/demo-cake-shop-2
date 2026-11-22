<?php
require_once __DIR__ . '/includes/functions.php';
log_audit('USER_LOGOUT', 'ইউজার লগআউট করেছেন');
clear_persistent_login();
session_destroy();
header('Location: index.php');
exit;
