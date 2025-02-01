<?php
// Allow requests from any origin (you can restrict this to specific domains)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");

// Handle OPTIONS requests (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit; // Exit early for OPTIONS requests
}

// Database connection
$con = mysqli_connect("15.206.128.214", "jahaann", "Jahaann#321", "gcl");
$response = array();

if ($con) {
    // Handling GET request to fetch data
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $sql = "SELECT * FROM meterdata ORDER BY id DESC"; // Fetch all data sorted by most recent
        $result = mysqli_query($con, $sql);

        if ($result) {
            $response = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $response[] = [
                    'id' => $row['id'],
                    'meter' => $row['Source'],
                    'option_selected' => $row['Status'],
                    'value' => $row['Value'],
                    'created_at' => $row['Time']
                ];
            }
            echo json_encode($response, JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error fetching data: " . mysqli_error($con)
            ]);
        }
    } 
    // Handling POST request for inserting data
    elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Extract data from POST request
        $meter = $data['meter'];
        $option_selected = $data['option_selected'];
        $value = $data['value'];
        $created_at = date('Y-m-d H:i:s'); // Add current timestamp for created_at

        // Insert a new row every time
        $sql = "INSERT INTO meterdata (Source, Status, Value, Time) 
                VALUES ('$meter', '$option_selected', '$value', '$created_at')";

        if (mysqli_query($con, $sql)) {
            echo json_encode([
                "success" => true,
                "message" => "Data saved successfully",
                "data" => [
                    "meter" => $meter,
                    "option_selected" => $option_selected,
                    "value" => $value,
                    "created_at" => $created_at
                ]
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error saving data: " . mysqli_error($con)
            ]);
        }
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "DB connection failed"
    ]);
}
?>
