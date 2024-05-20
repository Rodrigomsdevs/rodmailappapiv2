<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CodeIgniter\Config\BaseService;

class JwtHelper
{
    private static $key;
    private static $alg = 'HS256';

    public static function initialize()
    {
        self::$key = getenv('JWT_SECRET');
    }

    public static function generateToken($data)
    {
        self::initialize();
        $payload = [
            'iss' => 'your-issuer', // Emissor
            'aud' => 'your-audience', // Audiência
            'iat' => time(), // Data de emissão
            'nbf' => time(), // Não antes
            'exp' => time() + (3600 * 24 * 30), // Expiração (1 hora)
            'data' => $data // Dados do usuário
        ];

        return JWT::encode($payload, self::$key, self::$alg);
    }

    public static function decodeToken($token)
    {
        self::initialize();
        try {
            return JWT::decode($token, new Key(self::$key, self::$alg));
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function validateToken($token)
    {
        self::initialize();
        return JWT::decode($token, new Key(self::$key, 'HS256'));
    }
}
