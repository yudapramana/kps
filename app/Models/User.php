<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    //     'role',
    //     'avatar',
    //     'organization_id',
    // ];

    protected $guarded = [];

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
        'password' => 'hashed',
    ];

    protected $appends = [
        // 'formatted_created_at',
        'role_names',
        'permissions'
    ];

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format(setting('date_format'));
    }

    public function rolename(): Attribute
    {
        return Attribute::make(
            get: fn($value) => RoleType::from($value)->name,
        );
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function getPermissionsAttribute()
    {
        if (!$this->role) return collect();

        return $this->role->permissions->pluck('slug');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role && in_array($this->role->slug, ['superadmin'])) {
            // SUPERADMIN selalu boleh
            return true;
        }

        return $this->permissions->contains($permission);
    }

    public function getAvatarAttribute(){
        if($this->attributes['avatar']){
            $separator = '/upload/';
            $exp = explode($separator, $this->attributes['avatar']);
            return $exp[0] . '/upload/ar_1.0,c_fill,f_avif,q_5/' . $exp[1];
        } else {
            return "http://res.cloudinary.com/kemenagpessel/image/upload/v1709086972/profile_picture_pegawai/ijf9mhs8e1m2mjjgz69l.png";
        }
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function assignRole($roleName)
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    // Check if user has a specific role
    public function hasRole($roleName)
    {
        return $this->roles->contains('name', $roleName);
    }

    // Check if user has any of the given roles (array)
    public function hasAnyRole(array $roleNames)
    {
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    public function getRoleNamesAttribute(): array
    {
        $roleNames = $this->roles->pluck('name')->toArray();

        if (!in_array('USER', $roleNames)) {
            $roleNames[] = 'USER';
        }

        return $roleNames;
    }

}
