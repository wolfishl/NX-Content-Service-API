<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv =
	Dotenv\Dotenv::createImmutable(
		__DIR__ . '/../config'
	);

$dotenv->load();

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include the API routes
require_once __DIR__ . '/../routes/api.php';
