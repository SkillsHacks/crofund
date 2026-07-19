<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/location/LocationService.php';

$countries = require __DIR__ . '/../config/countries.php';
$citiesByCountry = require __DIR__ . '/../config/location/cities.php';
$locationService = new LocationService($countries, $citiesByCountry);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed.'
        ]);
        exit;
    }

    $countryCode = strtoupper(trim($_GET['country'] ?? ''));
    if ($countryCode === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Country is required.'
        ]);
        exit;
    }

    if (!$locationService->isValidCountry($countryCode)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Country not found.'
        ]);
        exit;
    }

    $country = $locationService->getCountryByCode($countryCode);
    $cityNames = $locationService->getCitiesForCountry($countryCode);
    $countryRecords = $locationService->createCountryRecords();
    $cityRecords = $locationService->createCityRecords();

    $countryRecord = null;
    foreach ($countryRecords as $record) {
        if ($record['iso_code'] === $countryCode) {
            $countryRecord = $record;
            break;
        }
    }

    $filteredCityRecords = [];
    if ($countryRecord !== null) {
        foreach ($cityRecords as $record) {
            if ($record['country_id'] === $countryRecord['id']) {
                $filteredCityRecords[] = $record;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'country' => [
            'id' => $countryRecord['id'] ?? null,
            'name' => $country['name'] ?? $countryCode,
            'iso_code' => $countryCode,
        ],
        'cities' => array_map(function ($cityName, $index) use ($countryRecord, $filteredCityRecords) {
            return [
                'id' => $filteredCityRecords[$index]['id'] ?? ($index + 1),
                'country_id' => $countryRecord['id'] ?? null,
                'city_name' => $cityName,
            ];
        }, $cityNames, array_keys($cityNames)),
    ]);
} catch (Exception $e) {
    error_log('cities API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load cities right now. Please try again.'
    ]);
}
