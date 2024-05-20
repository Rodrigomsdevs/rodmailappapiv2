<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EmailListModel;
use App\Helpers\JwtHelper;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Database\Database; // Certifique-se de importar o namespace Database

class EmailController extends ResourceController
{
    protected $emailListModel;

    public function __construct()
    {
        $this->emailListModel = new EmailListModel();
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

    public function listEmail()
    {
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;

        // Obter uma instância do banco de dados
        $db = \Config\Database::connect();

        // Consulta que junta email_list e email_info para obter o número de emails recebidos
        $emails = $db->table('email_list')
            ->select('email_list.id, email_list.email, COUNT(email_info.id) as email_count')
            ->join('email_info', 'email_info.to_address = email_list.email', 'left')
            ->where('email_list.id_usuario', $userId)
            ->groupBy('email_list.id, email_list.email')
            ->orderBy('email_count', 'DESC')
            ->get()
            ->getResultArray();

        return $this->respond(['emails' => $emails], ResponseInterface::HTTP_OK);
    }

    public function showEmail($emailId)
    {
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;

        // Obter uma instância do banco de dados
        $db = \Config\Database::connect();

        // Consulta que junta email_list e email_info para obter o número de emails recebidos
        $emails = $db->table('email_info')
            ->select('email_info.*, email_content.*')
            ->join('email_content', 'email_content.email_id = email_info.id', 'left')
            ->join('email_list', 'email_info.to_address = email_list.email', 'left')
            ->where('email_list.id_usuario', $userId)
            ->where('email_info.id', $emailId)
            ->get()
            ->getResultArray();

        return $this->respond(['data' => $emails], ResponseInterface::HTTP_OK);
    }

    public function deleteAccountEmail($id)
    {
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;

        // Obter uma instância do banco de dados
        $db = \Config\Database::connect();

        // Verificar se o email pertence ao usuário
        $email = $db->table('email_list')
            ->where('id', $id)
            ->where('id_usuario', $userId)
            ->get()
            ->getRow();

        if (!$email) {
            return $this->respond(['message' => 'Email não encontrado ou não autorizado'], ResponseInterface::HTTP_NOT_FOUND);
        }

        // Deletar o email
        $db->table('email_list')
            ->where('id', $id)
            ->delete();

        return $this->respond(['message' => 'Email deletado com sucesso'], ResponseInterface::HTTP_OK);
    }

    public function addEmail()
    {
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;
        $email = $this->request->getVar('email');

        if (!$email) {
            return $this->fail('Email não fornecido', ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Obter uma instância do banco de dados
        $db = \Config\Database::connect();

        // Verificar se o email já existe para outro usuário
        $existingEmail = $db->table('email_list')
            ->where('email', $email)
            ->get()
            ->getRow();

        if ($existingEmail) {
            return $this->respond(['message' => 'Email já está em uso por outro usuário'], ResponseInterface::HTTP_CONFLICT);
        }

        // Adicionar o novo email para o usuário
        $db->table('email_list')->insert([
            'id_usuario' => $userId,
            'email' => $email,
        ]);

        // Retornar o email com o count inicial
        $newEmail = [
            'id' => $db->insertID(),
            'email' => $email,
            'email_count' => 0 // Iniciando com 0 emails recebidos
        ];

        return $this->respond(['message' => 'Email adicionado com sucesso', 'email' => $newEmail], ResponseInterface::HTTP_CREATED);
    }

    public function markAllAsRead()
    {
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;
        $emailId = $this->request->getVar('id');

        if (!$emailId) {
            return $this->fail('ID do email não fornecido', ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Obter uma instância do banco de dados
        $db = \Config\Database::connect();

        // Obter o endereço de email a partir do ID
        $email = $db->table('email_list')
            ->select('email')
            ->where('id', $emailId)
            ->where('id_usuario', $userId)
            ->get()
            ->getRow();

        if (!$email) {
            return $this->fail('Email não encontrado ou não autorizado ' . $emailId . " | " . $userId, ResponseInterface::HTTP_NOT_FOUND);
        }

        // Atualizar todos os emails para marcar como lidos
        $db->table('email_info')
            ->where('to_address', $email->email)
            ->set('lido', 'S')
            ->update();

        return $this->respond(['message' => 'Todos os emails foram marcados como lidos'], ResponseInterface::HTTP_OK);
    }

    public function listUserEmails()
{
    $user = $this->getUserFromToken();
    if ($user instanceof ResponseInterface) {
        return $user; // Retorna a resposta de erro se a validação do token falhar
    }

    $userId = $user->id;
    $query = $this->request->getVar('query');
    $page = $this->request->getVar('page') ?? 1;
    $limit = $this->request->getVar('limit') ?? 20;
    $offset = ($page - 1) * $limit;

    // Conectar ao banco de dados
    $db = \Config\Database::connect();

    // Primeiro, obtenha a lista de e-mails associados ao usuário
    $emailList = $db->table('email_list')
        ->select('email')
        ->where('id_usuario', $userId)
        ->get()
        ->getResultArray();

    $emails = [];
    $totalEmails = 0;

    if (!empty($emailList)) {
        $emailAddresses = array_column($emailList, 'email');

        $builder = $db->table('email_info')
            ->select('email_info.*, email_content.html_content, email_content.text_content')
            ->join('email_content', 'email_content.email_id = email_info.id', 'left')
            ->whereIn('email_info.to_address', $emailAddresses)
            ->where('email_info.deleted_at', null)
            ->orderBy('email_info.date_received', 'DESC');

        if ($query) {
            $builder->groupStart()
                ->like('email_info.subject', $query)
                ->orLike('email_info.from_name', $query)
                ->orLike('email_info.html_preview', $query)
                ->orLike('email_content.html_content', $query)
                ->orLike('email_content.text_content', $query)
                ->groupEnd();
        }

        // Primeiro, obtenha o total de emails correspondentes à consulta
        $totalEmailsBuilder = clone $builder;
        $totalEmails = $totalEmailsBuilder->countAllResults(false);

        // Agora obtenha os emails paginados
        $emails = $builder->limit($limit, $offset)->get()->getResultArray();
    }

    return $this->respond(['emails' => $emails, 'totalEmails' => $totalEmails], ResponseInterface::HTTP_OK);
}


    public function deleteEmail($id)
    {
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;

        // Obter uma instância do banco de dados
        $db = \Config\Database::connect();

        // Subconsulta para verificar se o endereço de email pertence ao usuário
        $subQuery = $db->table('email_list')
            ->select('email')
            ->where('id_usuario', $userId)
            ->getCompiledSelect();

        // Verificar se o email pertence ao usuário
        $email = $db->table('email_info')
            ->where('id', $id)
            ->where("to_address IN ($subQuery)", null, false)
            ->get()
            ->getRow();

        if (!$email) {
            return $this->respond(['message' => 'Email não encontrado ou não autorizado'], ResponseInterface::HTTP_NOT_FOUND);
        }

        // Deletar o email (soft delete)
        $emailModel = new \App\Models\EmailInfoModel();
        if ($emailModel->delete($id)) {
            return $this->respond(['message' => 'Email deletado com sucesso'], ResponseInterface::HTTP_OK);
        } else {
            return $this->respond(['message' => 'Erro ao deletar o email'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function markAsRead($id)
    {
        $user = $this->getUserFromToken();
        if ($user instanceof ResponseInterface) {
            return $user; // Retorna a resposta de erro se a validação do token falhar
        }

        $userId = $user->id;

        // Obter uma instância do banco de dados
        $db = \Config\Database::connect();

        // Subconsulta para verificar se o endereço de email pertence ao usuário
        $subQuery = $db->table('email_list')
            ->select('email')
            ->where('id_usuario', $userId)
            ->getCompiledSelect();

        // Verificar se o email pertence ao usuário
        $email = $db->table('email_info')
            ->where('id', $id)
            ->where("to_address IN ($subQuery)", null, false)
            ->get()
            ->getRow();

        if (!$email) {
            return $this->respond(['message' => 'Email não encontrado ou não autorizado'], ResponseInterface::HTTP_NOT_FOUND);
        }

        // Alternar o status de leitura do email
        $newStatus = ($email->lido === 'S') ? 'N' : 'S';
        $emailModel = new \App\Models\EmailInfoModel();

        if ($emailModel->update($id, ['lido' => $newStatus])) {
            $message = ($newStatus === 'S') ? 'Email marcado como lido com sucesso' : 'Email marcado como não lido com sucesso';
            return $this->respond(['message' => $message, 'lido' => $newStatus], ResponseInterface::HTTP_OK);
        } else {
            return $this->respond(['message' => 'Erro ao atualizar o status do email', 'lido' => $newStatus], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function sendNotification($fcmToken, $title, $body, $emailId, $id)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $fields = [
            'to' => $fcmToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default'
            ],
            'data' => [
                'email_id' => $emailId, // Adiciona o email_id nos dados da notificação
                'id' => $id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK' // Adiciona click_action para tratamento em segundo plano
            ]
        ];

        $serverKey = 'AAAAOCPXQCA:APA91bHumktRQt7W-xiKz2I1xAxaKD3Bp5-endrk76NK2m_dN1W-x9hRC7QKqIGTIJFG3Jez4Ndno4dKFJxFaxwH-e6G6uRXjUt7zDMnABSI3xdgyUG1YrNuH6uOuJovi2MDs9AeEl3v';

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }


    // Test function to send a notification
    public function test($fcmToken, $emailId)
    {
        $retorno = $this->sendNotification($fcmToken, 'Teste', 'Esta é uma notificação de teste', $emailId, $emailId);

        return $this->respond(['retorno' => $retorno], ResponseInterface::HTTP_OK);
    }
}
