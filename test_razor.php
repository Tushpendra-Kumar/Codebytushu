<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [];
file_put_contents('php://input', json_encode(['amount' => 150]));
require 'e:/Codebytushu/api/create_order.php';
