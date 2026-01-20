<?php

namespace App\Http\Controllers;

use App\Services\NuvemshopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar descrições de categorias
 * Todas as operações são feitas diretamente na API da Nuvemshop
 * Não utilizamos mais o banco de dados local para categorias/descrições
 */
class DescriptionController extends Controller
{
    protected NuvemshopService $nuvemshopService;

    public function __construct(NuvemshopService $nuvemshopService)
    {
        $this->nuvemshopService = $nuvemshopService;
    }

    /**
     * Get all categories from Nuvemshop with their descriptions
     */
    public function index(Request $request)
    {
        try {
            $result = $this->nuvemshopService->getCategories();
            
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
    public function show($id)
    {
        try {
            $result = $this->nuvemshopService->getCategory(null, $id);

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
     * Get category by category ID from Nuvemshop
     * Returns data in the format expected by the frontend
     */
    public function getByCategory($categoryId)
    {
        try {
            $result = $this->nuvemshopService->getCategory(null, $categoryId);

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
     * Create/Update category description in Nuvemshop
     * Note: In Nuvemshop, we update the existing category with the new description
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'category_id' => 'required|string',
                'content' => 'required|string',
                'html_content' => 'required|string',
            ]);

            $result = $this->nuvemshopService->updateCategory(
                null,
                $request->input('category_id'),
                $request->input('html_content')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => $result['message'] ?? 'Error updating category description'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => 'Description synced with Nuvemshop successfully'
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
     * Update category description in Nuvemshop
     * The $id parameter is the category_id in Nuvemshop
     */
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'content' => 'required|string',
                'html_content' => 'required|string',
            ]);

            $result = $this->nuvemshopService->updateCategory(
                null,
                $id,
                $request->input('html_content')
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => $result['message'] ?? 'Error updating category description'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => 'Description synced with Nuvemshop successfully'
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
    public function getCategories()
    {
        try {
            $result = $this->nuvemshopService->getCategories();
            
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
     * Delete is not applicable since descriptions are part of categories in Nuvemshop
     * Instead, we can clear the description by setting it to empty
     */
    public function destroy($id)
    {
        try {
            // Para "deletar" uma descrição, setamos como vazio na Nuvemshop
            $result = $this->nuvemshopService->updateCategory(
                null,
                $id,
                ''
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => $result['message'] ?? 'Error clearing category description'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Description cleared successfully in Nuvemshop'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error clearing description: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get description by category ID (Public endpoint for frontend consumption)
     * Fetches directly from Nuvemshop API
     */
    public function getCategoryDescription($categoryId)
    {
        try {
            $result = $this->nuvemshopService->getCategory(null, $categoryId);

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
     * Fetches directly from Nuvemshop API
     */
    public function getCategoriesDescriptions()
    {
        try {
            $result = $this->nuvemshopService->getCategories();

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'message' => $result['message'] ?? 'Error fetching categories'
                ], 400);
            }

            $organized = [];
            foreach ($result['data'] as $category) {
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

                $organized[$category['id']] = [
                    'id' => $category['id'],
                    'category_id' => $category['id'],
                    'name' => $category['name'] ?? null,
                    'content' => strip_tags($description),
                    'html_content' => $description,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $organized,
                'total' => count($organized),
                'message' => 'All descriptions retrieved successfully from Nuvemshop'
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

