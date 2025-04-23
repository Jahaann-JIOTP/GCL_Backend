<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://admin:cisco123@13.234.241.103:27017/?authSource=iotdb&readPreference=primary&appname=MongoDB%20Compass&ssl=false");
        return $client->iotdb;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1]);

error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('Asia/Karachi');

$startDate = $_GET['start_date'];
$endDate = $_GET['end_date'];
$dateRangeLabel = $_GET['label'];

// $tariff = $_GET['tariff_cost'];


$Tag1 = 'G2_U20_ACTIVE_POWER_TOTAL_KW';
$Tag2 = 'U_27_ACTIVE_POWER_TOTAL_KW';
$Tag3 = 'U_24_ACTIVE_POWER_TOTAL_KW';
$Tag4 = 'U_25_ACTIVE_POWER_TOTAL_KW';
$Tag5 ='G1_U16_ACTIVE_POWER_TOTAL_KW';
$Tag6 ='G1_U17_ACTIVE_POWER_TOTAL_KW';
$Tag7 ='G1_U18_ACTIVE_POWER_TOTAL_KW';
$Tag8 ='G1_U19_ACTIVE_POWER_TOTAL_KW';


// Convert these dates to MongoDB UTCDateTime
$startTimestamp = strtotime($startDate . 'T00:00:00+05:00');
$endTimestamp = strtotime($endDate . 'T23:59:59+05:00');

// Initialize arrays to store the values
$hourlyData = [];

// Create DateTime objects for start and end dates
$startDateTime = new DateTime($startDate . ' 00:00:00');
$endDateTime = new DateTime($endDate . ' 23:59:59');

// Define aggregation pipeline
$pipeline = [
    [
        '$match' => [
            'PLC_DATE_TIME' => [
                '$gte' => 'DT#' . str_replace(' ', '-', $startDateTime->format('Y-m-d H:i:s')),
                '$lte' => 'DT#' . str_replace(' ', '-', $endDateTime->format('Y-m-d H:i:s'))
            ]
        ]
    ],
    [
        '$project' => [
            'G2_U20_ACTIVE_POWER_TOTAL_KW' => 1,
            'U_27_ACTIVE_POWER_TOTAL_KW' => 1,
            'U_24_ACTIVE_POWER_TOTAL_KW' => 1,
            'U_25_ACTIVE_POWER_TOTAL_KW' => ['$abs' => '$U_25_ACTIVE_POWER_TOTAL_KW'],
            'G1_U16_ACTIVE_POWER_TOTAL_KW' => 1,
            'G1_U17_ACTIVE_POWER_TOTAL_KW' => 1,
            'G1_U18_ACTIVE_POWER_TOTAL_KW' => 1,
            'G1_U19_ACTIVE_POWER_TOTAL_KW' => 1,
            
            // 'U_25_ACTIVE_POWER_TOTAL_KW' => 1,
            'PLC_DATE_TIME' => 1,
            'year' => ['$year' => ['$dateFromString' => ['dateString' => ['$substr' => ['$PLC_DATE_TIME', 3, 19]]]]],
            'month' => ['$month' => ['$dateFromString' => ['dateString' => ['$substr' => ['$PLC_DATE_TIME', 3, 19]]]]],
            'day' => ['$dayOfMonth' => ['$dateFromString' => ['dateString' => ['$substr' => ['$PLC_DATE_TIME', 3, 19]]]]],
            'hour' => ['$hour' => ['$dateFromString' => ['dateString' => ['$substr' => ['$PLC_DATE_TIME', 3, 19]]]]],
            'minute' => ['$minute' => ['$dateFromString' => ['dateString' => ['$substr' => ['$PLC_DATE_TIME', 3, 19]]]]]
        ]
    ],
    [
        '$group' => [
            '_id' => [
                'year' => '$year',
                'month' => '$month',
                'day' => '$day',
                'hour' => '$hour'
            ],
            'documents' => [
                '$push' => [
                    'G2_U20_ACTIVE_POWER_TOTAL_KW' => '$G2_U20_ACTIVE_POWER_TOTAL_KW',
                    'U_27_ACTIVE_POWER_TOTAL_KW' => '$U_27_ACTIVE_POWER_TOTAL_KW',
                    'U_24_ACTIVE_POWER_TOTAL_KW' => '$U_24_ACTIVE_POWER_TOTAL_KW',
                    'U_25_ACTIVE_POWER_TOTAL_KW' => '$U_25_ACTIVE_POWER_TOTAL_KW',
                    'G1_U16_ACTIVE_POWER_TOTAL_KW' => 'G1_U16_ACTIVE_POWER_TOTAL_KW',
                    'G1_U17_ACTIVE_POWER_TOTAL_KW' => 'G1_U17_ACTIVE_POWER_TOTAL_KW',
                    'G1_U18_ACTIVE_POWER_TOTAL_KW' => 'G1_U18_ACTIVE_POWER_TOTAL_KW',
                    'G1_U19_ACTIVE_POWER_TOTAL_KW' => 'G1_U19_ACTIVE_POWER_TOTAL_KW',
                    'PLC_DATE_TIME' => '$PLC_DATE_TIME',
                    'minute' => '$minute'
                ]
            ]
        ]
    ],
    [
        '$sort' => [
            '_id.year' => 1,
            '_id.month' => 1,
            '_id.day' => 1,
            '_id.hour' => 1
        ]
    ]
];

