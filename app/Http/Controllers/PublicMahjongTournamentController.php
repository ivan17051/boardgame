<?php

namespace App\Http\Controllers;

use App\Support\BornpadelMahjongTournaments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicMahjongTournamentController extends Controller
{
    /**
     * @return View|RedirectResponse
     */
    public function index(Request $request)
    {
        if ($request->routeIs('home') && auth()->check()) {
            return redirect()->route('rental.index');
        }

        $status = $request->query('status');
        $result = BornpadelMahjongTournaments::fetch($status);

        return view('public.mahjong-tournaments', [
            'tournaments' => $result['items'],
            'error' => $result['error'],
            'statusFilter' => $status,
        ]);
    }

    public function standings(int $id): View
    {
        $result = BornpadelMahjongTournaments::fetchGroupStandings($id);

        if ($result['error'] !== null || $result['data'] === null) {
            abort(404, $result['error'] ?? 'Klasemen tidak ditemukan.');
        }

        $data = $result['data'];
        $status = $data['turnamen']['status'] ?? null;

        if (! in_array($status, ['ongoing', 'completed'], true)) {
            abort(404, 'Klasemen belum tersedia untuk turnamen ini.');
        }

        return view('public.mahjong-standings', [
            'standings' => $data,
        ]);
    }

    public function showRegister(int $id): View
    {
        $tournament = BornpadelMahjongTournaments::findMahjongTournament($id);

        if (! $tournament || ($tournament['status'] ?? null) !== 'open') {
            abort(404, 'Pendaftaran tidak tersedia untuk turnamen ini.');
        }

        return view('public.mahjong-register', [
            'tournament' => $tournament,
        ]);
    }

    public function submitRegister(Request $request, int $id): RedirectResponse
    {
        $tournament = BornpadelMahjongTournaments::findMahjongTournament($id);
        
        if (! $tournament || ($tournament['status'] ?? null) !== 'open') {
            abort(404, 'Pendaftaran tidak tersedia untuk turnamen ini.');
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'gender' => ['required', 'in:male,female'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'tgl_lahir.date' => 'Tanggal lahir tidak valid.',
            'tgl_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
        ]);
        
        $result = BornpadelMahjongTournaments::registerPlayer([
            'id_turnamen' => $id,
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'],
            'gender' => $validated['gender'],
            'tgl_lahir' => $validated['tgl_lahir'] ?? null,
            'rating' => 0,
            'status' => 'pending',
        ]);
        
        if ($result['error'] !== null) {
            return back()
                ->withInput()
                ->withErrors(['form' => $result['error']]);
        }

        return redirect()
            ->route('home')
            ->with('success', $result['message'] ?? 'Pendaftaran berhasil dikirim.');
    }
}
