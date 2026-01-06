<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa criação de dispositivo com sucesso
     */
    public function test_authenticated_user_can_create_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/devices', [
            'name' => 'iPhone 13',
            'location' => 'Escritório',
            'purchase_date' => '2023-05-15',
            'in_use' => true
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Dispositivo criado com sucesso'
                 ]);

        $this->assertDatabaseHas('devices', [
            'name' => 'iPhone 13',
            'location' => 'Escritório',
            'user_id' => $user->id
        ]);
    }

    /**
     * Testa que campos obrigatórios não podem estar vazios
     */
    public function test_device_creation_requires_all_mandatory_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/devices', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'location', 'purchase_date']);
    }

    /**
     * Testa que data de compra não pode ser futura
     */
    public function test_device_purchase_date_cannot_be_in_future(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $futureDate = now()->addDays(10)->format('Y-m-d');

        $response = $this->postJson('/api/devices', [
            'name' => 'iPhone',
            'location' => 'Casa',
            'purchase_date' => $futureDate
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['purchase_date']);
    }

    /**
     * Testa que data de compra pode ser hoje
     */
    public function test_device_purchase_date_can_be_today(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/devices', [
            'name' => 'iPhone',
            'location' => 'Casa',
            'purchase_date' => now()->format('Y-m-d')
        ]);

        $response->assertStatus(201);
    }

    /**
     * Testa listagem de dispositivos
     */
    public function test_user_can_list_their_devices(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createDevice($user->id, 'Device 1', 'Escritório');
        $this->createDevice($user->id, 'Device 2', 'Casa');

        $response = $this->getJson('/api/devices');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'name', 'location', 'purchase_date', 'in_use', 'user_id']
                     ],
                     'meta' => ['current_page', 'per_page', 'total', 'last_page']
                 ]);
    }

    /**
     * Testa que usuário só vê seus próprios dispositivos
     */
    public function test_user_can_only_see_their_own_devices(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->createDevice($user1->id, 'User 1 Device', 'Escritório');
        $this->createDevice($user2->id, 'User 2 Device', 'Casa');

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/devices');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');

        $this->assertEquals('User 1 Device', $response->json('data.0.name'));
    }

    /**
     * Testa filtro por localização
     */
    public function test_can_filter_devices_by_location(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createDevice($user->id, 'Device Escritório', 'Escritório');
        $this->createDevice($user->id, 'Device Casa', 'Casa');

        $response = $this->getJson('/api/devices?location=Escritório');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Testa filtro por status in_use
     */
    public function test_can_filter_devices_by_in_use_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createDevice($user->id, 'Device Em Uso', 'Escritório', true);
        $this->createDevice($user->id, 'Device Disponível', 'Casa', false);

        $response = $this->getJson('/api/devices?in_use=1');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Testa filtro por faixa de datas
     */
    public function test_can_filter_devices_by_purchase_date_range(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        DB::table('devices')->insert([
            [
                'name' => 'Device 2022',
                'location' => 'Escritório',
                'purchase_date' => '2022-01-15',
                'in_use' => false,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Device 2023',
                'location' => 'Casa',
                'purchase_date' => '2023-06-20',
                'in_use' => false,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        $response = $this->getJson('/api/devices?purchase_date_start=2023-01-01&purchase_date_end=2023-12-31');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Testa ordenação por nome
     */
    public function test_can_sort_devices_by_name(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createDevice($user->id, 'Zebra Device', 'Casa');
        $this->createDevice($user->id, 'Apple Device', 'Escritório');

        $response = $this->getJson('/api/devices?sort_by=name&sort_order=asc');

        $response->assertStatus(200);
        $this->assertEquals('Apple Device', $response->json('data.0.name'));
    }

    /**
     * Testa atualização de dispositivo
     */
    public function test_user_can_update_their_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $deviceId = $this->createDevice($user->id, 'iPhone 12', 'Escritório');

        $response = $this->putJson("/api/devices/{$deviceId}", [
            'name' => 'iPhone 13 Pro',
            'location' => 'Casa'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Dispositivo atualizado com sucesso']);

        $this->assertDatabaseHas('devices', [
            'id' => $deviceId,
            'name' => 'iPhone 13 Pro',
            'location' => 'Casa'
        ]);
    }

    /**
     * Testa que usuário não pode atualizar dispositivo de outro
     */
    public function test_user_cannot_update_other_user_device(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $deviceId = $this->createDevice($user2->id, 'Device User 2', 'Casa');

        Sanctum::actingAs($user1);

        $response = $this->putJson("/api/devices/{$deviceId}", [
            'name' => 'Tentando Alterar'
        ]);

        $response->assertStatus(404);
    }

    /**
     * Testa toggle de status in_use
     */
    public function test_user_can_toggle_device_use_status(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $deviceId = $this->createDevice($user->id, 'iPhone', 'Escritório', false);

        $response = $this->patchJson("/api/devices/{$deviceId}/use");

        $response->assertStatus(200);

        // Verificar que mudou para true
        $device = DB::table('devices')->where('id', $deviceId)->first();
        $this->assertTrue((bool)$device->in_use);

        // Toggle novamente
        $response = $this->patchJson("/api/devices/{$deviceId}/use");

        $device = DB::table('devices')->where('id', $deviceId)->first();
        $this->assertFalse((bool)$device->in_use);
    }

    /**
     * Testa soft delete
     */
    public function test_user_can_soft_delete_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $deviceId = $this->createDevice($user->id, 'iPhone', 'Escritório');

        $response = $this->deleteJson("/api/devices/{$deviceId}");

        $response->assertStatus(200);

        // Verificar soft delete
        $device = DB::table('devices')->where('id', $deviceId)->first();
        $this->assertNotNull($device->deleted_at);
    }

    /**
     * Testa que dispositivo deletado não aparece na listagem
     */
    public function test_soft_deleted_device_does_not_appear_in_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $deviceId = $this->createDevice($user->id, 'iPhone', 'Escritório');

        // Deletar
        $this->deleteJson("/api/devices/{$deviceId}");

        // Listar
        $response = $this->getJson('/api/devices');

        $response->assertStatus(200)
                 ->assertJsonCount(0, 'data');
    }

    /**
     * Testa paginação
     */
    public function test_devices_are_paginated_correctly(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Criar 15 dispositivos
        for ($i = 1; $i <= 15; $i++) {
            $this->createDevice($user->id, "Device {$i}", 'Escritório');
        }

        // Página 1 (deve ter 10 itens)
        $response = $this->getJson('/api/devices?page=1');

        $response->assertStatus(200)
                 ->assertJsonCount(10, 'data')
                 ->assertJson([
                     'meta' => [
                         'total' => 15,
                         'per_page' => 10,
                         'current_page' => 1,
                         'last_page' => 2
                     ]
                 ]);

        // Página 2 (deve ter 5 itens)
        $response = $this->getJson('/api/devices?page=2');

        $response->assertStatus(200)
                 ->assertJsonCount(5, 'data');
    }

    /**
     * Testa que usuário não autenticado não pode acessar rotas
     */
    public function test_unauthenticated_user_cannot_access_devices(): void
    {
        $response = $this->getJson('/api/devices');
        $response->assertStatus(401);

        $response = $this->postJson('/api/devices', []);
        $response->assertStatus(401);

        $response = $this->putJson('/api/devices/1', []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/devices/1');
        $response->assertStatus(401);
    }

    /**
     * Helper: Criar dispositivo
     */
    private function createDevice($userId, $name, $location, $inUse = false)
    {
        return DB::table('devices')->insertGetId([
            'name' => $name,
            'location' => $location,
            'purchase_date' => '2023-01-01',
            'in_use' => $inUse,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}