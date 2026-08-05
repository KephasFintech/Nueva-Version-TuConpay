<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Ticket\StoreReceiptRequest;
use App\Models\ExchangeTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends ApiController
{
    /**
     * Subir comprobante a un ticket
     */
    public function store(StoreReceiptRequest $request, ExchangeTicket $exchangeTicket): JsonResponse
    {
        $file = $request->file('file');
        
        $path = $file->store("receipts/{$exchangeTicket->code}");

        $receipt = $exchangeTicket->receipts()->create([
            'uploader_id'   => Auth::id(),
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_type'     => $file->getMimeType(),
            'file_size'     => $file->getSize(),
            'notes'         => $request->input('notes'),
        ]);

        return $this->created($receipt, 'Comprobante subido exitosamente');
    }
}
