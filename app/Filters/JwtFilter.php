<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Helpers\JwtHelper;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeader('Authorization');
        if (!$header) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED, 'No token provided');
        }

        $token = $header->getValue();
        $decoded = JwtHelper::decodeToken($token);

        if (!$decoded) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED, 'Invalid token');
        }

        // Pode adicionar informações do usuário na requisição, se necessário
        $request->user = $decoded->data;

        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Não faz nada após a resposta
    }
}
