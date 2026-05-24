<?php

require_once __DIR__ . '/../src/Models/Content.php';
require_once __DIR__ . '/../src/Models/Author.php';

$router = [
    'GET' => [],
    'POST' => [],
    'PUT' => [],
    'DELETE' => []
];

// GET /api/content - Get all contents
$router['GET']['/api/content'] = function() {
    $content = new Content(Database::getInstance());
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    $author = isset($_GET['author']) ? $_GET['author'] : null;
    $industry = isset($_GET['industry']) ? $_GET['industry'] : null;
    $contractor = isset($_GET['contractor']) ? $_GET['contractor'] : null;

    
    $results = $content->getAll($type, $author, $industry, $contractor); //only returns not deleted content (includes unpublished
    jsonResponse($results);
};

// GET /api/contents/{id} - Get content by ID
$router['GET']['/api/content/(\d+)'] = function($matches) {
    $content = new Content(Database::getInstance());
    $result = $content->getById($matches[0]); //also returns deleted and unpublished
    
    if ($result['success']==1) {
        jsonResponse($result["result"]);
    } else {
        jsonResponse($result["error"], 404);
    }
};

// POST /api/contents - Create new content
$router['POST']['/api/content'] = function() {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['type']) || !isset($data['author']) || !isset($data['body']) || !isset($data['title'])) {
        jsonResponse(['error' => 'Missing required fields: type, author, body, title'], 400);
        return;
    }
    
    $content = new Content(Database::getInstance());
    $response = $content->create($data);
    
    if ($response['success'] == 1) {
        jsonResponse(['message' => 'Content created', 'id' => $response['id']], 201);
    } else {
        jsonResponse(['error' => $response['error']], 500);
    }
};

// PUT /api/content/{id} - Update content
$router['PUT']['/api/content/(\d+)'] = function($matches) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        jsonResponse(['error' => 'Invalid JSON data'], 400);
        return;
    }
    
    $content = new Content(Database::getInstance());
    $result = $content->getById($matches[0]);
    
    if (!$result) {
        jsonResponse(['error' => 'Content not found'], 404);
        return;
    }
    
    $updated = $content->update($matches[0], $data);
    
    if ($updated['success'] == 1) {
        jsonResponse(['message' => 'Content updated']);
    } else {
        jsonResponse(['error' => $updated['error']], 500);
    }
};

// PUT /api/content/{id}/publish - publish content by ID
$router['PUT']['/api/content/(\d+)/publish'] = function($matches) {
	$content = new Content(Database::getInstance());
	$existing = $content->getById($matches[0]);
	if (!$existing) {
		jsonResponse(['error' => 'Content not found'], 404);
		return;
	}
	$result = $content->publish($matches[0]);

	if ($result['success'] == 1) {
		jsonResponse(['message' => 'Content published']);
	} else {
		jsonResponse(['error' => 'Unable to publish content'], 404);
	}
};

// DELETE /api/content/{id} - Delete content
$router['DELETE']['/api/content/(\d+)'] = function($matches) {
    $content = new Content(Database::getInstance());
    $result = $content->getById($matches[0]);
    
    if (!$result) {
        jsonResponse(['error' => 'Content not found'], 404);
        return;
    }
    
    $deleted = $content->delete($matches[0]);
    
    if ($deleted) {
        jsonResponse(['message' => 'Content deleted']);
    } else {
        jsonResponse(['error' => 'Failed to delete content'], 500);
    }
};

// GET /api/authors/{id} - Get author
$router['GET']['/api/authors/(\d+)'] = function($matches) {
	$author = new Author(Database::getInstance());
	$result = $author->getAuthor($matches[0]);

	if ($result) {
		jsonResponse($result);
	} else {
		jsonResponse(['error' => 'Author not found'], 404);
	}
};

// POST /api/authors - Add author
$router['POST']['/api/authors'] = function() {
	$data = json_decode(file_get_contents('php://input'), true);

	if (!$data) {
		jsonResponse(['error' => 'Invalid JSON data'], 400);
		return;
	}

	$author = new Author(Database::getInstance());
	$response = $author->addAuthor($data);

	if ($response['success'] == 1) {
		jsonResponse(['message' => 'Author created', 'id' => $response['id']], 201);
	} else {
		jsonResponse(['error' => $response['error']], 500);
	}
};

// GET /{content_type}/{slug} - Get specific article
$router['GET']['/([\w\-]+)/([\w\-]+)'] = function($matches) {
	$content = new Content(Database::getInstance());

	$results = $content->getBySlug($matches[0], $matches[1]); //only returns published content
	if ($results["success"] == 0) jsonResponse(["error" => $results["error"]], 404);
	else jsonResponse($results["result"]);
};

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function route($router) {
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

	foreach ($router[$method] as $pattern => $handler) {


        $regex = '#^' . $pattern . '$#';
        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches);
            return $handler($matches);
        }
    }
    
    jsonResponse(['error' => 'Route not found'], 404);
}

route($router);
