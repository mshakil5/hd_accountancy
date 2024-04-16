<?php

namespace App\Models;

use App\Models\TaskAssign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'task',
        'note',
        'date',
        'status'
    ];

    public function taskAssigns()
    {
        return $this->belongsToMany(TaskAssign::class, 'task_staff', 'task_id', 'task_assign_id');
    }

}
