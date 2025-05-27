<?php

namespace app;

class TerminalController
{
    public function run()
    {

        if (!isset($_SESSION['usuario'])) {
            // http_response_code(401); // ← Establece el código HTTP 401
            header('Content-Type: application/json');
            echo json_encode(["status" => false, "output" => "Usted no esta autorizado", "asynchronous" => false]);
            exit;
        }

        $directorioTerminal = dirname(dirname(getcwd())); //base de trabajo de consola
        if (!isset($_SESSION['directorioTerminal'])) {
            $_SESSION['directorioTerminal'] = $directorioTerminal;
        }


        if (empty($_POST)) {
            $_POST = json_decode(file_get_contents('php://input'), true) ?? null;
        }
        if (!isset($_POST['command'])) {
            exit;
        }

        $command = $_POST['command'];

        $comandosProhibidos = [
            'rm',
            'rmdir',
            'mv',
            'dd',
            'chmod',
            'chown', // Comandos destructivos
            'curl',
            'wget',
            'scp',
            'rsync',
            'ssh',      // Comandos relacionados con la red
            'kill',
            'killall',
            'pkill',                 // Comandos de procesos
            'sudo',
            'su',                               // Comandos administrativos
            'reboot',
            'shutdown',
            'halt',               // Comandos de sistema
            '>',
            'cat >',
            'cat',             // Redirección de salida
            'echo >'                      // Sobrescritura de archivos
        ];


        if (in_array(explode(' ', $command)[0], $comandosProhibidos)) {
            echo json_encode(["status" => false, "output" => "Error: Comando no permitido.", "asynchronous" => false]);
            exit;
        }

        $comandosSincronos = [
            'ls',        // Lista archivos
            'cat',       // Muestra el contenido de un archivo
            'echo',      // Imprime texto
            'pwd',       // Muestra el directorio actual
            'mkdir',     // Crea un directorio
            'touch',     // Crea archivo vacío
            'rm',        // Elimina archivo o carpeta
            'rmdir',     // Elimina directorios vacíos
            'whoami',    // Usuario actual
            'date',      // Fecha y hora actual
            'hostname',  // Nombre del host
            'uname',     // Información del sistema
            'id',        // ID de usuario
            'df',        // Espacio en disco
            'du',        // Uso de espacio (rápido en carpetas pequeñas)
            'head',      // Primeras líneas de archivo
            'tail',      // Últimas líneas de archivo
            'stat',      // Estado del archivo
            'basename',  // Nombre de archivo sin ruta
            'dirname',   // Solo la ruta del archivo
            'uptime',    // Tiempo activo del sistema
            'clear',     // Limpia pantalla (no funcional en scripts, pero común)
            'php -v',  // Versión de PHP
            'php --version', // Versión de Python
            'cd', // Cambia de directorio
        ];
        //EJECUCION DE COMANDO SINCRONOS
        if (preg_match('/^cd\s+(.*)$/', $command, $matches)) {
            $newDir = trim($matches[1]);

            // Manejar el caso de "cd .." para retroceder un directorio
            if ($newDir === '..') {
                // Prevenir retroceder fuera del directorio de trabajo
                if ($_SESSION['directorioTerminal'] === $directorioTerminal) {
                    //echo json_encode(["output"=>"Error: No se puede retroceder más allá del directorio base."]);
                    echo json_encode(["status" => true, "output" => "", "asynchronous" => false, 'data' => [
                        'usuario' => $_SESSION['usuario'],
                        'directorioTerminal' => $_SESSION['directorioTerminal'],
                    ]]);
                    exit;
                }
                // Retroceder un nivel en el directorio
                $newDir = dirname($_SESSION['directorioTerminal']);
            } elseif ($newDir !== '') {
                // Resolver una ruta absoluta o relativa
                $newDir = realpath($_SESSION['directorioTerminal'] . '/' . $newDir);
            }

            // Verificar si el directorio existe y si es un directorio
            if ($newDir !== false && is_dir($newDir)) {
                $_SESSION['directorioTerminal'] = $newDir;
                echo json_encode(["status" => true, "output" => "",  "asynchronous" => false, 'data' => [
                    'usuario' => $_SESSION['usuario'],
                    'directorioTerminal' => $_SESSION['directorioTerminal'],
                ]]);
                exit;
            } else {
                echo json_encode(["status" => true, "output" => "Error: El directorio no existe o no es accesible.", "asynchronous" => false]);
                exit;
            }
        } else {
            //EJECUCION DE COMANDOS ASINCRONOS
            if (!in_array($command, $comandosSincronos)) {
                //AGREGAR ANSI POR DEFECTO
                $comandoBase = explode(' ', trim($command))[0];
                $comandosConAnsi = ['./composer.sh','composer', 'npm', 'yarn', 'php'];

                if (
                    in_array($comandoBase, $comandosConAnsi) &&
                    !str_contains($command, '--ansi') &&
                    !str_contains($command, '--no-ansi')
                ) {
                    $command .= ' --ansi';
                }

                $id = session_id();
                $outputFile = __DIR__ . "/../temp/output_$id.txt";
                $pidFile = __DIR__ . "/../temp/pid_$id.txt";

                // Limpia archivos
                file_put_contents($outputFile, '');
                file_put_contents($pidFile, '');

                // Armamos comando completo
                $directorio = escapeshellarg($_SESSION['directorioTerminal']);
                $cmd = "$command";

                // $fullCmd = "cd $directorio && ($cmd) & echo \$! > " . escapeshellarg($pidFile);

                $exitCodeFile = __DIR__ . "/../temp/exitcode_$id.txt";
                $fullCmd = "cd $directorio && ($cmd; echo \$? > " . escapeshellarg($exitCodeFile) . ") & echo \$! > " . escapeshellarg($pidFile);

                $descriptorspec = [
                    1 => ["file", $outputFile, "a"],
                    2 => ["file", $outputFile, "a"],
                ];

                $process = proc_open("bash -c " . escapeshellarg($fullCmd), $descriptorspec, $pipes);

                if (is_resource($process)) {
                    echo json_encode(["status" => true, "asynchronous" => true]);
                    exit;
                } else {
                    echo json_encode(["status" => false, "output" => "No se pudo ejecutar", "asynchronous" => false]);
                    exit;
                }
            }
            //EJECUCION DE COMANDOS SINCRONOS
            $output = shell_exec("cd " . escapeshellarg($_SESSION['directorioTerminal']) . " && $command 2>&1");
            $output = $output !== null ? $output : '';
    
            echo json_encode([
                "status" => true,
                "output" => htmlspecialchars($output),
                "asynchronous" => false
            ]);
            exit;
        }
    }

