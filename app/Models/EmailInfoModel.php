<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailInfoModel extends Model
{
    protected $table = 'email_info';
    protected $primaryKey = 'id';
    protected $allowedFields = ['from_address', 'from_name', 'to_address', 'to_name', 'subject', 'message_id', 'date_received', 'lido', 'important', 'html_preview'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $useSoftDeletes        = true;
}
