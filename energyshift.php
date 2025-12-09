<?php
require 'vendor/autoload.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Connect to MongoDB
function connectDB() {
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/GCL?authSource=admin&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->GCL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1, 'meterId' => 1]);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Log the input for debugging
    file_put_contents('php://stderr', "Received POST data: " . print_r($input, true), FILE_APPEND);

    $startDate = $input['start_date'] ?? null;
    $endDate = $input['end_date'] ?? null;
    $meterIds = $input['meterIds'] ?? []; // Expecting an array of meter IDs
    $suffixes = isset($input['suffixes']) ? explode(',', $input['suffixes']) : [];
    $shifts = $input['shifts'] ?? []; // Shifts parameter

    if (!$startDate || !$endDate || empty($meterIds) || empty($shifts)) {
        echo json_encode(["error" => "Missing required parameters: start_date, end_date, meterIds, or shifts."]);
        exit;
    }

    // Format the dates for MongoDB
    $startDate = new DateTime($startDate);
    $endDate = new DateTime($endDate);
    $endDate->modify('+1 day'); // Include the entire end day

    $consumptionData = [];

    try {
        // Iterate through each date in the range
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($startDate, $interval, $endDate);

        foreach ($dateRange as $date) {
            $currentDate = $date->format('Y-m-d');
            $consumptionData[$currentDate] = []; // Initialize the date group

            foreach ($meterIds as $meterId) {
                $suffix = $suffixes[0]; // Assuming only one suffix for simplicity
                $consumptionData[$currentDate][$meterId] = []; // Initialize meter data

                foreach ($shifts as $shift) {
                    $startOfShift = new DateTime($currentDate . ' ' . $shift['startTime']);
                    $endOfShift = new DateTime($currentDate . ' ' . $shift['endTime']);
                
                    // Handle shifts that span midnight
                    if ($startOfShift > $endOfShift) {
                        // Split into two parts
                        $midnight = new DateTime($currentDate . ' 23:59:59');
                        $nextDay = $date->modify('+1 day')->format('Y-m-d');
                
                        // Part 1: Current day until midnight
                        $firstDocPart1 = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift->format(DateTime::ISO8601), '$lte' => $midnight->format(DateTime::ISO8601)]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => 1]
                            ]
                        );
                
                        $lastDocPart1 = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift->format(DateTime::ISO8601), '$lte' => $midnight->format(DateTime::ISO8601)]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => -1]
                            ]
                        );
                
                        // Part 2: Midnight to end time on the next day
                        $firstDocPart2 = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $nextDay . 'T00:00:00.000+05:00', '$lte' => $endOfShift->format(DateTime::ISO8601)]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => 1]
                            ]
                        );
                
                        $lastDocPart2 = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $nextDay . 'T00:00:00.000+05:00', '$lte' => $endOfShift->format(DateTime::ISO8601)]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => -1]
                            ]
                        );
                
                        // Calculate consumption for both parts
                        $startValuePart1 = $firstDocPart1["{$meterId}_{$suffix}"] ?? 0;
                        $endValuePart1 = $lastDocPart1["{$meterId}_{$suffix}"] ?? 0;
                        $consumptionPart1 = $endValuePart1 - $startValuePart1;
                
                        $startValuePart2 = $firstDocPart2["{$meterId}_{$suffix}"] ?? 0;
                        $endValuePart2 = $lastDocPart2["{$meterId}_{$suffix}"] ?? 0;
                        $consumptionPart2 = $endValuePart2 - $startValuePart2;
                
                        // Total consumption for the shift
                        $consumptionData[$currentDate][$meterId][$shift['name']] = $consumptionPart1 + $consumptionPart2;
                    } else {
                        // Handle regular shifts within the same day
                        $firstDoc = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift->format(DateTime::ISO8601), '$lte' => $endOfShift->format(DateTime::ISO8601)]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => 1]
                            ]
                        );
                
                        $lastDoc = $collection->findOne(
                            [
                                'timestamp' => ['$gte' => $startOfShift->format(DateTime::ISO8601), '$lte' => $endOfShift->format(DateTime::ISO8601)]
                            ],
                            [
                                'projection' => ["{$meterId}_{$suffix}" => 1, 'timestamp' => 1],
                                'sort' => ['timestamp' => -1]
                            ]
                        );
                
                        if ($firstDoc && $lastDoc) {
                            $startValue = $firstDoc["{$meterId}_{$suffix}"] ?? 0;
                            $endValue = $lastDoc["{$meterId}_{$suffix}"] ?? 0;
                            $consumption = $endValue - $startValue;
                
                            $consumptionData[$currentDate][$meterId][$shift['name']] = $consumption;
                        } else {
                            $consumptionData[$currentDate][$meterId][$shift['name']] = 0; // Default to 0 if no data
                        }
                    }
                }
                
            }
        }

        echo json_encode(array_values(array_map(function($date, $data) {
            return ['date' => $date] + $data;
        }, array_keys($consumptionData), $consumptionData))); // Convert to array format
    } catch (Exception $e) {
        echo json_encode(["error" => "Error fetching data: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["error" => "Invalid request method. POST expected."]);
}
?>
