<?php


require_once 'config.php';
require_once 'Auth.php';

$auth = new Auth();
$auth->logout();

//when logged out it sends you to index
redirect('index.php');
?>