<?php

namespace lib;

use Exception;

class EnvLoader {

    public static function load($path)
    {
        if (!file_exists($path)) {
            throw new Exception('El archivo .env no existe.');
        }
    
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Separate the name and value parts
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            
            // Remove surrounding quotes from the value
            $value = trim($value);
            if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                $value = $matches[1];
            }
            
            // Set the environment variable
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
