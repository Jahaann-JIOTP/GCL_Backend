<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$url = "http://13.234.241.103:1880/latestgcl1";

$response = file_get_contents($url);

if ($response !== FALSE) {
    echo $response;
} else {
    echo json_encode(["error" => "Unable to fetch data"]);
}
?>
