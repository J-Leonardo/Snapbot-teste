<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    /**
     * GET /api/devices?page=1
     * Listar dispositivos filtrados
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $perPage = 10;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        // Query base
        $query = DB::table('devices')
            ->where('user_id', $userId)
            ->whereNull('deleted_at');

        // Aplicar filtros
        if ($request->has('in_use')) {
            $query->where('in_use', $request->boolean('in_use'));
        }

        if ($request->has('location')) {
            $query->where('location', 'LIKE', '%' . $request->input('location') . '%');
        }
        // Filtro de data (faixa)
        if ($request->has('purchase_date_start')) {
            $query->where('purchase_date', '>=', $request->input('purchase_date_start'));
        }

        if ($request->has('purchase_date_end')) {
            $query->where('purchase_date', '<=', $request->input('purchase_date_end'));
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Validar campos permitidos para ordenação
        $allowedSorts = ['name', 'location', 'purchase_date', 'in_use', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Contar total
        $total = $query->count();

        // Buscar dados paginados
        $devices = $query
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'data' => $devices,
            'meta' => [
                'current_page' => (int) $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
            ]
        ]);
    }

    /**
     * POST /api/devices
     * Criar novo dispositivo
     */
    public function store(StoreDeviceRequest $request)
    {
        $userId = $request->user()->id;

        $deviceId = DB::table('devices')->insertGetId([
            'name' => $request->name,
            'location' => $request->location,
            'purchase_date' => $request->purchase_date,
            'in_use' => $request->input('in_use', false),
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $device = DB::table('devices')->where('id', $deviceId)->first();

        return response()->json([
            'message' => 'Dispositivo criado com sucesso',
            'data' => $device
        ], 201);
    }

    /**
     * PUT /api/devices/{id}
     * Atualizar dispositivo
     */
    public function update(UpdateDeviceRequest $request, $id)
    {
        $userId = $request->user()->id;

        // Verificar se o dispositivo existe e pertence ao usuário
        $device = DB::table('devices')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$device) {
            return response()->json([
                'message' => 'Dispositivo não encontrado'
            ], 404);
        }

        // Preparar dados para atualização
        $updateData = array_filter([
            'name' => $request->input('name'),
            'location' => $request->input('location'),
            'purchase_date' => $request->input('purchase_date'),
            'in_use' => $request->input('in_use'),
            'updated_at' => now(),
        ], function ($value) {
            return !is_null($value);
        });

        // Atualizar
        DB::table('devices')
            ->where('id', $id)
            ->update($updateData);

        // Buscar dispositivo atualizado
        $updatedDevice = DB::table('devices')->where('id', $id)->first();

        return response()->json([
            'message' => 'Dispositivo atualizado com sucesso',
            'data' => $updatedDevice
        ]);
    }

    /**
     * DELETE /api/devices/{id}
     * Excluir dispositivo (Soft Delete)
     */
    public function destroy(Request $request, $id)
    {
        $userId = $request->user()->id;

        // Verificar se o dispositivo existe e pertence ao usuário
        $device = DB::table('devices')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$device) {
            return response()->json([
                'message' => 'Dispositivo não encontrado'
            ], 404);
        }

        // Soft Delete
        DB::table('devices')
            ->where('id', $id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Dispositivo excluído com sucesso'
        ]);
    }

    /**
     * PATCH /api/devices/{id}/use
     * Marcar/desmarcar como em uso
     */
    public function toggleUse(Request $request, $id)
    {
        $userId = $request->user()->id;

        // Verificar se o dispositivo existe e pertence ao usuário
        $device = DB::table('devices')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first();

        if (!$device) {
            return response()->json([
                'message' => 'Dispositivo não encontrado'
            ], 404);
        }

        // Inverter o status de in_use
        $newStatus = !$device->in_use;

        DB::table('devices')
            ->where('id', $id)
            ->update([
                'in_use' => $newStatus,
                'updated_at' => now(),
            ]);

        // Buscar dispositivo atualizado
        $updatedDevice = DB::table('devices')->where('id', $id)->first();

        return response()->json([
            'message' => 'Status atualizado com sucesso',
            'data' => $updatedDevice
        ]);
    }
}