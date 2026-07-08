<?php

namespace App\Http\Controllers;

use App\Services\PhoneNumberService;
use App\Support\BornpadelMahjongTournaments;
use Illuminate\Http\JsonResponse;
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
        $tournament = BornpadelMahjongTournaments::findMahjongTournament($id);

        if (! $tournament) {
            abort(404, 'Turnamen tidak ditemukan.');
        }

        $status = $tournament['status'] ?? null;

        if (! in_array($status, ['ongoing', 'completed'], true)) {
            abort(404, 'Klasemen belum tersedia untuk turnamen ini.');
        }

        $result = BornpadelMahjongTournaments::fetchGroupStandings($id);
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];

        if ($data === []) {
            $data = [
                'turnamen' => $tournament,
                'sections' => [],
                'overall' => [],
                'recap' => [],
                'babak_numbers' => [],
            ];
        }

        return view('public.mahjong-standings', [
            'standings' => $data,
            'standingsError' => $result['error'],
        ]);
    }

    public function winners(int $id): JsonResponse
    {
        $result = BornpadelMahjongTournaments::fetchWinners($id);

        if ($result['error'] !== null || $result['data'] === null) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Data juara tidak ditemukan.',
            ], 404);
        }

        $data = $result['data'];
        $data['placeholder_url'] = BornpadelMahjongTournaments::pemainPhotoPlaceholderUrl();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function showRegister(int $id): View
    {
        $tournament = $this->openTournamentOrAbort($id);

        return view('public.mahjong-register-check', [
            'tournament' => $tournament,
        ]);
    }

    public function checkRegister(Request $request, int $id): RedirectResponse
    {
        $tournament = $this->openTournamentOrAbort($id);

        $validated = $request->validate([
            'no_hp' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'no_hp_country' => ['nullable', 'string', 'max:8'],
            'no_hp_local' => ['nullable', 'string', 'max:20'],
        ], [
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
        ]);

        $noHp = $this->normalizedPhone($request, $validated['no_hp']);

        if ($noHp === '') {
            return back()
                ->withInput()
                ->withErrors(['no_hp' => 'Nomor HP wajib diisi.']);
        }

        $result = BornpadelMahjongTournaments::checkRegistration($id, $noHp);

        if ($result['error'] !== null || $result['data'] === null) {
            return back()
                ->withInput()
                ->withErrors(['no_hp' => $result['error'] ?? 'Gagal memeriksa nomor HP.']);
        }

        $data = $result['data'];
        $pemain = is_array($data['pemain'] ?? null) ? $data['pemain'] : null;
        $registration = is_array($data['registration'] ?? null) ? $data['registration'] : null;

        $sessionData = [
            'no_hp' => (string) ($data['no_hp'] ?? $noHp),
            'registered' => (bool) ($data['registered'] ?? false),
            'pemain_exists' => (bool) ($data['pemain_exists'] ?? false),
            'nama' => $pemain['nama'] ?? null,
            'gender' => $pemain['gender'] ?? null,
            'foto_url' => $pemain['foto_url'] ?? null,
            'registration_status' => $registration['status'] ?? null,
            'peserta_id' => $registration['peserta_id'] ?? null,
            'bukti_bayar_url' => $registration['bukti_bayar_url'] ?? null,
        ];

        session()->put($this->registerSessionKey($id), $sessionData);

        if ($sessionData['registered']) {
            return redirect()->route('public.mahjong-tournaments.register.status', $tournament['id']);
        }

        return redirect()->route('public.mahjong-tournaments.register.form', $tournament['id']);
    }

    /**
     * @return View|RedirectResponse
     */
    public function showRegisterForm(int $id)
    {
        $tournament = $this->openTournamentOrAbort($id);
        $session = $this->registerSession($id);

        if ($session === null) {
            return redirect()->route('public.mahjong-tournaments.register', $id);
        }

        if (! empty($session['registered'])) {
            return redirect()->route('public.mahjong-tournaments.register.status', $id);
        }

        return view('public.mahjong-register', [
            'tournament' => $tournament,
            'check' => $session,
            'prefillNama' => old('nama', $session['nama'] ?? ''),
            'prefillGender' => old('gender', $session['gender'] ?? ''),
            'prefillNoHp' => old('no_hp', $session['no_hp'] ?? ''),
            'pemainExists' => ! empty($session['pemain_exists']),
        ]);
    }

    /**
     * @return View|RedirectResponse
     */
    public function showRegisterStatus(int $id)
    {
        $tournament = $this->tournamentOrAbort($id);
        $session = $this->registerSession($id);

        if ($session === null) {
            return redirect()->route('public.mahjong-tournaments.register', $id);
        }

        if (empty($session['registered'])) {
            return redirect()->route('public.mahjong-tournaments.register.form', $id);
        }

        if (! empty($session['no_hp'])) {
            $check = BornpadelMahjongTournaments::checkRegistration($id, $session['no_hp']);
            if ($check['error'] === null && $check['data'] !== null) {
                $session = $this->mergeSessionFromCheck($session, $check['data']);
            }
        }

        $justRegistered = ! empty($session['just_registered']);
        if ($justRegistered) {
            unset($session['just_registered']);
        }
        session()->put($this->registerSessionKey($id), $session);

        return view('public.mahjong-register-status', [
            'tournament' => $tournament,
            'check' => $session,
            'statusLabel' => BornpadelMahjongTournaments::registrationStatusLabel($session['registration_status'] ?? null),
            'genderLabel' => BornpadelMahjongTournaments::genderLabel($session['gender'] ?? null),
            'canUploadReceipt' => BornpadelMahjongTournaments::canUploadPaymentReceipt(
                $session['registration_status'] ?? null,
                $session['bukti_bayar_url'] ?? null
            ),
            'justRegistered' => $justRegistered,
        ]);
    }

    public function uploadPaymentReceipt(Request $request, int $id): RedirectResponse
    {
        $this->tournamentOrAbort($id);
        $session = $this->registerSession($id);

        if ($session === null || empty($session['registered'])) {
            return redirect()->route('public.mahjong-tournaments.register', $id);
        }

        if (! BornpadelMahjongTournaments::canUploadPaymentReceipt(
            $session['registration_status'] ?? null,
            $session['bukti_bayar_url'] ?? null
        )) {
            return redirect()
                ->route('public.mahjong-tournaments.register.status', $id)
                ->withErrors(['bukti_bayar' => 'Bukti bayar sudah diunggah atau tidak dapat diubah.']);
        }

        $validated = $request->validate([
            'bukti_bayar' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ], [
            'bukti_bayar.required' => 'Bukti bayar wajib diunggah.',
            'bukti_bayar.mimes' => 'Bukti bayar harus berformat JPG, PNG, WebP, atau PDF.',
            'bukti_bayar.max' => 'Ukuran bukti bayar maksimal 5 MB.',
        ]);

        $result = BornpadelMahjongTournaments::uploadPaymentReceipt([
            'id_turnamen' => $id,
            'no_hp' => $session['no_hp'] ?? null,
            'peserta_id' => $session['peserta_id'] ?? null,
        ], $validated['bukti_bayar']);

        if ($result['error'] !== null) {
            return back()->withErrors(['bukti_bayar' => $result['error']]);
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $session['registration_status'] = $data['status'] ?? $session['registration_status'] ?? null;
        $session['bukti_bayar_url'] = $data['bukti_bayar_url'] ?? $session['bukti_bayar_url'] ?? null;
        $session['peserta_id'] = $data['peserta_id'] ?? $session['peserta_id'] ?? null;
        $session['just_registered'] = false;
        session()->put($this->registerSessionKey($id), $session);

        return redirect()
            ->route('public.mahjong-tournaments.register.status', $id)
            ->with('success', $result['message'] ?? 'Bukti bayar berhasil diunggah.');
    }

    public function submitRegister(Request $request, int $id): RedirectResponse
    {
        $tournament = $this->openTournamentOrAbort($id);
        $session = $this->registerSession($id);

        if ($session === null || ! empty($session['registered'])) {
            return redirect()->route('public.mahjong-tournaments.register', $id);
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'gender' => ['required', 'in:male,female'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak valid.',
            'tgl_lahir.date' => 'Tanggal lahir tidak valid.',
            'tgl_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        $noHp = $this->normalizedPhone($request, $validated['no_hp']);

        if ($noHp !== ($session['no_hp'] ?? '')) {
            return redirect()
                ->route('public.mahjong-tournaments.register', $id)
                ->withErrors(['form' => 'Nomor HP tidak sesuai. Silakan periksa ulang.']);
        }

        $result = BornpadelMahjongTournaments::registerPlayer([
            'id_turnamen' => $id,
            'nama' => $validated['nama'],
            'no_hp' => $noHp,
            'gender' => $validated['gender'],
            'tgl_lahir' => $validated['tgl_lahir'] ?? null,
            'rating' => 0,
            'status' => 'pending',
        ], $request->file('foto'));

        if ($result['error'] !== null) {
            return back()
                ->withInput()
                ->withErrors(['form' => $result['error']]);
        }

        $check = BornpadelMahjongTournaments::checkRegistration($id, $noHp);
        $checkData = is_array($check['data'] ?? null) ? $check['data'] : [];
        $pemain = is_array($checkData['pemain'] ?? null) ? $checkData['pemain'] : null;
        $registration = is_array($checkData['registration'] ?? null) ? $checkData['registration'] : null;
        $registerData = is_array($result['data'] ?? null) ? $result['data'] : [];

        session()->put($this->registerSessionKey($id), [
            'no_hp' => $noHp,
            'registered' => true,
            'pemain_exists' => true,
            'nama' => $pemain['nama'] ?? $validated['nama'],
            'gender' => $pemain['gender'] ?? $validated['gender'],
            'foto_url' => $pemain['foto_url'] ?? $registerData['foto_url'] ?? null,
            'registration_status' => $registration['status'] ?? $registerData['status'] ?? 'pending',
            'peserta_id' => $registration['peserta_id'] ?? $registerData['peserta_id'] ?? null,
            'bukti_bayar_url' => $registration['bukti_bayar_url'] ?? null,
            'just_registered' => true,
        ]);

        return redirect()
            ->route('public.mahjong-tournaments.register.status', $id)
            ->with('success', $result['message'] ?? 'Pendaftaran berhasil dikirim.');
    }

    /**
     * @return array<string, mixed>
     */
    private function tournamentOrAbort(int $id): array
    {
        $tournament = BornpadelMahjongTournaments::findMahjongTournament($id);

        if (! $tournament) {
            abort(404, 'Turnamen tidak ditemukan.');
        }

        return $tournament;
    }

    /**
     * @return array<string, mixed>
     */
    private function openTournamentOrAbort(int $id): array
    {
        $tournament = BornpadelMahjongTournaments::findMahjongTournament($id);

        if (! $tournament || ($tournament['status'] ?? null) !== 'open') {
            abort(404, 'Pendaftaran tidak tersedia untuk turnamen ini.');
        }

        return $tournament;
    }

    private function registerSessionKey(int $id): string
    {
        return 'mahjong_register.'.$id;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function registerSession(int $id): ?array
    {
        $session = session($this->registerSessionKey($id));

        return is_array($session) ? $session : null;
    }

    private function normalizedPhone(Request $request, string $fallback = ''): string
    {
        $phoneService = app(PhoneNumberService::class);

        if ($request->filled('no_hp_local') || $request->filled('no_hp_country')) {
            return $phoneService->normalize(
                $request->input('no_hp_country'),
                $request->input('no_hp_local')
            );
        }

        $parsed = $phoneService->parse($fallback);

        return $parsed['full'] ?? '';
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeSessionFromCheck(array $session, array $data): array
    {
        $pemain = is_array($data['pemain'] ?? null) ? $data['pemain'] : null;
        $registration = is_array($data['registration'] ?? null) ? $data['registration'] : null;

        return array_merge($session, [
            'no_hp' => (string) ($data['no_hp'] ?? $session['no_hp'] ?? ''),
            'registered' => (bool) ($data['registered'] ?? $session['registered'] ?? false),
            'pemain_exists' => (bool) ($data['pemain_exists'] ?? $session['pemain_exists'] ?? false),
            'nama' => $pemain['nama'] ?? $session['nama'] ?? null,
            'gender' => $pemain['gender'] ?? $session['gender'] ?? null,
            'foto_url' => $pemain['foto_url'] ?? $session['foto_url'] ?? null,
            'registration_status' => $registration['status'] ?? $session['registration_status'] ?? null,
            'peserta_id' => $registration['peserta_id'] ?? $session['peserta_id'] ?? null,
            'bukti_bayar_url' => $registration['bukti_bayar_url'] ?? $session['bukti_bayar_url'] ?? null,
        ]);
    }
}
