<?php

namespace App\Http\Controllers;

use App\Models\CategoryDescription;
use App\Models\Store;
use App\Services\NuvemshopService;
use Illuminate\Http\Request;

class DescriptionController extends Controller
{
    protected NuvemshopService $nuvemshopService;

    public function __construct(NuvemshopService $nuvemshopService)
    {
        $this->nuvemshopService = $nuvemshopService;
    }

    /**
     * Get all descriptions with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        $descriptions = CategoryDescription::paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $descriptions->items(),
            'pagination' => [
                'current_page' => $descriptions->currentPage(),
                'total' => $descriptions->total(),
                'per_page' => $descriptions->perPage(),
                'last_page' => $descriptions->lastPage(),
                'from' => $descriptions->firstItem(),
                'to' => $descriptions->lastItem(),
            ],
            'message' => 'Descriptions retrieved successfully'
        ]);
    }

    /**
     * Get a specific description by ID
     */
    public function show($id)
    {
        $description = CategoryDescription::find($id);

        if (!$description) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Description not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $description,
            'message' => 'Description retrieved successfully'
        ]);
    }

    /**
     * Get description by category ID
     */
    public function getByCategory($categoryId)
    {
        $description = CategoryDescription::where('category_id', $categoryId)->first();

        if (!$description) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Description not found for this category'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $description,
            'message' => 'Description retrieved successfully'
        ]);
    }

    /**
     * Create a new description
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'category_id' => 'required|string|unique:category_descriptions',
                'content' => 'required|string',
                'html_content' => 'required|string',
            ]);

            $description = CategoryDescription::create($request->all());

            // Tenta atualizar na Nuvemshop se houver uma loja configurada
            $store = Store::first();
            if ($store) {
                $this->nuvemshopService->updateCategory(
                    $store->store_id,
                    $description->category_id,
                    $description->html_content
                );
            }

            return response()->json([
                'success' => true,
                'data' => $description,
                'message' => 'Description created and synced with Nuvemshop successfully'
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
     * Update a description
     */
    public function update(Request $request, $id)
    {
        try {
            $description = CategoryDescription::find($id);

            if (!$description) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Description not found'
                ], 404);
            }

            $this->validate($request, [
                'content' => 'required|string',
                'html_content' => 'required|string',
            ]);

            $description->update($request->all());

            // Tenta atualizar na Nuvemshop se houver uma loja configurada
            $store = Store::first();
            if ($store) {
                $this->nuvemshopService->updateCategory(
                    $store->store_id,
                    $description->category_id,
                    $description->html_content
                );
            }

            return response()->json([
                'success' => true,
                'data' => $description,
                'message' => 'Description updated and synced with Nuvemshop successfully'
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
     * Delete a description
     */
    public function destroy($id)
    {
        try {
            $description = CategoryDescription::find($id);

            if (!$description) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Description not found'
                ], 404);
            }

            $description->delete();

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Description deleted successfully'
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
     */
    public function getCategoryDescription($categoryId)
    {
        try {
            $description = CategoryDescription::where('category_id', $categoryId)->first();

            if (!$description) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Description not found for this category',
                    'category_id' => $categoryId,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $description->id,
                    'category_id' => $description->category_id,
                    'content' => $description->content,
                    'html_content' => $description->html_content,
                    'created_at' => $description->created_at,
                    'updated_at' => $description->updated_at,
                ],
                'message' => 'Description retrieved successfully'
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
     */
    public function getCategoriesDescriptions()
    {
        try {
            $descriptions = CategoryDescription::all();

            $organized = [];
            foreach ($descriptions as $description) {
                $organized[$description->category_id] = [
                    'id' => $description->id,
                    'category_id' => $description->category_id,
                    'content' => $description->content,
                    'html_content' => $description->html_content,
                    'updated_at' => $description->updated_at,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $organized,
                'total' => count($organized),
                'message' => 'All descriptions retrieved successfully'
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
