<?php

namespace App\Models;

use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaskAssign extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        'staff_id',
        'task_id',
        'client_id',
        'note',
        'date',
        'staff_notification',
        'admin_notification',
        'status',
        'created_by',
        'updated_by',
    ];
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_staff', 'task_assign_id', 'task_id');
    }

}
