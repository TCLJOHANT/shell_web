<?php

namespace app;

use lib\EnvLoader;

EnvLoader::load(__DIR__ . '/../.env');

class AuthController
{
    public function logout()
    {
        session_destroy();
        echo json_encode(['mensaje' => 'se cerro sesion con exito', 'status' => true]);
    }

    public function login()
    {
        $autorizadosUsuarios = [
            ["user" => getenv('USER_SHELL'), "password" => getenv('PASSWORD_SHELL')],
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isLogin = false;
            if (empty($_POST)) {
                $_POST = json_decode(file_get_contents('php://input'), true) ?? null;
            }
            $usuario = $_POST['usuario'] ?? '';
            $password = $_POST['password'] ?? '';

            foreach ($autorizadosUsuarios as $credenciales) {
                if (
                    $credenciales['user'] === $usuario &&
                    password_verify($password, $credenciales['password'])
                ) {
                    $_SESSION['usuario'] = $usuario;
                    $isLogin = true;
                    $directorioTerminal = dirname(dirname(getcwd()));
                    if (!isset($_SESSION['directorioTerminal'])) {
                        $_SESSION['directorioTerminal'] = $directorioTerminal;
                    }
                    break;
                }
            }

            echo $isLogin
                ? json_encode([
                    'mensaje' => 'Bienvenido',
                    'status' => $isLogin,
                    'data' => [
                        'usuario' => $_SESSION['usuario'],
                        'directorioTerminal' => $_SESSION['directorioTerminal'],
                    ]
                ])
                : json_encode(['mensaje' => 'Credenciales incorrectas.', 'status' => $isLogin]);
        }
    }

    public function checkSession()
    {
        $autorizadosUsuarios = [
            ["user" => getenv('USER_SHELL'), "password" => getenv('PASSWORD_SHELL')],
        ];
        $isLogin = false;
        if (isset($_SESSION['usuario'])) {
            foreach ($autorizadosUsuarios as $usuarioAutorizado) {
                if ($usuarioAutorizado['user'] === $_SESSION['usuario']) {
                    $isLogin = true;
                    break;
                }
            }
        }
        echo  json_encode(['status' => $isLogin, 'data' => [
            'usuario' => $_SESSION['usuario'] ?? '',
            'directorioTerminal' => $_SESSION['directorioTerminal'] ?? dirname(getcwd()),
        ]]);
    }
}
