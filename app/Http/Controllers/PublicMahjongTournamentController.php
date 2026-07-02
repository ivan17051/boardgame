<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PublicMahjongTournamentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $apiUrl = rtrim(config('services.bornpadel.api_url'), '/');
        $token = config('services.bornpadel.api_token');

        $error = null;
        $tournaments = [];

        if (! $token) {
            $error = 'Token API Bornpadel belum dikonfigurasi.';
        } else {
            try {
                $response = Http::timeout(15)
                    ->acceptJson()
                    ->withToken($token)
                    ->get($apiUrl.'/tournaments/mahjong', array_filter([
                        'status' => $status,
                    ]));

                if ($response->successful() && ($response->json('success') === true)) {
                    $tournaments = $response->json('data') ?? [];
                } else {
                    $error = $response->json('message') ?? 'Gagal memuat data turnamen.';
                }
            } catch (\Throwable $e) {
                $error = 'Tidak dapat terhubung ke server turnamen.';
            }
        }

        return view('public.mahjong-tournaments', [
            'tournaments' => $tournaments,
            'error' => $error,
            'statusFilter' => $status,
        ]);
    }
}
