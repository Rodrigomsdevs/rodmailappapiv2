<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;

class AuthController extends ResourceController
{
    public function login()
    {
        $model = new UserModel();
        $usernameOrEmail = $this->request->getGet('username');
        $password = $this->request->getGet('password');

        if (empty($usernameOrEmail) || empty($password)) {
            return $this->respond(['message' => 'Credenciais não informadas. Por favor, informe seu nome de usuário ou email e senha.'], 401);
        }

        // Verificar se é email ou username
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            // Buscar por email
            $user = $model->where('email', $usernameOrEmail)->first();
        } else {
            // Buscar por nome de usuário
            $user = $model->where('username', $usernameOrEmail)->first();
        }

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->respond(['message' => 'Credenciais inválidas. Verifique seu nome de usuário/email e senha e tente novamente.'], 401);
        }

        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'admin' => $user['admin']
        ];

        $token = JwtHelper::generateToken($userData);

        unset($user['password']);

        return $this->respond([
            'token' => $token,
            'user' => $user
        ], 200);
    }

    public function validateToken()
    {
        $authHeader = $this->request->getHeader('Authorization');
        if ($authHeader) {
            $token = str_replace('Bearer ', '', $authHeader->getValue()); // Remove "Bearer " do token
            try {
                $decodedToken = JwtHelper::validateToken($token);
                return $this->respond(['message' => 'Token is valid', 'data' => $decodedToken], 200);
            } catch (\Exception $e) {
                return $this->respond(['message' => 'Invalid token', 'error' => $e->getMessage()], 401);
            }
        }
        return $this->respond(['message' => 'Authorization header not found'], 400);
    }

    public function register()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[255]|is_unique[users.username]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]'
        ];

        $messages = [
            'username' => [
                'is_unique' => 'O nome de usuário já está em uso.'
            ],
            'email' => [
                'is_unique' => 'O email já está em uso.'
            ],
            'password' => [
                'min_length' => 'A senha precisa ter no minimo 8 digitos.'
            ]
        ];

        if (!$this->validate($rules, $messages)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $userModel = new UserModel();

        $data = [
            'username' => $this->request->getVar('username'),
            'email'    => $this->request->getVar('email'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'admin'    => 'N',
            'vip'      => 'N',
            'ativo'    => 'S',
            'banido'   => 'N'
        ];

        $userModel->save($data);

        return $this->respondCreated(['message' => 'Usuário registrado com sucesso']);
    }
}
