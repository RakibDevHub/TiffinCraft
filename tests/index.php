<?php
require_once __DIR__ . '/../src/config/app.php';
require_once BASE_PATH . '/src/helpers/Logger.php';
require_once BASE_PATH . '/src/helpers/Session.php';
require_once BASE_PATH . '/src/helpers/Auth.php';
require_once BASE_PATH . '/src/router/routes.php';

logMessage('INFO', "Page accessed: " . $_SERVER['REQUEST_URI']);
Session::start();

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

function getLayout($requiredRole = null)
{
    $role = $requiredRole ? strtolower($requiredRole) : null;

    if ($role === 'admin') {
        return BASE_PATH . '/src/views/layouts/admin_layout.php';
    } elseif ($role === 'seller') {
        return BASE_PATH . '/src/views/layouts/seller_layout.php';
    } else {
        return BASE_PATH . '/src/views/layouts/default_layout.php';
    }
}


function showErrorPage($code, $title, $message, $layout = null)
{
    http_response_code($code);
    $data = [
        'viewFile' => BASE_PATH . '/src/views/pages/common/error.php',
        'title' => $title,
        'code' => $code,
        'message' => $message
    ];

    $layout = $layout ?: getLayout();
    include $layout;
    exit;
}

if (!isset($routes[$path])) {
    showErrorPage(404, '404 Not Found', 'Page not found');
}

$route = $routes[$path];

$requiredRole = $route['role'] ?? null;

if ($requiredRole && $requiredRole !== 'any') {
    if (!Session::get('user_id')) {
        Session::flash('error', 'Please log in to access this page.');
        header("Location: /login");
        exit;
    }

    $currentRole = strtolower(Session::get('user_role') ?? '');

    if ($currentRole !== strtolower($requiredRole)) {
        showErrorPage(
            403,
            'Access Denied',
            "You don't have permission to access this page"
        );
    }
}


$layout = getLayout($requiredRole);

$controllerFile = BASE_PATH . "/src/controllers/{$route['controller']}.php";
if (!file_exists($controllerFile)) {
    showErrorPage(500, 'Server Error', 'Oops! Something went wrong. Please try again later.', $layout);
}

require_once $controllerFile;
$controllerName = $route['controller'] ?? null;
$method = $route['method'] ?? null;

if (!$controllerName || !$method) {
    logError('500', "Invalid route configuration. Route data: " . json_encode($route));
    showErrorPage(
        500,
        'Server Error',
        'Oops! Something went wrong. Please contact support if the problem persists.',
        $layout
    );
}


if (!class_exists($controllerName) || !method_exists($controllerName, $method)) {
    showErrorPage(404, '404 Not Found', 'Page Not Found', $layout);
}

try {
    $controllerInstance = new $controllerName();
    $data = $controllerInstance->{$method}();

    include $layout;
} catch (Exception $e) {
    logError('ERROR', "Controller execution failed: " . $e->getMessage());
    showErrorPage(500, 'Server Error', 'An error occurred while processing your request.', $layout);
}
