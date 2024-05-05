<?php

namespace App\Models;

use App\Models\Client;
use App\Models\Department;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'id_number',
        'phone',
        'email',
        'password',
        'ni_number',
        'date_of_birth',
        'address_line1',
        'address_line2',
        'address_line3',
        'town',
        'postcode',
        'image',
        'department_id',
        'job_title',
        'employment_status',
        'joining_date',
        'reporting_to',
        'reporting_employee_id',
        'type',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'manager_id');
    }

    public function logComments()
    {
        return $this->hasMany(LogComment::class);
    }

    protected function type(): Attribute
    {
        return new Attribute(
            get: fn ($value) =>  ["user", "admin", "manager", "staff"][$value],
        );
    }

}
