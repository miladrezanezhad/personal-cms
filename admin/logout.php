<?php
require_once '../config.php';
require_once BASE_PATH . '/includes/functions.php';  // این خط را اضافه کنید

Auth::logout();
header('Location: login.php');
exit;