// Execute aggregation pipeline
$cursor = $collection->aggregate($pipeline);

// Initialize arrays to store the values
$hourlyData = [];
$hourlyAverages = [];

// Process documents
foreach ($cursor as $hourData) {
    $dateStr = sprintf('%04d-%02d-%02d', $hourData['_id']['year'], $hourData['_id']['month'], $hourData['_id']['day']);
    $hour = $hourData['_id']['hour'];

    if (!isset($hourlyData[$dateStr])) {
        $hourlyData[$dateStr] = [];
    }

    if (!isset($hourlyData[$dateStr][$hour])) {
        $hourlyData[$dateStr][$hour] = [
            'G2_U20_ACTIVE_POWER_TOTAL_KW' => [],
            'U_27_ACTIVE_POWER_TOTAL_KW' => [],
            'U_24_ACTIVE_POWER_TOTAL_KW' => [],
            'U_25_ACTIVE_POWER_TOTAL_KW' => [],
            'G1_U16_ACTIVE_POWER_TOTAL_KW' => [],
            'G1_U17_ACTIVE_POWER_TOTAL_KW' => [],
            'G1_U18_ACTIVE_POWER_TOTAL_KW' => [],
            'G1_U19_ACTIVE_POWER_TOTAL_KW' => [],
            'intervals' => []
        ];
    }

    foreach ($hourData['documents'] as $document) {
        $minute = $document['minute'];
        if ($minute % 15 === 0 || $minute === 0) {
            $hourlyData[$dateStr][$hour]['G2_U20_ACTIVE_POWER_TOTAL_KW'][] = $document['G2_U20_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['U_27_ACTIVE_POWER_TOTAL_KW'][] = $document['U_27_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['U_24_ACTIVE_POWER_TOTAL_KW'][] = $document['U_24_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['U_25_ACTIVE_POWER_TOTAL_KW'][] = $document['U_25_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['G1_U16_ACTIVE_POWER_TOTAL_KW'][] = $document['G1_U16_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['G1_U17_ACTIVE_POWER_TOTAL_KW'][] = $document['G1_U17_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['G1_U18_ACTIVE_POWER_TOTAL_KW'][] = $document['G1_U18_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['G1_U19_ACTIVE_POWER_TOTAL_KW'][] = $document['G1_U19_ACTIVE_POWER_TOTAL_KW'];
            $hourlyData[$dateStr][$hour]['intervals'][] = $document;
        }
    }
}

// Calculate averages
foreach ($hourlyData as $dateStr => $hours) {
    foreach ($hours as $hour => $data) {
        $dataPointsU26 = $data['G2_U20_ACTIVE_POWER_TOTAL_KW'];
        $dataPointsU27 = $data['U_27_ACTIVE_POWER_TOTAL_KW'];
        $dataPointsU24 = $data['U_24_ACTIVE_POWER_TOTAL_KW'];
        $dataPointsU25 = $data['U_25_ACTIVE_POWER_TOTAL_KW'];
        $dataPointsG1_U16 = $data['G1_U16_ACTIVE_POWER_TOTAL_KW'];
        $dataPointsG1_U17 = $data['G1_U17_ACTIVE_POWER_TOTAL_KW'];
        $dataPointsG1_U18 = $data['G1_U18_ACTIVE_POWER_TOTAL_KW'];
        $dataPointsG1_U19 = $data['G1_U19_ACTIVE_POWER_TOTAL_KW'];

        $avgU26 = !empty($dataPointsU26) ? array_sum($dataPointsU26) / count($dataPointsU26) : 0;
        $avgU27 = !empty($dataPointsU27) ? array_sum($dataPointsU27) / count($dataPointsU27) : 0;
        $avgU24 = !empty($dataPointsU24) ? array_sum($dataPointsU24) / count($dataPointsU24) : 0;
        $avgU25 = !empty($dataPointsU25) ? array_sum($dataPointsU25) / count($dataPointsU25) : 0;
        $avgG1_U16 = !empty($dataPointsG1_U16) ? array_sum($dataPointsG1_U16) / count($dataPointsG1_U16) : 0;
        $avgG1_U17 = !empty($dataPointsG1_U17) ? array_sum($dataPointsG1_U17) / count($dataPointsG1_U17) : 0;
        $avgG1_U18 = !empty($dataPointsG1_U18) ? array_sum($dataPointsG1_U18) / count($dataPointsG1_U18) : 0;
        $avgG1_U19 = !empty($dataPointsG1_U19) ? array_sum($dataPointsG1_U19) / count($dataPointsG1_U19) : 0;

        $hourlyAverages[$dateStr][$hour] = [
            'avg_G2_U20_ACTIVE_POWER_TOTAL_KW' => $avgU26,
            'avg_U_27_ACTIVE_POWER_TOTAL_KW' => $avgU27,
            'sum_avg_G2_U20_and_U_27' => $avgU26 + $avgU27,
            'avg_U_24_ACTIVE_POWER_TOTAL_KW' => $avgU24,
            'avg_U_25_ACTIVE_POWER_TOTAL_KW' => $avgU25,
            'sum_avg_U_24_and_U_25' => ($avgU24 + $avgU25) / 1000,
            'avg_G1_U16_ACTIVE_POWER_TOTAL_KW' => $avgG1_U16,
            'avg_G1_U17_ACTIVE_POWER_TOTAL_KW' => $avgG1_U17,
            'avg_G1_U18_ACTIVE_POWER_TOTAL_KW' => $avgG1_U18,
            'avg_G1_U19_ACTIVE_POWER_TOTAL_KW' => $avgG1_U19,
            'sum_avg_G1_U16_and_G1_U17_and_G1_U18_and_G1_U19' => $avgG1_U16 + $avgG1_U17+ $avgG1_U18 + $avgG1_U19 ,

            // 'sum_avg_U_24_and_U_256' => (50)
        ];
    }
}

// Prepare final output based on the date range label
$finalOutput = [];

if ($dateRangeLabel === 'hourly') {
    foreach ($hourlyAverages as $dateStr => $hours) {
        foreach ($hours as $hour => $data) {
            $hourStr = sprintf('%s %02d:00:00', $dateStr, $hour);
            $sum_avg = round($data['sum_avg_U_24_and_U_25'] + $data['sum_avg_G1_U16_and_G1_U17_and_G1_U18_and_G1_U19'], 2);
            
            $solar_usage = ($sum_avg + $data['sum_avg_G2_U20_and_U_27']) != 0 
                ? round(($data['sum_avg_G2_U20_and_U_27'] / ($sum_avg + $data['sum_avg_G2_U20_and_U_27'])) * 100, 2)
                : 0;
            
            $finalOutput[] = [
                'date' => $hourStr,
                'avg_G2_U20_ACTIVE_POWER_TOTAL_KW' => round($data['avg_G2_U20_ACTIVE_POWER_TOTAL_KW'], 2),
                'avg_U_27_ACTIVE_POWER_TOTAL_KW' => round($data['avg_U_27_ACTIVE_POWER_TOTAL_KW'], 2),
                'sum_avg_G2_U20_and_U_27' => round($data['sum_avg_G2_U20_and_U_27'], 2),
                'sum_avg_G1_U16_and_G1_U17_and_G1_U18_and_G1_U19' => round($data['sum_avg_G1_U16_and_G1_U17_and_G1_U18_and_G1_U19'], 2),
                'avg_U_24_ACTIVE_POWER_TOTAL_KW' => round($data['avg_U_24_ACTIVE_POWER_TOTAL_KW'], 2),
                'avg_U_25_ACTIVE_POWER_TOTAL_KW' => round($data['avg_U_25_ACTIVE_POWER_TOTAL_KW'], 2),
                'sum_avg_U_24_and_U_25' => round($data['sum_avg_U_24_and_U_25'], 2),
                'sum_avg' => $sum_avg,
                'solar_usage' => $solar_usage
            ];
        }
    }
}elseif ($dateRangeLabel === 'daily') {
    $dailyAverages = [];
    foreach ($hourlyAverages as $dateStr => $hours) {
        if (!isset($dailyAverages[$dateStr])) {
            $dailyAverages[$dateStr] = [
                'total_G2_U20_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_U_27_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_U_24_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_U_25_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U16_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U17_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U18_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U19_ACTIVE_POWER_TOTAL_KW' => 0,
                'count' => 0
            ];
        }

        foreach ($hours as $hour => $data) {
            $dailyAverages[$dateStr]['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G2_U20_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['total_U_27_ACTIVE_POWER_TOTAL_KW'] += $data['avg_U_27_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['total_U_24_ACTIVE_POWER_TOTAL_KW'] += $data['avg_U_24_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['total_U_25_ACTIVE_POWER_TOTAL_KW'] += $data['avg_U_25_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U16_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U17_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U18_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['total_G1_U9_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U19_ACTIVE_POWER_TOTAL_KW'];
            $dailyAverages[$dateStr]['count']++;
        }
    }

    foreach ($dailyAverages as $dateStr => $totals) {
        $finalOutput[] = [
            'date' => $dateStr,
            
            // Averages
            'avg_G2_U20_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_U_27_ACTIVE_POWER_TOTAL_KW' => round($totals['total_U_27_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'sum_avg_G2_U20_and_U_27' => round($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_27_ACTIVE_POWER_TOTAL_KW'], 2),
            
            'avg_U_24_ACTIVE_POWER_TOTAL_KW' => round($totals['total_U_24_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_U_25_ACTIVE_POWER_TOTAL_KW' => round($totals['total_U_25_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'sum_avg_U_24_and_U_25' => round(($totals['total_U_24_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_25_ACTIVE_POWER_TOTAL_KW']) / 1000, 2),
            
            'avg_G1_U16_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_G1_U17_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_G1_U18_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_G1_U19_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U19_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
        
            // Sum of selected values (fixed errors)
            'sum_avg_G1_U16_and_G1_U17_and_G1_U18_and_G1_U19' => round(
                ($totals['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] +
                 $totals['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] +
                 $totals['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] +
                 $totals['total_G1_U19_ACTIVE_POWER_TOTAL_KW']) / 1000, 2
            ),
        
            // Solar Usage Calculation
            'solar_usage' => round(
                (
                    ($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_27_ACTIVE_POWER_TOTAL_KW']) /
                    (
                        (($totals['total_U_24_ACTIVE_POWER_TOTAL_KW'] + 
                          $totals['total_U_25_ACTIVE_POWER_TOTAL_KW'] +
                          $totals['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] +
                          $totals['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] +
                          $totals['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] +
                          $totals['total_G1_U19_ACTIVE_POWER_TOTAL_KW']) / 1000) 
                        + ($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_27_ACTIVE_POWER_TOTAL_KW'])
                    )
                ) * 100, 2
            )
        ];
        
    }
} elseif ($dateRangeLabel === 'monthly') {
    $monthlyAverages = [];
    foreach ($hourlyAverages as $dateStr => $hours) {
        $dt = new DateTime($dateStr);
        $monthKey = $dt->format('Y-m');

        if (!isset($monthlyAverages[$monthKey])) {
            $monthlyAverages[$monthKey] = [
                'total_G2_U20_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_U_27_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_U_24_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_U_25_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U16_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U17_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U18_ACTIVE_POWER_TOTAL_KW' => 0,
                'total_G1_U19_ACTIVE_POWER_TOTAL_KW' => 0,

                'count' => 0
            ];
        }

        foreach ($hours as $hour => $data) {
            $monthlyAverages[$monthKey]['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G2_U20_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['total_U_27_ACTIVE_POWER_TOTAL_KW'] += $data['avg_U_27_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['total_U_24_ACTIVE_POWER_TOTAL_KW'] += $data['avg_U_24_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['total_U_25_ACTIVE_POWER_TOTAL_KW'] += $data['avg_U_25_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U16_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U17_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U18_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['total_G1_U19_ACTIVE_POWER_TOTAL_KW'] += $data['avg_G1_U19_ACTIVE_POWER_TOTAL_KW'];
            $monthlyAverages[$monthKey]['count']++;
        }
    }

    foreach ($monthlyAverages as $monthKey => $totals) {
        $finalOutput[] = [
            'date' => $monthKey,
            'avg_G2_U20_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_U_27_ACTIVE_POWER_TOTAL_KW' => round($totals['total_U_27_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'sum_avg_G2_U20_and_U_27' => round(($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_27_ACTIVE_POWER_TOTAL_KW']), 2),
            'avg_U_24_ACTIVE_POWER_TOTAL_KW' => round($totals['total_U_24_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_U_25_ACTIVE_POWER_TOTAL_KW' => round($totals['total_U_25_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'sum_avg_U_24_and_U_25' => round((($totals['total_U_24_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_25_ACTIVE_POWER_TOTAL_KW'])) / 1000, 2),
        
            'avg_G1_U16_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_G1_U17_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_G1_U18_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'avg_G1_U19_ACTIVE_POWER_TOTAL_KW' => round($totals['total_G1_U19_ACTIVE_POWER_TOTAL_KW'] / $totals['count'], 2),
            'sum_avg_G1_U16_and_G1_U17_and_G1_U18_and_G1_U19' => round(($totals['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] + $totals['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] + 
            $totals['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] + $totals['total_G1_U19_ACTIVE_POWER_TOTAL_KW']), 2),
        
            'sum_avg' => round($data['sum_avg_U_24_and_U_25'] + round($data['sum_avg_G1_U16_and_G1_U17_and_G1_U18_and_G1_U19']), 2),
        
            'solar_usage' => round((($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_27_ACTIVE_POWER_TOTAL_KW']) / 
            ((($totals['total_U_24_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_25_ACTIVE_POWER_TOTAL_KW'] + 
            $totals['total_G1_U16_ACTIVE_POWER_TOTAL_KW'] + $totals['total_G1_U17_ACTIVE_POWER_TOTAL_KW'] + 
            $totals['total_G1_U18_ACTIVE_POWER_TOTAL_KW'] + $totals['total_G1_U19_ACTIVE_POWER_TOTAL_KW']) / 1000) + 
            ($totals['total_G2_U20_ACTIVE_POWER_TOTAL_KW'] + $totals['total_U_27_ACTIVE_POWER_TOTAL_KW']))) * 100, 2)
        ];
        
    }
}

echo json_encode($finalOutput);
?>
