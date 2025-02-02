<?php

namespace App\Http\Controllers;

use App\Enums\CryptoType;
use App\Repositories\CryptoTradeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CryptoTradeController extends Controller
{
    public function __construct(
        protected CryptoTradeRepository $cryptoTradeRepository
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('CryptoTrade', [

        ]);
    }

    public function getCandles(Request $request, ?int $cryptoType = CryptoType::BTC->value): JsonResponse
    {
        $interval = $request->query('interval') ?? 15;
        $cryptoType = CryptoType::from($cryptoType);
        $candles = $this->cryptoTradeRepository->getCandles($cryptoType, $interval);
        Log::info('Bougies récupérées :', [$candles]);
        return response()->json($candles);
    }
}
