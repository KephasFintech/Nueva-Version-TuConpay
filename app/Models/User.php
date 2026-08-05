<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ─── JWT ──────────────────────────────────────────────────────────────────

    /**
     * Identificador único para el subject del JWT (sub claim).
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Claims custom añadidos al payload del JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'roles' => $this->getRoleNames()->toArray(),
            'email' => $this->email,
        ];
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    /**
     * Tickets donde este usuario es el agente ATC.
     */
    public function ticketsAsAtc()
    {
        return $this->hasMany(ExchangeTicket::class, 'atc_user_id');
    }

    /**
     * Tickets donde este usuario es el cliente.
     */
    public function ticketsAsClient()
    {
        return $this->hasMany(ExchangeTicket::class, 'client_id');
    }

    /**
     * Tickets donde este usuario es el broker.
     */
    public function ticketsAsBroker()
    {
        return $this->hasMany(ExchangeTicket::class, 'broker_id');
    }

    /**
     * Notificaciones internas del usuario.
     */
    public function internalNotifications()
    {
        return $this->hasMany(InternalNotification::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Verifica si el usuario tiene un rol específico del sistema.
     */
    public function hasSystemRole(UserRole $role): bool
    {
        return $this->hasRole($role->value);
    }

    /**
     * Scope: solo usuarios activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
