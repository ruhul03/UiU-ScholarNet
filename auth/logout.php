<?php
require_once('../includes/session.php');
start_secure_session();
session_destroy();
header("Location: login.php");
exit();
