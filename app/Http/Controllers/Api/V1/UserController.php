<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Usuarios & Agentes
 *
 * Gestión de usuarios del sistema (todos los roles).
 * El filtro por rol se realiza mediante el query param `role`.
 */
class UserController extends ApiController
{
    /**
     * Listar Usuarios
     *
     * Lista todos los usuarios. Puede filtrarse por rol, estado activo, y búsqueda por nombre/email.
     *
     * @authenticated
     *
     * @queryParam role string Filtrar por rol. Ejemplo: broker, atc, client.
     * @queryParam is_active boolean Filtrar por estado activo. Ejemplo: 1
     * @queryParam search string Búsqueda por nombre o email.
     * @queryParam per_page integer Resultados por página (default: 15).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->input('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate($request->input('per_page', 15));

        return $this->paginate($users);
    }

    /**
     * Crear Usuario
     *
     * Crea un nuevo usuario y le asigna el rol indicado.
     *
     * @authenticated
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name'      => $request->input('name'),
            'email'     => $request->input('email'),
            'password'  => Hash::make($request->input('password')),
            'phone'     => $request->input('phone'),
            'is_active' => $request->input('is_active', true),
        ]);

        $user->assignRole($request->input('role'));

        return $this->created($this->formatUser($user), 'Usuario creado exitosamente');
    }

    /**
     * Ver Usuario
     *
     * Retorna el detalle de un usuario por ID.
     *
     * @authenticated
     */
    public function show(User $user): JsonResponse
    {
        return $this->success($this->formatUser($user->load('roles')));
    }

    /**
     * Actualizar Usuario
     *
     * Actualiza los datos de un usuario. Solo los campos enviados se modifican.
     *
     * @authenticated
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->only(['name', 'email', 'phone', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        return $this->success($this->formatUser($user->fresh('roles')), 'Usuario actualizado exitosamente');
    }

    /**
     * Eliminar Usuario
     *
     * Desactiva el usuario (soft delete lógico via is_active = false).
     *
     * @authenticated
     */
    public function destroy(User $user): JsonResponse
    {
        // No eliminar al super_admin
        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            return $this->error('No se puede eliminar al Super Administrador', 403);
        }

        $user->update(['is_active' => false]);

        return $this->success(null, 'Usuario desactivado exitosamente');
    }

    /**
     * Formatea el usuario para la respuesta JSON.
     *
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'is_active'  => $user->is_active,
            'roles'      => $user->getRoleNames(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
