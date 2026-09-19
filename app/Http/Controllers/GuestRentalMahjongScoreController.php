<?php

namespace App\Http\Controllers;

use App\Support\RentalMahjongScoring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class GuestRentalMahjongScoreController extends Controller
{
    public function index(Request $request): View
    {
        $token = $this->tokenFromRequest($request);

        return view('guest.mahjong-score', [
            'initialToken' => $token,
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $session = $this->sessionOrAbort($request);

        return response()->json([
            'success' => true,
            'data' => RentalMahjongScoring::payload($session),
        ]);
    }

    public function updatePlayers(Request $request): JsonResponse
    {
        $session = $this->sessionOrAbort($request);

        $validated = $request->validate([
            'players' => ['required', 'array', 'size:'.RentalMahjongScoring::PLAYER_COUNT],
            'players.*.seat' => ['nullable', 'integer', 'min:1', 'max:'.RentalMahjongScoring::PLAYER_COUNT],
            'players.*.nama' => ['required', 'string', 'max:255'],
            'players.*.is_renter' => ['nullable', 'boolean'],
        ], [
            'players.required' => 'Daftar pemain wajib diisi.',
            'players.size' => 'Harus mengisi tepat '.RentalMahjongScoring::PLAYER_COUNT.' pemain.',
            'players.*.nama.required' => 'Nama pemain wajib diisi.',
        ]);

        try {
            $session = RentalMahjongScoring::setPlayers($session, $validated['players']);
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'success' => true,
            'message' => 'Nama pemain disimpan.',
            'data' => RentalMahjongScoring::payload($session),
        ]);
    }

    public function storeHand(Request $request): JsonResponse
    {
        $session = $this->sessionOrAbort($request);

        $validated = $request->validate([
            'scores' => ['required', 'array', 'size:'.RentalMahjongScoring::PLAYER_COUNT],
            'scores.*.seat' => ['required', 'integer', 'min:1', 'max:'.RentalMahjongScoring::PLAYER_COUNT],
            'scores.*.poin' => ['nullable', 'integer', 'min:-999999', 'max:999999'],
            'winner_seat' => ['nullable', 'integer', 'min:1', 'max:'.RentalMahjongScoring::PLAYER_COUNT],
        ], [
            'scores.required' => 'Poin wajib diisi.',
            'scores.size' => 'Harus mengisi poin untuk '.RentalMahjongScoring::PLAYER_COUNT.' pemain.',
            'scores.*.poin.integer' => 'Poin wajib angka.',
        ]);

        $session = RentalMahjongScoring::appendHand(
            $session,
            $validated['scores'],
            array_key_exists('winner_seat', $validated) ? $validated['winner_seat'] : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Ronde tersimpan.',
            'data' => RentalMahjongScoring::payload($session),
        ]);
    }

    public function voidLastHand(Request $request): JsonResponse
    {
        $session = $this->sessionOrAbort($request);
        $session = RentalMahjongScoring::voidLastHand($session);

        return response()->json([
            'success' => true,
            'message' => 'Ronde terakhir dibatalkan.',
            'data' => RentalMahjongScoring::payload($session),
        ]);
    }

    private function sessionOrAbort(Request $request)
    {
        $token = $this->tokenFromRequest($request);
        $session = RentalMahjongScoring::findByAccessToken($token ?? '');

        if (! $session) {
            abort(404, 'Sesi skor tidak ditemukan.');
        }

        return $session;
    }

    private function tokenFromRequest(Request $request): ?string
    {
        $header = $request->header('X-Score-Token');
        if (is_string($header) && trim($header) !== '') {
            return trim($header);
        }

        $query = $request->query('token');
        if (is_string($query) && trim($query) !== '') {
            return trim($query);
        }

        $input = $request->input('token');
        if (is_string($input) && trim($input) !== '') {
            return trim($input);
        }

        return null;
    }
}
