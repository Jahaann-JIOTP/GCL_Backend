<?php
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

function connectDB()
{
    try {
        $client = new MongoDB\Client("mongodb://Jamal:rVl8O8iMN@43.204.118.114:57019/GCL?authSource=admin&readPreference=primary&ssl=false");
        return $client->GCL;
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to connect to MongoDB: " . $e->getMessage()]);
        exit;
    }
}

$db = connectDB();
$collection = $db->GCL_new;
$collection->createIndex(['timestamp' => 1]);

$meterIds = ["G2_U20", "U_27", "U_24", "U_25", "G1_U16", "G1_U17", "G1_U18", "G1_U19"];
$suffixes = ["ACTIVE_ENERGY_IMPORT_KWH"];

$solarKeys = ["G2_U20_ACTIVE_ENERGY_IMPORT_KWH", "U_27_ACTIVE_ENERGY_IMPORT_KWH"];
$transformerKeys = ["U_24_ACTIVE_ENERGY_IMPORT_KWH", "U_25_ACTIVE_ENERGY_IMPORT_KWH"];
$gensetKeys = ["G1_U16_ACTIVE_ENERGY_IMPORT_KWH", "G1_U17_ACTIVE_ENERGY_IMPORT_KWH", "G1_U18_ACTIVE_ENERGY_IMPORT_KWH", "G1_U19_ACTIVE_ENERGY_IMPORT_KWH"];

function getDayDate($dayOffset = 0)
{
    $date = strtotime("this week Monday +{$dayOffset} days");
    return [
        'start' => date("Y-m-d", $date) . 'T00:00:00.000+05:00',
        'end' => date("Y-m-d", $date) . 'T23:59:59.999+05:00'
    ];
}

function fetchConsumption($collection, $dates, $meterIds, $suffixes, $solarKeys, $transformerKeys, $gensetKeys)
{
    $projection = ['timestamp' => 1];
    foreach ($meterIds as $meterId) {
        foreach ($suffixes as $suffix) {
            $projection["{$meterId}_{$suffix}"] = 1;
        }
    }

    $pipeline = [
        ['$match' => ['timestamp' => ['$gte' => $dates['start'], '$lte' => $dates['end']]]],
        ['$project' => $projection],
        ['$sort' => ['timestamp' => 1]]
    ];

    $data = $collection->aggregate($pipeline)->toArray();

    $firstValues = [];
    $lastValues = [];
    foreach ($data as $document) {
        foreach ($meterIds as $meterId) {
            foreach ($suffixes as $suffix) {
                $key = "{$meterId}_{$suffix}";
                if (isset($document[$key])) {
                    if (!isset($firstValues[$key])) {
                        $firstValues[$key] = $document[$key];
                    }
                    $lastValues[$key] = $document[$key];
                }
            }
        }
    }

    $consumptionData = [];
    foreach ($firstValues as $key => $firstValue) {
        if (isset($lastValues[$key])) {
            $consumptionData[$key] = $lastValues[$key] - $firstValue;
        }
    }

    $totalConsumption = [
        "Solars" => 0,
        "Transformers" => 0,
        "Genset" => 0
    ];

    foreach ($consumptionData as $key => $value) {
        if (in_array($key, $solarKeys)) {
            $totalConsumption["Solars"] += $value;
        } elseif (in_array($key, $transformerKeys)) {
            $totalConsumption["Transformers"] += $value * 10; // Corrected multiplication only for Transformers
        } elseif (in_array($key, $gensetKeys)) {
            $totalConsumption["Genset"] += $value;
        }
    }

    return $totalConsumption["Solars"] + $totalConsumption["Transformers"] + $totalConsumption["Genset"];
}

// Weekly Calculation
$days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
$result = [];

for ($i = 0; $i < 7; $i++) {
    $thisWeekDate = getDayDate($i);
    $lastWeekDate = getDayDate($i - 7);

    $thisWeekConsumption = fetchConsumption($collection, $thisWeekDate, $meterIds, $suffixes, $solarKeys, $transformerKeys, $gensetKeys);
    $lastWeekConsumption = fetchConsumption($collection, $lastWeekDate, $meterIds, $suffixes, $solarKeys, $transformerKeys, $gensetKeys);

    $result[] = [
        "Day" => $days[$i],
        "This Week" => round($thisWeekConsumption, 2),
        "Last Week" => round($lastWeekConsumption, 2)
    ];
}

