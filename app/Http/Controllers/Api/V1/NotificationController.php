<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\InternalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends ApiController
{
    /**
     * Listar Notificaciones Internas
     */
    public function index(Request $request): JsonResponse
    {
        $query = InternalNotification::where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        return $this->paginate($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Marcar notificación como leída
     */
    public function markRead(InternalNotification $notification): JsonResponse
    {
        // Validar que la notificación pertenece al usuario actual
        if ($notification->user_id !== Auth::id()) {
            return $this->error('No autorizado', 403);
        }

        $notification->markAsRead();

        return $this->success(null, 'Notificación marcada como leída');
    }
}
