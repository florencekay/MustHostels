<?php
require_once '../includes/config.php';
requireLogin('admin');
$db = getDB();
$pageTitle = 'All Students';
require_once '../operator/students.php';
