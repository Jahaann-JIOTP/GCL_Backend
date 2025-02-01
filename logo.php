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
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Decode the incoming JSON data
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Log incoming data for debugging
        error_log(print_r($data, true));

        // Ensure data is received correctly
        if (isset($data['Source']) && isset($data['Status']) && isset($data['value']) && isset($data['Time'])) {
            $Source = $data['Source'];
            $Status = $data['Status'];
            $value = $data['value'];
            $Time = $data['Time'];  // Assuming the time is in UTC format (e.g., '2024-12-16T10:00:00Z')
            
            // Convert the 'Time' (UTC) to a DateTime object
            $datetime = new DateTime($Time, new DateTimeZone('UTC'));
            $datetime->setTimezone(new DateTimeZone('Asia/Karachi'));  // Convert to local timezone (Asia/Karachi)
            $created_at_local = $datetime->format('Y-m-d H:i:s');  // Format the datetime

            // Check if a record with the same 'Source' and 'Status' exists
            $check_sql = "SELECT * FROM meterdata WHERE Source = '$Source' AND Status = '$Status'";
            $check_result = mysqli_query($con, $check_sql);

            if (mysqli_num_rows($check_result) > 0) {
                // If a row exists, update the 'Value' and 'Time'
                $update_sql = "UPDATE meterdata SET Value = '$value', Time = '$created_at_local' 
                               WHERE Source = '$Source' AND Status = '$Status'";
                if (mysqli_query($con, $update_sql)) {
                    echo json_encode([
                        "success" => true,
                        "message" => "Data updated successfully"
                    ]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Error updating data: " . mysqli_error($con)
                    ]);
                }
            } else {
                // If no row exists, insert a new one
                $insert_sql = "INSERT INTO meterdata (Source, Status, Value, Time) 
                               VALUES ('$Source', '$Status', '$value', '$created_at_local')";
                if (mysqli_query($con, $insert_sql)) {
                    echo json_encode([
                        "success" => true,
                        "message" => "Data saved successfully"
                    ]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Error saving data: " . mysqli_error($con)
                    ]);
                }
            }
        } else {
            // If any parameter is missing, log the error
            error_log("Missing required parameters in POST data");
            echo json_encode([
                "success" => false,
                "message" => "Missing required parameters"
            ]);
        }
    } else {
        // If the request is GET, fetch all records from the database
        $sql = "SELECT * FROM meterdata";
        $result = mysqli_query($con, $sql);
        if ($result) {
            header("Content-Type: application/json");
            $i = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $response[$i]['id'] = $row['id'];
                $response[$i]['meter'] = $row['Source'];
                $response[$i]['option_selected'] = $row['Status'];
                $response[$i]['value'] = $row['Value'];
                $response[$i]['created_at'] = $row['Time'];
                $i++;
            }
            echo json_encode($response, JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error fetching data: " . mysqli_error($con)
            ]);
        }
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
}
?>
