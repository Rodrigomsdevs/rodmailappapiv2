<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailFcmTokenModel extends Model
{
    protected $table = 'email_fcm_tokens';
    protected $primaryKey = 'id';
    protected $allowedFields = ['token', 'user_id'];

    // Define the validation rules
    protected $validationRules = [
        'token' => 'required|string',
        'user_id' => 'permit_empty|integer'
    ];
}
