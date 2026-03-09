<?php

namespace App\Http\Controllers;

use App\Models\CategoryDescription;
use App\Services\NuvemshopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar descrições de categorias
 * Todas as operações são feitas diretamente na API da Nuvemshop
 * O store_id é extraído do token JWT pelo middleware NexoApiAuth
 */
class DescriptionController extends Controller
{
    protected NuvemshopService $nuvemshopService;

    public function __construct(NuvemshopService $nuvemshopService)
    {
        $this->nuvemshopService = $nuvemshopService;
    }

    /**
     * Helper para obter o store_id do request (injetado pelo middleware)
     */
    private function getStoreId(Request $request): ?string
    {
        return $request->attributes->get('store_id') ?? $request->input('auth_store_id');
    }

    /**
     * Get all categories from Nuvemshop with their descriptions
     */
    public function index(Request $request)
    {
        try {
            $storeId = $this->getStoreId($request);
            Log::info("DescriptionController@index - store_id: {$storeId}");
            
            $result = $this->nuvemshopService->getCategories($storeId);
            
            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => 'Categories retrieved successfully from Nuvemshop'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error fetching categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific category by ID from Nuvemshop
     */
    public function show(Request $request, $id)
    {
        try {
            $storeId = $this->getStoreId($request);
            $result = $this->nuvemshopService->getCategory($storeId, $id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => $result['message'] ?? 'Category not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => 'Category retrieved successfully from Nuvemshop'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error fetching category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category by category ID from local database or Nuvemshop
     * Returns data in the format expected by the frontend
     */
    public function getByCategory(Request $request, $categoryId)
    {
        try {
            $storeId = $this->getStoreId($request);
            Log::info("DescriptionController@getByCategory - store_id: {$storeId}, category_id: {$categoryId}");
            
            // Verificar se existe descrição local
            $localDescription = CategoryDescription::where('category_id', $categoryId)->first();
            if ($localDescription) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $localDescription->id,
                        'category_id' => $localDescription->category_id,
                        'name' => null,
                        'content' => $localDescription->content,
                        'html_content' => $localDescription->html_content,
                    ],
                    'message' => 'Category retrieved successfully from local database'
                ]);
            }
            
            // Se não encontrar localmente, buscar na Nuvemshop
            $result = $this->nuvemshopService->getCategory($storeId, $categoryId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => $result['message'] ?? 'Category not found'
                ], 404);
            }

            $category = $result['data'];
            
            // Extrair descrição do objeto da categoria (formato i18n da Nuvemshop)
            $description = '';
            if (isset($category['description'])) {
                if (is_array($category['description'])) {
                    $description = $category['description']['pt'] 
                        ?? $category['description']['es'] 
                        ?? $category['description']['en'] 
                        ?? '';
                } else {
                    $description = $category['description'];
                }
            }

            // Retornar no formato esperado pelo frontend
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category['id'],
                    'category_id' => $category['id'],
                    'name' => $category['name'] ?? null,
                    'content' => strip_tags($description),
                    'html_content' => $description,
                ],
                'message' => 'Category retrieved successfully from Nuvemshop'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error fetching category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create/Update category description in local database
     * No longer syncs with Nuvemshop API, only saves locally
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'category_id' => 'required|string',
                'content' => 'required|string',
                'html_content' => 'required|string',
            ]);

            $storeId = $this->getStoreId($request);
            Log::info("DescriptionController@store - store_id: {$storeId}");

            $categoryDescription = CategoryDescription::updateOrCreate(
                ['category_id' => $request->input('category_id')],
                [
                    'content' => $request->input('content'),
                    'html_content' => $request->input('html_content'),
                ]
            );

            // Salvar também como metafield na Nuvemshop
            $metafieldResult = $this->nuvemshopService->saveMetafield(
                $storeId,
                $request->input('category_id'),
                $request->input('html_content')
            );

            if (!$metafieldResult['success']) {
                Log::warning("Falha ao salvar metafield", [
                    'category_id' => $request->input('category_id'),
                    'message' => $metafieldResult['message']
                ]);
                // Não retornamos erro aqui - a descrição foi salva localmente mesmo se o metafield falhar
            }

            return response()->json([
                'success' => true,
                'data' => $categoryDescription->toArray(),
                'message' => 'Description saved successfully in local database and Nuvemshop metafield'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error creating description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update category description in local database
     * No longer syncs with Nuvemshop API, only saves locally
     * The $id parameter is the category_id
     */
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'content' => 'required|string',
                'html_content' => 'required|string',
            ]);

            $storeId = $this->getStoreId($request);
            Log::info("DescriptionController@update - store_id: {$storeId}, category_id: {$id}");

            $categoryDescription = CategoryDescription::updateOrCreate(
                ['category_id' => $id],
                [
                    'content' => $request->input('content'),
                    'html_content' => $request->input('html_content'),
                ]
            );

            // Salvar também como metafield na Nuvemshop
            $metafieldResult = $this->nuvemshopService->saveMetafield(
                $storeId,
                $id,
                $request->input('html_content')
            );

            if (!$metafieldResult['success']) {
                Log::warning("Falha ao salvar metafield", [
                    'category_id' => $id,
                    'message' => $metafieldResult['message']
                ]);
                // Não retornamos erro aqui - a descrição foi salva localmente mesmo se o metafield falhar
            }

            return response()->json([
                'success' => true,
                'data' => $categoryDescription->toArray(),
                'message' => 'Description saved successfully in local database and Nuvemshop metafield'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error updating description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all categories from Nuvemshop
     */
    public function getCategories(Request $request)
    {
        try {
            $storeId = $this->getStoreId($request);
            Log::info("DescriptionController@getCategories - store_id: {$storeId}");
            
            $result = $this->nuvemshopService->getCategories($storeId);
            
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error fetching categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete category description from local database
     */
    public function destroy(Request $request, $id)
    {
        try {
            $storeId = $this->getStoreId($request);
            Log::info("DescriptionController@destroy - store_id: {$storeId}, category_id: {$id}");
            
            $categoryDescription = CategoryDescription::where('category_id', $id)->first();

            if (!$categoryDescription) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Description not found'
                ], 404);
            }

            $categoryDescription->delete();

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Description deleted successfully from local database'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error deleting description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get description by category ID (Public endpoint for frontend consumption)
     * Fetches from local database first, falls back to Nuvemshop API if not found locally
     * Requer storeId na URL pois é endpoint público (sem autenticação JWT)
     */
    public function getCategoryDescription($storeId, $categoryId)
    {
        try {
            Log::info("DescriptionController@getCategoryDescription (public) - store_id: {$storeId}, category_id: {$categoryId}");
            
            // Verificar se existe descrição local
            $localDescription = CategoryDescription::where('category_id', $categoryId)->first();
            if ($localDescription) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $localDescription->id,
                        'category_id' => $localDescription->category_id,
                        'name' => null,
                        'content' => $localDescription->content,
                        'html_content' => $localDescription->html_content,
                    ],
                    'message' => 'Description retrieved successfully from local database'
                ]);
            }
            
            // Se não encontrar localmente, buscar na Nuvemshop
            $result = $this->nuvemshopService->getCategory($storeId, $categoryId);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Category not found',
                    'category_id' => $categoryId,
                ], 404);
            }

            $category = $result['data'];
            
            // Extrair descrição do objeto da categoria
            $description = '';
            if (isset($category['description'])) {
                if (is_array($category['description'])) {
                    $description = $category['description']['pt'] 
                        ?? $category['description']['es'] 
                        ?? $category['description']['en'] 
                        ?? '';
                } else {
                    $description = $category['description'];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category['id'],
                    'category_id' => $category['id'],
                    'name' => $category['name'] ?? null,
                    'content' => strip_tags($description),
                    'html_content' => $description,
                ],
                'message' => 'Description retrieved successfully from Nuvemshop'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error retrieving description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all descriptions organized by category (Public endpoint for bulk consumption)
     * Fetches local descriptions, combined with Nuvemshop categories
     * Requer storeId na URL pois é endpoint público (sem autenticação JWT)
     */
    public function getCategoriesDescriptions($storeId)
    {
        try {
            Log::info("DescriptionController@getCategoriesDescriptions (public) - store_id: {$storeId}");
            
            // Buscar todas as descrições locais
            $localDescriptions = CategoryDescription::all()->keyBy('category_id');
            
            // Buscar categorias da Nuvemshop
            $result = $this->nuvemshopService->getCategories($storeId);

            if (!$result['success']) {
                // Se não conseguir da Nuvemshop, retornar apenas as locais
                $organized = [];
                foreach ($localDescriptions as $desc) {
                    $organized[$desc->category_id] = [
                        'id' => $desc->id,
                        'category_id' => $desc->category_id,
                        'name' => null,
                        'content' => $desc->content,
                        'html_content' => $desc->html_content,
                    ];
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $organized,
                    'total' => count($organized),
                    'message' => 'Descriptions retrieved from local database only'
                ]);
            }

            // Mesclar dados da Nuvemshop com descrições locais
            $organized = [];
            foreach ($result['data'] as $category) {
                $categoryId = $category['id'];
                
                // Se existe descrição local, usar ela
                if (isset($localDescriptions[$categoryId])) {
                    $desc = $localDescriptions[$categoryId];
                    $organized[$categoryId] = [
                        'id' => $desc->id,
                        'category_id' => $desc->category_id,
                        'name' => $category['name'] ?? null,
                        'content' => $desc->content,
                        'html_content' => $desc->html_content,
                    ];
                } else {
                    // Senão, usar descrição da Nuvemshop
                    $description = '';
                    if (isset($category['description'])) {
                        if (is_array($category['description'])) {
                            $description = $category['description']['pt'] 
                                ?? $category['description']['es'] 
                                ?? $category['description']['en'] 
                                ?? '';
                        } else {
                            $description = $category['description'];
                        }
                    }

                    $organized[$categoryId] = [
                        'id' => $category['id'],
                        'category_id' => $category['id'],
                        'name' => $category['name'] ?? null,
                        'content' => strip_tags($description),
                        'html_content' => $description,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $organized,
                'total' => count($organized),
                'message' => 'All descriptions retrieved (local and Nuvemshop)'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error retrieving descriptions: ' . $e->getMessage()
            ], 500);
        }
    }
}