// Monthly Calculation
if (isset($_GET['value']) && $_GET['value'] === 'month') {
    function getWeekRanges($month, $year) {
        $weeks = [];
        $startDate = strtotime("first monday of $year-$month");
        
        for ($i = 0; $i < 4; $i++) {
            $weekStart = date('Y-m-d', strtotime("+$i week", $startDate));
            $weekEnd = date('Y-m-d', strtotime("+6 days", strtotime($weekStart)));
            $weeks[] = [$weekStart, $weekEnd];
        }
        return $weeks;
    }

    $currentMonth = date('m');
    $currentYear = date('Y');
    $lastMonth = date('m', strtotime("-1 month"));
    $lastYear = date('Y', strtotime("-1 month"));

    $weekLabels = ["Week1", "Week2", "Week3", "Week4"];
    $result = [];

    $weeksThisMonth = getWeekRanges($currentMonth, $currentYear);
    $weeksLastMonth = getWeekRanges($lastMonth, $lastYear);

    for ($i = 0; $i < 4; $i++) {
        $thisMonthConsumption = fetchConsumption($collection, ['start' => $weeksThisMonth[$i][0] . 'T00:00:00.000+05:00', 'end' => $weeksThisMonth[$i][1] . 'T23:59:59.999+05:00'], $meterIds, $suffixes, $solarKeys, $transformerKeys, $gensetKeys);
        
        $lastMonthConsumption = fetchConsumption($collection, ['start' => $weeksLastMonth[$i][0] . 'T00:00:00.000+05:00', 'end' => $weeksLastMonth[$i][1] . 'T23:59:59.999+05:00'], $meterIds, $suffixes, $solarKeys, $transformerKeys, $gensetKeys);

        $result[] = [
            "Weeks" => $weekLabels[$i],
            "Last Month" => round($lastMonthConsumption, 2),
            "This Month" => round($thisMonthConsumption, 2)
        ];
    }
}
function getHourlyTimestamps($dayOffset = 0)
{
    $date = date("Y-m-d", strtotime("$dayOffset days"));
    $hours = [];
    for ($i = 0; $i < 24; $i++) {
        $hour = str_pad($i, 2, "0", STR_PAD_LEFT);
        $hours[] = [
            'start' => "$date" . "T$hour:00:00.000+05:00",
            'end' => "$date" . "T$hour:59:59.999+05:00",
            'label' => "$hour:00"
        ];
    }
    return $hours;
}

function fetchHourlyConsumption($collection, $timestamps, $meterIds, $suffixes)
{
    $hourlyData = [];
    
    foreach ($timestamps as $time) {
        $pipeline = [
            ['$match' => ['timestamp' => ['$gte' => $time['start'], '$lte' => $time['end']]]],
            ['$sort' => ['timestamp' => 1]]
        ];
        
        $data = $collection->aggregate($pipeline)->toArray();
        
        $firstValues = [];
        $lastValues = [];
        foreach ($data as $document) {
            foreach ($meterIds as $meterId) {
                foreach ($suffixes as $suffix) {
                    $key = "{$meterId}_{$suffix}";
                    if (isset($document[$key])) {
                        if (!isset($firstValues[$key])) {
                            $firstValues[$key] = $document[$key];
                        }
                        $lastValues[$key] = $document[$key];
                    }
                }
            }
        }

        $consumption = 0;
        foreach ($firstValues as $key => $firstValue) {
            if (isset($lastValues[$key])) {
                $consumption += ($lastValues[$key] - $firstValue);
            }
        }

        $hourlyData[] = [
            "Time" => $time['label'],
            "Consumption" => round($consumption, 2)
        ];
    }
    return $hourlyData;
}

if (isset($_GET['value']) && $_GET['value'] === 'today') {
    $todayTimestamps = getHourlyTimestamps(0);
    $yesterdayTimestamps = getHourlyTimestamps(-1);

    $todayData = fetchHourlyConsumption($collection, $todayTimestamps, $meterIds, $suffixes);
    $yesterdayData = fetchHourlyConsumption($collection, $yesterdayTimestamps, $meterIds, $suffixes);
    
    $result = [];
    for ($i = 0; $i < 24; $i++) {
        $result[] = [
            "Time" => $todayData[$i]["Time"],
            "Today" => $todayData[$i]["Consumption"],
            "Yesterday" => $yesterdayData[$i]["Consumption"]
        ];
    }

}

function getMonthDateRange($year, $month)
{
    return [
        'start' => date("Y-m-01", strtotime("$year-$month-01")) . 'T00:00:00.000+05:00',
        'end' => date("Y-m-t", strtotime("$year-$month-01")) . 'T23:59:59.999+05:00'
    ];
}

if (isset($_GET['value']) && $_GET['value'] === 'year') {
    $currentYear = date('Y');
    $previousYear = $currentYear - 1;
    $months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    $result = [];

    for ($i = 1; $i <= 12; $i++) {
        $currentYearDates = getMonthDateRange($currentYear, $i);
        $previousYearDates = getMonthDateRange($previousYear, $i);

        $currentYearConsumption = fetchConsumption($collection, $currentYearDates, $meterIds, $suffixes, $solarKeys, $transformerKeys, $gensetKeys);
        $previousYearConsumption = fetchConsumption($collection, $previousYearDates, $meterIds, $suffixes, $solarKeys, $transformerKeys, $gensetKeys);

        $result[] = [
            "Month" => $months[$i - 1],
            "Current Year" => round($currentYearConsumption, 2),
            "Previous Year" => round($previousYearConsumption, 2)
        ];
    }}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
