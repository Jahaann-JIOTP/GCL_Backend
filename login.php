<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

$host = "15.206.128.214";
$dbname = "gcl";
$dbuser = "jahaann";
$dbpassword = "Jahaann#321";

$conn = new mysqli($host, $dbuser, $dbpassword, $dbname);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['email']) || !isset($input['password'])) {
    echo json_encode(["status" => "error", "message" => "Email and password are required."]);
    exit();
}

$email = $conn->real_escape_string($input['email']);
$password = $input['password'];

$sql = "SELECT id, password FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "SQL prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if ($user['password'] === $password) { // Direct comparison for plain text
        echo json_encode(["status" => "success", "message" => "Login successful.", "user_id" => $user['id']]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid email or password1."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid email or password2."]);
}

$stmt->close();
$conn->close();
?>
