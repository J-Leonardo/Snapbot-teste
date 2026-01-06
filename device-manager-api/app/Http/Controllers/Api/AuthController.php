<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Registro de usuário
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Criar usuário usando Eloquent
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Criar token usando Sanctum
            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('Usuário registrado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuário registrado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro no registro: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Buscar e testar existência de usuário
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Tentativa de login com credenciais inválidas', [
                    'email' => $request->email
                ]);
                
                return response()->json([
                    'errors' => [
                        'email' => ['As credenciais fornecidas são inválidas.']
                    ]
                ], 422);
            }

            // Criar token usando Sanctum
            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('Login realizado com sucesso', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro no login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        try {
            // Deletar token atual
            $request->user()->currentAccessToken()->delete();

            Log::info('Logout realizado', [
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro no logout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao fazer logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retornar usuário autenticado
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro na rota /me: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar usuário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rota de debug, apenas para teste (REMOVER REMOVER REMOVER REMOVER REMOVER REMOVER REMOVER REMOVER REMOVER REMOVER)
     */
    public function debug(Request $request)
    {
        try {
            $token = $request->bearerToken();
            
            $debugInfo = [
                'sanctum_config' => [
                    'guard' => config('auth.defaults.guard'),
                    'api_driver' => config('auth.guards.api.driver'),
                ],
                'headers' => [
                    'authorization' => $request->header('Authorization'),
                    'content_type' => $request->header('Content-Type'),
                    'accept' => $request->header('Accept'),
                ],
                'token_info' => [
                    'has_bearer_token' => $token ? 'sim' : 'não',
                    'token_length' => $token ? strlen($token) : 0,
                    'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
                ],
                'user_info' => [
                    'is_authenticated' => $request->user() ? 'sim' : 'não',
                    'user_id' => $request->user()->id ?? null,
                    'user_name' => $request->user()->name ?? null,
                ],
                'database_check' => [
                    'users_count' => User::count(),
                    'tokens_count' => \Laravel\Sanctum\PersonalAccessToken::count(),
                ],
                'middleware' => [
                    'current_middleware' => $request->route() ? $request->route()->middleware() : [],
                ]
            ];

            if ($token) {
                // Verificar se o token existe no banco
                $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                
                $debugInfo['token_validation'] = [
                    'token_found_in_db' => $tokenModel ? 'sim' : 'não',
                    'token_user_id' => $tokenModel->tokenable_id ?? null,
                    'token_name' => $tokenModel->name ?? null,
                    'token_created_at' => $tokenModel->created_at ?? null,
                ];
            }

            return response()->json([
                'success' => true,
                'debug' => $debugInfo
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro no debug',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}