<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NexoApiAuth
{
    /**
     * Handle an incoming request.
     *
     * Valida o token Bearer JWT do Nexo e extrai o store_id.
     * Anexa store_id e store ao request para uso nos controllers.
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticação não fornecido',
                'error' => 'unauthorized',
            ], 401);
        }

        $token = substr($authHeader, 7); // Remove "Bearer "

        try {
            // Decodificar payload do JWT (sem validar assinatura por enquanto)
            // Em produção, você deve validar a assinatura com a chave do Nexo
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new \Exception('Token JWT inválido');
            }

            $payload = json_decode(
                base64_decode(strtr($parts[1], '-_', '+/')),
                true
            );

            if (!$payload) {
                throw new \Exception('Payload do token inválido');
            }

            // Extrair store_id do payload (pode vir como storeId, store_id, ou iss)
            $storeId = $payload['storeId'] ?? $payload['store_id'] ?? $payload['iss'] ?? null;

            if (!$storeId) {
                Log::warning('Store ID não encontrado no token', ['payload' => $payload]);
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID não encontrado no token',
                    'error' => 'invalid_token',
                ], 401);
            }

            Log::info("NexoApiAuth: store_id extraído do token: {$storeId}");

            // Buscar store no banco de dados pelo store_id
            $store = Store::where('store_id', $storeId)->first();

            if (!$store) {
                Log::warning("NexoApiAuth: Loja não encontrada para store_id: {$storeId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Loja não encontrada. Execute a instalação do app primeiro.',
                    'error' => 'store_not_found',
                ], 404);
            }

            Log::info("NexoApiAuth: Loja encontrada - store_id: {$store->store_id}");

            // Anexar dados ao request para uso nos controllers
            $request->merge([
                'auth_store_id' => $store->store_id,
                'auth_store' => $store,
            ]);

            // Também disponibilizar via attributes para acesso mais direto
            $request->attributes->set('store', $store);
            $request->attributes->set('store_id', $store->store_id);

        } catch (\Exception $e) {
            Log::error('NexoApiAuth: Erro ao processar token', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticação inválido',
                'error' => 'invalid_token',
                'details' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