    public function output(int $indiceDeLectura = 0)
    {
        $id = session_id();
        $baseDir = __DIR__ . "/../temp";

        $outputFile = "$baseDir/output_$id.txt";
        $pidFile = "$baseDir/pid_$id.txt";
        $exitCodeFile = "$baseDir/exitcode_$id.txt";

        $timeout = 30;
        $intervalo = 1;
        $inicio = time();
        $output = '';
        $newLength = 0;
        $done = false;
        $exitCode = null;

        function procesoFinalizado($pidFile)
        {
            if (!file_exists($pidFile)) return true;

            $pid = trim(file_get_contents($pidFile));
            return ($pid === '' || !ctype_digit($pid) || !file_exists("/proc/$pid"));
        }

        while (true) {
            if (file_exists($outputFile)) {
                $fp = fopen($outputFile, 'r');
                fseek($fp, (int)$indiceDeLectura);
                $output = stream_get_contents($fp);
                fclose($fp);

                $newLength = strlen($output);
                if ($newLength > 0) {
                    break;
                }
            }

            if (procesoFinalizado($pidFile)) {
                $done = true;
                break;
            }

            if ((time() - $inicio) >= $timeout) {
                break;
            }

            sleep($intervalo);
        }

        // Final check
        if (procesoFinalizado($pidFile)) {
            $done = true;
            if (file_exists($pidFile)) unlink($pidFile);

            if (file_exists($exitCodeFile)) {
                $exitCode = (int)trim(file_get_contents($exitCodeFile));
                unlink($exitCodeFile);
            }
        }

        echo json_encode([
            "output" => $output,
            "length" => $newLength,
            "done" => $done,
            "exitCode" => $exitCode
        ]);
    }
}
