<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
date_default_timezone_set('Africa/Addis_Ababa');
const APP_NAME = 'OBN Program Workflow';
const BASE_URL = '/OBN-Program-Workflow';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'obn_workflow';
const DB_USER = 'root';
const DB_PASS = '';
const SESSION_TIMEOUT = 3600;

function e(?string $value): string
{
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
	header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
	exit;
}

function csrf_token(): string
{
	if (empty($_SESSION['csrf'])) {
		$_SESSION['csrf'] = bin2hex(random_bytes(32));
	}

	return $_SESSION['csrf'];
}

function verify_csrf(?string $token): void
{
	if (!hash_equals($_SESSION['csrf'] ?? '', $token ?? '')) {
		http_response_code(419);
		exit('Invalid request token.');
	}
}

function json_response(bool $success, array $data = [], ?string $error = null, int $status = 200): never
{
	header('Content-Type: application/json; charset=utf-8');
	http_response_code($status);
	echo json_encode(['success' => $success, 'data' => $data, 'error' => $error], JSON_UNESCAPED_UNICODE);
	exit;
}

function request_data(): array
{
	if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
		$decoded = json_decode((string) file_get_contents('php://input'), true);
		return is_array($decoded) ? $decoded : [];
	}

	return $_POST;
}
