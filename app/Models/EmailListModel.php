<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailListModel extends Model
{
    protected $table      = 'email_list';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['id_usuario', 'email'];

    protected $useTimestamps = true;
}
