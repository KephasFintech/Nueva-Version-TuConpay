<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Autenticación
 *
 * Endpoints para login, refresh de token, logout y perfil del usuario autenticado.
 */
class AuthController extends ApiController
{
    /**
     * Login
     *
     * Autentica al usuario y retorna un JWT de acceso y un token de refresco.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login exitoso",
     *   "data": {
     *     "access_token": "eyJ...",
     *     "refresh_token": "eyJ...",
     *     "token_type": "bearer",
     *     "expires_in": 3600
     *   }
     * }
     * @response 401 {"success":false,"message":"Credenciales incorrectas"}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return $this->error('Credenciales incorrectas', 401);
        }

        return $this->success($this->buildTokenPayload($token), 'Login exitoso');
    }

    /**
     * Refresh Token
     *
     * Rota el JWT actual y emite un nuevo par de tokens.
     *
     * @authenticated
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            return $this->success($this->buildTokenPayload($token), 'Token renovado exitosamente');
        } catch (\Exception $e) {
            return $this->error('Token inválido o expirado', 401);
        }
    }

    /**
     * Logout
     *
     * Invalida el token actual (lo añade a la blacklist de JWT).
     *
     * @authenticated
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->success(null, 'Sesión cerrada exitosamente');
    }

    /**
     * Perfil del Usuario Autenticado
     *
     * Retorna los datos del usuario actual con sus roles y permisos.
     *
     * @authenticated
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user()->load([]);

        return $this->success([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'is_active'  => $user->is_active,
            'roles'      => $user->getRoleNames(),
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Construye el payload de respuesta de token.
     *
     * @return array<string, mixed>
     */
    private function buildTokenPayload(string $token): array
    {
        return [
            'access_token'  => $token,
            'token_type'    => 'bearer',
            'expires_in'    => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
