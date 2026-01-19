<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NuvemshopService
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $tokenUrl = 'https://www.nuvemshop.com.br/apps/authorize/token';

    protected string $apiBaseUrl = 'https://api.nuvemshop.com.br/2025-03';

    public function __construct()
    {
        $this->clientId = config('services.nuvemshop.client_id');
        $this->clientSecret = config('services.nuvemshop.client_secret');
    }

    /**
     * Get categories from Nuvemshop API using stored access token
     */
    public function getCategories(?string $storeId = null): array
    {
        try {
            // Obter a loja (se não informado, usar a primeira)
            $store = $storeId 
                ? Store::where('store_id', $storeId)->first()
                : Store::first();

            if (!$store) {
                return [
                    'success' => false,
                    'message' => 'Nenhuma loja configurada. Execute a instalação primeiro.',
                    'data' => [],
                ];
            }

            Log::info('Buscando categorias para store_id: ' . $store->store_id);

            // Fazer requisição à API da Nuvemshop
            $response = Http::withHeaders([
                'Authentication' => 'bearer ' . $store->access_token,
                'User-Agent' => 'Nuvemshop-Category-Description-App',
                'Content-Type' => 'application/json',
            ])->get(
                $this->apiBaseUrl . '/' . $store->store_id . '/categories',
                [
                    'page' => 1,
                    'per_page' => 100,
                    'fields' => 'id,name,description,handle,subcategories',
                ]
            );

            if (!$response->successful()) {
                Log::error('Erro ao buscar categorias da Nuvemshop', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Erro ao buscar categorias: ' . $response->body(),
                    'data' => [],
                ];
            }

            $categories = $response->json();

            return [
                'success' => true,
                'data' => $categories,
                'message' => 'Categorias obtidas com sucesso',
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao buscar categorias: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao buscar categorias: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Update category description in Nuvemshop
     * Sincroniza a descrição mantendo TODOS os outros campos (name, etc)
     */
    public function updateCategory(string $storeId, string $categoryId, string $htmlDescription): array
    {
        try {
            $store = Store::where('store_id', $storeId)->first();

            if (!$store) {
                return [
                    'success' => false,
                    'message' => 'Loja não encontrada.',
                ];
            }

            Log::info("Atualizando descrição da categoria $categoryId para loja $storeId");

            // GET para pegar dados atuais
            $getResponse = Http::withHeaders([
                'Authentication' => 'bearer ' . $store->access_token,
                'User-Agent' => 'Nuvemshop-Category-Description-App',
                'Content-Type' => 'application/json',
            ])->get(
                $this->apiBaseUrl . '/' . $store->store_id . '/categories/' . $categoryId
            );

            if (!$getResponse->successful()) {
                Log::error('Erro ao buscar categoria', [
                    'status' => $getResponse->status(),
                    'body' => $getResponse->body(),
                ]);
                return [
                    'success' => false,
                    'message' => 'Erro ao buscar categoria: ' . $getResponse->body(),
                ];
            }

            $categoryData = $getResponse->json();

            // Preparar payload com apenas os campos necessários
            $updatePayload = [
                'name' => $categoryData['name'] ?? [],
                'description' => [
                    'pt' => $htmlDescription,
                    'es' => $htmlDescription,
                    'en' => $htmlDescription,
                ],
            ];

            Log::info("Payload para atualizar categoria", [
                'categoryId' => $categoryId,
                'name' => $updatePayload['name'],
                'description_pt' => substr($htmlDescription, 0, 50) . '...',
            ]);

            // PUT com payload preparado
            $putResponse = Http::withHeaders([
                'Authentication' => 'bearer ' . $store->access_token,
                'User-Agent' => 'Nuvemshop-Category-Description-App',
                'Content-Type' => 'application/json',
            ])->put(
                $this->apiBaseUrl . '/' . $store->store_id . '/categories/' . $categoryId,
                $updatePayload
            );

            if (!$putResponse->successful()) {
                Log::error('Erro ao atualizar categoria na Nuvemshop', [
                    'status' => $putResponse->status(),
                    'body' => $putResponse->body(),
                    'categoryId' => $categoryId,
                    'payload' => $updatePayload,
                ]);

                return [
                    'success' => false,
                    'message' => 'Erro ao atualizar na Nuvemshop: ' . $putResponse->body(),
                ];
            }

            Log::info("Categoria $categoryId atualizada com sucesso na Nuvemshop");

            return [
                'success' => true,
                'data' => $putResponse->json(),
                'message' => 'Descrição sincronizada com sucesso na Nuvemshop',
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar categoria: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao atualizar categoria: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Authorize app installation with Nuvemshop
     */
    public function authorize(string $code): array
    {
        try {
            Log::info('Tentando autorizar com código: '.substr($code, 0, 10).'...');

            $response = Http::asForm()->post($this->tokenUrl, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            if (! $response->successful()) {
                Log::error('Erro na autorização Nuvemshop', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Falha na autorização: '.$response->body(),
                    'status' => $response->status(),
                ];
            }

            $data = $response->json();

            if (! isset($data['access_token'])) {
                Log::error('Token não recebido na resposta', $data);

                return [
                    'success' => false,
                    'message' => 'Falha na autorização: Token não recebido',
                    'status' => 400,
                ];
            }

            // Armazenar tokens na tabela stores
            $storeId = $data['user_id'] ?? $data['store_id'] ?? null;
            if (!$storeId) {
                Log::error('Store ID não encontrado na resposta', $data);
                return [
                    'success' => false,
                    'message' => 'Store ID não fornecido',
                    'status' => 400,
                ];
            }

            Store::updateOrCreate(
                ['store_id' => $storeId],
                [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_expires_at' => isset($data['expires_in']) 
                        ? now()->addSeconds($data['expires_in'])
                        : null,
                    'store_data' => $data,
                ]
            );

            Log::info('Token recebido e armazenado com sucesso para store: '.$storeId);

            return [
                'success' => true,
                'data' => $data,
                'message' => 'Autorização realizada com sucesso',
            ];
        } catch (\Exception $e) {
            Log::error('Erro na autorização Nuvemshop: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro interno: '.$e->getMessage(),
                'status' => 500,
            ];
        }
    }
}
