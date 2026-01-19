<?php

namespace App\Http\Controllers;

use App\Services\NuvemshopService;
use Illuminate\Http\Request;

class NuvemshopController extends Controller
{
    protected NuvemshopService $nuvemshopService;

    public function __construct(NuvemshopService $nuvemshopService)
    {
        $this->nuvemshopService = $nuvemshopService;
    }

    /**
     * Handle app installation from Nuvemshop
     */
    public function install(Request $request)
    {
        $code = $request->query('code');

        if (! $code) {
            return response()->json([
                'success' => false,
                'message' => 'Código de autorização é obrigatório',
            ], 400);
        }

        try {
            $result = $this->nuvemshopService->authorize($code);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro na autorização',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Instalação realizada com sucesso',
                'data' => $result['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro durante instalação: ' . $e->getMessage(),
            ], 500);
        }
    }
}
