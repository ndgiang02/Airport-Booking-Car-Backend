<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function getWalletDriver()
    {

        $user = auth()->user();

        if ($user->user_type !== 'driver') {
            return response()->json([
                'status' => false,
                'message' => 'User is not a driver'
            ], 403);
        }

        $driver_id = $user->id;
        
        $balance = WalletTransaction::where('driver_id', $driver_id)->sum('amount');
        $transactions = WalletTransaction::where('driver_id', $driver_id)->get();

        return response()->json([
            'status'=> true,
            'message' => 'Wallet transactions fetched successfully',
            'balance' => $balance,
            'transactions' => $transactions
        ]);
    }
}