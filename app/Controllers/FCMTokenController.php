<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EmailFcmTokenModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\JwtHelper;

class FCMTokenController extends ResourceController
{
    protected $modelName = 'App\Models\EmailFcmTokenModel';
    protected $format    = 'json';

    // Create or update FCM token
    public function saveToken()
    {
        // Get the user from the token
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;

        // Get the request data
        $data = $this->request->getJSON(true);

        // Log the incoming data for debugging
        log_message('debug', 'Incoming data: ' . json_encode($data));

        // Check if 'token' key exists in the data array
        if (!isset($data['token'])) {
            return $this->failValidationError('Token is required');
        }

        // Validate the request data
        if (!$this->validate([
            'token' => 'required|string',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Check if the token already exists for the user
        $existingToken = $this->model->where('user_id', $userId)->first();
        if ($existingToken) {
            // Update the existing token
            $this->model->update($existingToken['id'], ['token' => $data['token'], 'user_id' => $userId]);
        } else {
            // Insert the new token
            $this->model->save(['token' => $data['token'], 'user_id' => $userId]);
        }

        return $this->respond(['message' => 'Token saved successfully'], 200);
    }

    private function getUserFromToken()
    {
        $authHeader = $this->request->getHeader('Authorization');
        if (!$authHeader) {
            return $this->failUnauthorized('Token não encontrado');
        }

        $token = str_replace('Bearer ', '', $authHeader->getValue());

        try {
            $decodedToken = JwtHelper::validateToken($token);
            return $decodedToken->data;
        } catch (\Exception $e) {
            return $this->fail('Token inválido: ' . $e->getMessage());
        }
    }
}
