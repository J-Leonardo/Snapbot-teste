<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa registro de novo usuário com sucesso
     */
    public function test_user_can_register_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user' => ['id', 'name', 'email'],
                     'token'
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'joao@example.com',
            'name' => 'João Silva'
        ]);
    }

    /**
     * Testa registro sem nome
     */
    public function test_user_cannot_register_without_name(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /**
     * Testa registro com email inválido
     */
    public function test_user_cannot_register_with_invalid_email(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Teste',
            'email' => 'email-invalido',
            'password' => 'senha123',
            'password_confirmation' => 'senha123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Testa registro com senha muito curta
     */
    public function test_user_cannot_register_with_short_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => '123',
            'password_confirmation' => '123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Testa registro com senhas que não conferem
     */
    public function test_user_cannot_register_with_mismatched_passwords(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha456'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /**
     * Testa registro com email duplicado
     */
    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existente@example.com'
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Novo Usuário',
            'email' => 'existente@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Testa login com credenciais válidas
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'teste@example.com',
            'password' => Hash::make('senha123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste@example.com',
            'password' => 'senha123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user' => ['id', 'name', 'email'],
                     'token'
                 ]);
    }

    /**
     * Testa login com email inexistente
     */
    public function test_user_cannot_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'naoexiste@example.com',
            'password' => 'senha123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Testa login com senha incorreta
     */
    public function test_user_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'teste@example.com',
            'password' => Hash::make('senha123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste@example.com',
            'password' => 'senha-errada'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Testa logout de usuário autenticado
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * Testa rota /me para obter dados do usuário autenticado
     */
    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'user' => [
                         'id' => $user->id,
                         'name' => $user->name,
                         'email' => $user->email
                     ]
                 ]);
    }

    /**
     * Testa que usuário não autenticado não pode acessar /me
     */
    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
    }
}