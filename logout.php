<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
session_destroy();
header('Location: index.php');
exit;
