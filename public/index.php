<?php

use app\AuthController;
use app\TerminalController;

require __DIR__ . '/../lib/autoload.php';

session_start();

$terminalController = new TerminalController();
$authController = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST)) {
    $_POST = json_decode(file_get_contents('php://input'), true) ?? null;
  }
  if (isset($_POST['usuario']) && isset($_POST['password'])) {
    $authController->login();
  } elseif (isset($_POST['command'])) {
    $terminalController->run();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['indiceDeLectura'])) {
    $terminalController->output($_GET['indiceDeLectura']);
  } elseif (isset($_GET['checkSesion'])) {
    $authController->checkSession();
  } elseif (isset($_GET['logout'])) {
    $authController->logout();
  }
}
