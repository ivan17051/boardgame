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
        $status = $request->query('status');
        $result = BornpadelMahjongTournaments::fetch($status);

        return view('public.mahjong-tournaments', [
            'tournaments' => $result['items'],
            'error' => $result['error'],
            'statusFilter' => $status,
        ]);
    }

    public function standings(Request $request, int $id): View
    {
        $tournament = $this->tournamentOrAbort($id);
        $idKategori = $this->resolveKategoriId($request, $tournament);
        $result = BornpadelMahjongTournaments::fetchGroupStandings($id, $idKategori);
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];

        if ($data === []) {
            $data = [
                'turnamen' => $tournament,
                'sections' => [],
            'overall' => [],
            'recap' => [],
            'babak_numbers' => [],
            'teams' => [],
            ];
        }

        return view('public.mahjong-standings', [
            'tournament' => $tournament,
            'idKategori' => $idKategori,
            'standings' => $data,
            'standingsError' => $result['error'],
        ]);
    }

    public function groupsPage(Request $request, int $id): View
    {
        $tournament = $this->tournamentOrAbort($id);
        $idKategori = $this->resolveKategoriId($request, $tournament);
        $result = BornpadelMahjongTournaments::fetchMahjongGroups($id);
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];

        return view('public.mahjong-groups', [
            'tournament' => $tournament,
            'idKategori' => $idKategori,
            'groups' => is_array($data['groups'] ?? null) ? $data['groups'] : [],
            'groupsError' => $result['error'],
            'canInputScores' => BornpadelMahjongTournaments::canInputPublicScores($tournament, $idKategori, $data),
            'scoreStoreUrl' => route('public.mahjong-tournaments.scores.store', $id),
        ]);
    }

    /**
     * @return View|RedirectResponse
     */
    public function showGroup(Request $request, int $id, int $grupId)
    {
        $tournament = $this->tournamentOrAbort($id);

        if (! BornpadelMahjongTournaments::findMahjongGroup($id, $grupId)) {
            abort(404, 'Grup tidak ditemukan pada turnamen ini.');
        }

        return redirect()->route('public.mahjong-tournaments.groups', $id);
    }

    public function storeGroupPageScores(Request $request, int $id, int $grupId): RedirectResponse
    {
        $this->scoreInputTournamentOrAbort($request, $id);

        if (! BornpadelMahjongTournaments::findMahjongGroup($id, $grupId)) {
            abort(404, 'Grup tidak ditemukan pada turnamen ini.');
        }

        $validated = $request->validate([
            'id_grup_member_pemenang' => ['nullable', 'integer'],
            'scores' => ['required', 'array', 'size:4'],
            'scores.*.id_grup_member' => ['required', 'integer'],
            'scores.*.poin' => ['required', 'integer'],
        ], [
            'scores.required' => 'Poin keempat pemain wajib diisi.',
            'scores.size' => 'Poin harus diisi untuk keempat pemain dalam grup.',
            'scores.*.id_grup_member.required' => 'Anggota grup wajib dipilih.',
            'scores.*.poin.required' => 'Poin wajib diisi.',
            'scores.*.poin.integer' => 'Poin harus berupa angka.',
        ]);

        $scores = array_map(static function (array $score) {
            return [
                'id_grup_member' => (int) $score['id_grup_member'],
                'poin' => (int) $score['poin'],
            ];
        }, $validated['scores']);

        $winnerId = isset($validated['id_grup_member_pemenang']) && $validated['id_grup_member_pemenang'] !== null
            ? (int) $validated['id_grup_member_pemenang']
            : null;
        $scoreMemberIds = array_map(static function (array $score) {
            return (int) $score['id_grup_member'];
        }, $scores);

        if ($winnerId !== null && ! in_array($winnerId, $scoreMemberIds, true)) {
            return back()
                ->withInput()
                ->withErrors(['form' => 'Pemenang harus salah satu pemain di grup ini.']);
        }

        $result = BornpadelMahjongTournaments::storeGroupScores(
            $id,
            $grupId,
            $scores,
            $winnerId
        );

        if ($result['error'] !== null) {
            return back()
                ->withInput()
                ->withErrors(['form' => $result['error']]);
        }

        return redirect()
            ->route('public.mahjong-tournaments.groups', $id)
            ->with('success', $result['message'] ?? 'Poin grup berhasil disimpan.');
    }

    public function participants(Request $request, int $id): View
    {
        $tournament = $this->tournamentOrAbort($id);
        $idKategori = $this->resolveKategoriId($request, $tournament);
        $result = BornpadelMahjongTournaments::fetchParticipants($id, $idKategori);

        return view('public.mahjong-participants', [
            'tournament' => $tournament,
            'idKategori' => $idKategori,
            'participants' => $result['items'] ?? [],
            'participantType' => $result['type'] ?? 'single',
            'participantsError' => $result['error'],
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

    public function groups(int $id): JsonResponse
    {
        $this->ongoingTournamentOrAbort($id);
        $result = BornpadelMahjongTournaments::fetchMahjongGroups($id);

        if ($result['error'] !== null) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function storeGroupScores(Request $request, int $id): JsonResponse
    {
        $this->scoreInputTournamentOrAbort($request, $id, true);

        $validated = $request->validate([
            'id_grup' => ['required', 'integer'],
            'id_grup_member_pemenang' => ['nullable', 'integer'],
            'scores' => ['required', 'array', 'size:4'],
            'scores.*.id_grup_member' => ['required', 'integer'],
            'scores.*.poin' => ['required', 'integer'],
        ], [
            'id_grup.required' => 'Grup wajib dipilih.',
            'scores.required' => 'Poin keempat pemain wajib diisi.',
            'scores.size' => 'Poin harus diisi untuk keempat pemain dalam grup.',
            'scores.*.id_grup_member.required' => 'Anggota grup wajib dipilih.',
            'scores.*.poin.required' => 'Poin wajib diisi.',
            'scores.*.poin.integer' => 'Poin harus berupa angka.',
        ]);

        $scores = array_map(static function (array $score) {
            return [
                'id_grup_member' => (int) $score['id_grup_member'],
                'poin' => (int) $score['poin'],
            ];
        }, $validated['scores']);

        $winnerId = isset($validated['id_grup_member_pemenang']) && $validated['id_grup_member_pemenang'] !== null
            ? (int) $validated['id_grup_member_pemenang']
            : null;
        $scoreMemberIds = array_map(static function (array $score) {
            return (int) $score['id_grup_member'];
        }, $scores);

        if ($winnerId !== null && ! in_array($winnerId, $scoreMemberIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Pemenang harus salah satu pemain di grup ini.',
            ], 422);
        }

        $result = BornpadelMahjongTournaments::storeGroupScores(
            $id,
            (int) $validated['id_grup'],
            $scores,
            $winnerId
        );

        return $this->scoreJson($result, 201);
    }

    public function showRegister(Request $request, int $id): View
    {
        $tournament = $this->openTournamentOrAbort($id);
        $idKategori = $this->resolveKategoriId($request, $tournament);

        return view('public.mahjong-register-check', [
            'tournament' => $tournament,
            'idKategori' => $idKategori,
            'allowsGroup' => BornpadelMahjongTournaments::allowsGroupRegistration($tournament),
            'groupSize' => BornpadelMahjongTournaments::registrationRosterSize($tournament, $idKategori),
            'rosterNoun' => BornpadelMahjongTournaments::registrationRosterNoun($tournament),
            'rosterNounTitle' => BornpadelMahjongTournaments::registrationRosterNoun($tournament, true),
        ]);
    }

    public function checkRegister(Request $request, int $id): RedirectResponse
    {
        $tournament = $this->openTournamentOrAbort($id);
        $allowsGroup = BornpadelMahjongTournaments::allowsGroupRegistration($tournament);

        $rules = [
            'no_hp' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'no_hp_country' => ['nullable', 'string', 'max:8'],
            'no_hp_local' => ['nullable', 'string', 'max:20'],
            'id_kategori' => ['nullable', 'integer'],
            'registration_mode' => [$allowsGroup ? 'required' : 'nullable', 'in:single,group'],
            'nama_grup' => ['nullable', 'string', 'max:255'],
        ];

        $idKategori = $this->resolveKategoriId($request, $tournament);
        $groupSize = BornpadelMahjongTournaments::registrationRosterSize($tournament, $idKategori);
        $isGroupMode = $allowsGroup && $request->input('registration_mode') === 'group';

        if ($isGroupMode) {
            $rules['nama_grup'] = ['required', 'string', 'max:255'];
            for ($n = 2; $n <= $groupSize; $n++) {
                $rules['no_hp_'.$n] = ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'];
                $rules['no_hp_'.$n.'_country'] = ['nullable', 'string', 'max:8'];
                $rules['no_hp_'.$n.'_local'] = ['nullable', 'string', 'max:20'];
            }
        }

        $rosterNoun = BornpadelMahjongTournaments::registrationRosterNoun($tournament);
        $validated = $request->validate($rules, [
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'nama_grup.required' => 'Nama '.$rosterNoun.' wajib diisi.',
        ]);

        $noHp = $this->normalizedPhone($request, $validated['no_hp'] ?? '', 'no_hp');

        if ($noHp === '') {
            return back()
                ->withInput()
                ->withErrors(['no_hp' => 'Nomor HP wajib diisi.']);
        }

        if (! empty($tournament['has_multiple_kategori']) && ! $idKategori) {
            return back()
                ->withInput()
                ->withErrors(['id_kategori' => 'Pilih kategori kompetisi terlebih dahulu.']);
        }

        $phones = [$noHp];
        $phoneFields = ['no_hp'];

        if ($isGroupMode) {
            for ($n = 2; $n <= $groupSize; $n++) {
                $field = 'no_hp_'.$n;
                $phone = $this->normalizedPhone($request, $validated[$field] ?? '', $field);
                if ($phone === '') {
                    return back()
                        ->withInput()
                        ->withErrors([$field => 'Nomor HP pemain '.$n.' wajib diisi.']);
                }
                $phones[] = $phone;
                $phoneFields[] = $field;
            }

            $seen = [];
            foreach ($phones as $index => $phone) {
                if (in_array($phone, $seen, true)) {
                    return back()
                        ->withInput()
                        ->withErrors([$phoneFields[$index] => 'Nomor HP harus unik untuk setiap pemain dalam '.$rosterNoun.'.']);
                }
                $seen[] = $phone;
            }

            $nameCheck = BornpadelMahjongTournaments::assertGroupNameAvailable(
                $id,
                (string) $validated['nama_grup'],
                $idKategori
            );
            if (! $nameCheck['ok']) {
                return back()
                    ->withInput()
                    ->withErrors(['nama_grup' => $nameCheck['error']]);
            }
        }

        $players = [];
        foreach ($phones as $index => $phone) {
            $result = BornpadelMahjongTournaments::checkRegistration($id, $phone, $idKategori);

            if ($result['error'] !== null || $result['data'] === null) {
                return back()
                    ->withInput()
                    ->withErrors([$phoneFields[$index] => $result['error'] ?? 'Gagal memeriksa nomor HP.']);
            }

            $data = $result['data'];
            if (! empty($data['registered'])) {
                if (! $isGroupMode) {
                    $sessionData = $this->sessionFromCheck($data, $noHp, $idKategori, 'single');
                    session()->put($this->registerSessionKey($id), $sessionData);

                    return redirect()->route('public.mahjong-tournaments.register.status', $tournament['id']);
                }

                $label = $index === 0 ? 'pemain 1' : 'pemain '.($index + 1);

                return back()
                    ->withInput()
                    ->withErrors([$phoneFields[$index] => 'Nomor HP '.$label.' sudah terdaftar pada kategori ini.']);
            }

            $pemain = is_array($data['pemain'] ?? null) ? $data['pemain'] : null;
            $players[] = [
                'no_hp' => (string) ($data['no_hp'] ?? $phone),
                'pemain_exists' => (bool) ($data['pemain_exists'] ?? false),
                'nama' => $pemain['nama'] ?? null,
                'gender' => $pemain['gender'] ?? null,
                'foto_url' => $pemain['foto_url'] ?? null,
            ];
        }

        $first = $players[0];
        $sessionData = [
            'registration_mode' => $isGroupMode ? 'group' : 'single',
            'nama_grup' => $isGroupMode ? trim((string) $validated['nama_grup']) : null,
            'no_hp' => $first['no_hp'],
            'id_kategori' => $idKategori,
            'registered' => false,
            'pemain_exists' => $first['pemain_exists'],
            'nama' => $first['nama'],
            'gender' => $first['gender'],
            'foto_url' => $first['foto_url'],
            'registration_status' => null,
            'peserta_id' => null,
            'bukti_bayar_url' => null,
            'players' => $players,
            'group' => null,
        ];

        session()->put($this->registerSessionKey($id), $sessionData);

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

        $idKategori = $session['id_kategori'] ?? $tournament['default_kategori_id'] ?? null;
        $isGroupMode = ($session['registration_mode'] ?? 'single') === 'group'
            && BornpadelMahjongTournaments::allowsGroupRegistration($tournament);
        $groupSize = $isGroupMode
            ? BornpadelMahjongTournaments::registrationRosterSize($tournament, $idKategori)
            : 1;
        $players = $this->sessionPlayers($session, $groupSize);

        return view('public.mahjong-register', [
            'tournament' => $tournament,
            'idKategori' => $idKategori,
            'check' => $session,
            'isGroupMode' => $isGroupMode,
            'groupSize' => $groupSize,
            'rosterNounTitle' => BornpadelMahjongTournaments::registrationRosterNoun($tournament, true),
            'namaGrup' => $session['nama_grup'] ?? '',
            'players' => $players,
            'prefillNama' => old('nama', $players[0]['nama'] ?? ''),
            'prefillGender' => old('gender', $players[0]['gender'] ?? ''),
            'prefillNoHp' => old('no_hp', $players[0]['no_hp'] ?? ''),
            'pemainExists' => ! empty($players[0]['pemain_exists']),
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
            $check = BornpadelMahjongTournaments::checkRegistration(
                $id,
                $session['no_hp'],
                $session['id_kategori'] ?? null
            );
            if ($check['error'] === null && $check['data'] !== null) {
                $session = $this->mergeSessionFromCheck($session, $check['data']);
            }
        }

        $justRegistered = ! empty($session['just_registered']);
        if ($justRegistered) {
            unset($session['just_registered']);
        }
        session()->put($this->registerSessionKey($id), $session);

        $isGroupMode = ($session['registration_mode'] ?? 'single') === 'group'
            || ! empty($session['nama_grup'])
            || ! empty($session['group']);
        $group = is_array($session['group'] ?? null) ? $session['group'] : null;
        $members = is_array($group['members'] ?? null)
            ? $group['members']
            : (is_array($session['players'] ?? null) ? $session['players'] : []);

        return view('public.mahjong-register-status', [
            'tournament' => $tournament,
            'idKategori' => $session['id_kategori'] ?? $tournament['default_kategori_id'] ?? null,
            'check' => $session,
            'isGroupMode' => $isGroupMode,
            'namaGrup' => $session['nama_grup'] ?? ($group['nama'] ?? null),
            'members' => $members,
            'rosterNounTitle' => BornpadelMahjongTournaments::registrationRosterNoun($tournament, true),
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
            'id_kategori' => $session['id_kategori'] ?? null,
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

        $idKategori = $this->resolveKategoriId($request, $tournament, $session);
        $isGroupMode = ($session['registration_mode'] ?? 'single') === 'group'
            && BornpadelMahjongTournaments::allowsGroupRegistration($tournament);
        $groupSize = $isGroupMode
            ? BornpadelMahjongTournaments::registrationRosterSize($tournament, $idKategori)
            : 1;

        $rules = [
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['required', 'string', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'gender' => ['required', 'in:male,female'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'bukti_bayar' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            'registration_mode' => ['nullable', 'in:single,group'],
            'nama_grup' => [$isGroupMode ? 'required' : 'nullable', 'string', 'max:255'],
        ];

        $messages = [
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
            'bukti_bayar.mimes' => 'Bukti transfer harus berformat JPG, PNG, WebP, atau PDF.',
            'bukti_bayar.max' => 'Ukuran bukti transfer maksimal 5 MB.',
            'nama_grup.required' => 'Nama '.BornpadelMahjongTournaments::registrationRosterNoun($tournament).' wajib diisi.',
        ];

        if ($isGroupMode) {
            for ($n = 2; $n <= $groupSize; $n++) {
                $rules['player_'.$n.'.nama'] = ['required', 'string', 'max:255'];
                $rules['player_'.$n.'.no_hp'] = ['required', 'string', 'max:25'];
                $rules['player_'.$n.'.gender'] = ['required', 'in:male,female'];
                $rules['player_'.$n.'.tgl_lahir'] = ['nullable', 'date', 'before:today'];
                $rules['foto_'.$n] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'];
                $messages['player_'.$n.'.nama.required'] = 'Nama pemain '.$n.' wajib diisi.';
                $messages['player_'.$n.'.gender.required'] = 'Jenis kelamin pemain '.$n.' wajib dipilih.';
                $messages['player_'.$n.'.gender.in'] = 'Jenis kelamin pemain '.$n.' tidak valid.';
            }
        }

        $validated = $request->validate($rules, $messages);

        $noHp = $this->normalizedPhone($request, $validated['no_hp']);

        if ($noHp !== ($session['no_hp'] ?? '')) {
            return redirect()
                ->route('public.mahjong-tournaments.register', $id)
                ->withErrors(['form' => 'Nomor HP tidak sesuai. Silakan periksa ulang.']);
        }

        if ($isGroupMode) {
            return $this->submitGroupRegister($request, $id, $tournament, $session, $validated, $idKategori, $groupSize, $noHp);
        }

        $result = BornpadelMahjongTournaments::registerPlayer([
            'id_turnamen' => $id,
            'id_kategori' => $idKategori,
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

        $check = BornpadelMahjongTournaments::checkRegistration($id, $noHp, $idKategori);
        $checkData = is_array($check['data'] ?? null) ? $check['data'] : [];
        $pemain = is_array($checkData['pemain'] ?? null) ? $checkData['pemain'] : null;
        $registration = is_array($checkData['registration'] ?? null) ? $checkData['registration'] : null;
        $registerData = is_array($result['data'] ?? null) ? $result['data'] : [];

        $session = [
            'registration_mode' => 'single',
            'nama_grup' => null,
            'no_hp' => $noHp,
            'id_kategori' => $registerData['kategori_id'] ?? $checkData['kategori_id'] ?? $idKategori,
            'registered' => true,
            'pemain_exists' => true,
            'nama' => $pemain['nama'] ?? $validated['nama'],
            'gender' => $pemain['gender'] ?? $validated['gender'],
            'foto_url' => $pemain['foto_url'] ?? $registerData['foto_url'] ?? null,
            'registration_status' => $registration['status'] ?? $registerData['status'] ?? 'pending',
            'peserta_id' => $registration['peserta_id'] ?? $registerData['peserta_id'] ?? null,
            'bukti_bayar_url' => $registration['bukti_bayar_url'] ?? null,
            'players' => [[
                'no_hp' => $noHp,
                'pemain_exists' => true,
                'nama' => $pemain['nama'] ?? $validated['nama'],
                'gender' => $pemain['gender'] ?? $validated['gender'],
                'foto_url' => $pemain['foto_url'] ?? $registerData['foto_url'] ?? null,
            ]],
            'group' => $checkData['group'] ?? null,
            'just_registered' => true,
        ];
        $session = $this->attachSubmittedReceipt($request, $id, $session);
        session()->put($this->registerSessionKey($id), $session);

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

        if (! $tournament || empty($tournament['registration_open'])) {
            abort(404, 'Pendaftaran tidak tersedia untuk turnamen ini.');
        }

        return $tournament;
    }

    private function resolveKategoriId(Request $request, array $tournament, ?array $session = null): ?int
    {
        if ($request->filled('id_kategori')) {
            return (int) $request->input('id_kategori');
        }

        if ($session && ! empty($session['id_kategori'])) {
            return (int) $session['id_kategori'];
        }

        if (! empty($tournament['default_kategori_id'])) {
            return (int) $tournament['default_kategori_id'];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function ongoingTournamentOrAbort(int $id): array
    {
        $tournament = BornpadelMahjongTournaments::findMahjongTournament($id);

        if (! $tournament || ($tournament['status'] ?? null) !== 'ongoing') {
            abort(404, 'Input poin hanya tersedia untuk turnamen yang sedang berlangsung.');
        }

        return $tournament;
    }

    /**
     * @return array<string, mixed>
     */
    private function scoreInputTournamentOrAbort(Request $request, int $id, bool $asJson = false): array
    {
        $tournament = $this->ongoingTournamentOrAbort($id);
        $idKategori = $this->resolveKategoriId($request, $tournament);

        if (BornpadelMahjongTournaments::isExternalScoringEnabled($tournament, $idKategori)) {
            return $tournament;
        }

        $message = 'Input skor eksternal sedang dinonaktifkan untuk turnamen ini.';

        if ($asJson) {
            abort(response()->json([
                'success' => false,
                'message' => $message,
                'data' => [
                    'mahjong_external_scoring_enabled' => false,
                ],
            ], 403));
        }

        abort(403, $message);
    }

    /**
     * @param  array{data: array<string, mixed>|null, message?: string|null, error: string|null}  $result
     */
    private function scoreJson(array $result, int $successStatus = 200): JsonResponse
    {
        if ($result['error'] !== null || $result['data'] === null) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Gagal menyimpan poin.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Poin berhasil disimpan.',
            'data' => $result['data'],
        ], $successStatus);
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

    private function normalizedPhone(Request $request, string $fallback = '', string $name = 'no_hp'): string
    {
        $phoneService = app(PhoneNumberService::class);

        if ($request->filled($name.'_local') || $request->filled($name.'_country')) {
            return $phoneService->normalize(
                $request->input($name.'_country'),
                $request->input($name.'_local')
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
        $group = is_array($data['group'] ?? null) ? $data['group'] : ($session['group'] ?? null);
        $namaGrup = $group['nama'] ?? ($session['nama_grup'] ?? null);
        $players = $session['players'] ?? null;

        if (is_array($group['members'] ?? null)) {
            $players = array_map(static function (array $member) {
                return [
                    'no_hp' => $member['no_hp'] ?? null,
                    'pemain_exists' => true,
                    'nama' => $member['nama'] ?? null,
                    'gender' => $member['gender'] ?? null,
                    'foto_url' => $member['foto_url'] ?? null,
                    'peserta_id' => $member['peserta_id'] ?? null,
                    'status' => $member['status'] ?? null,
                ];
            }, $group['members']);
        }

        return array_merge($session, [
            'no_hp' => (string) ($data['no_hp'] ?? $session['no_hp'] ?? ''),
            'id_kategori' => $data['kategori_id'] ?? $session['id_kategori'] ?? null,
            'registered' => (bool) ($data['registered'] ?? $session['registered'] ?? false),
            'pemain_exists' => (bool) ($data['pemain_exists'] ?? $session['pemain_exists'] ?? false),
            'nama' => $pemain['nama'] ?? $session['nama'] ?? null,
            'gender' => $pemain['gender'] ?? $session['gender'] ?? null,
            'foto_url' => $pemain['foto_url'] ?? $session['foto_url'] ?? null,
            'registration_status' => $registration['status'] ?? $session['registration_status'] ?? null,
            'peserta_id' => $registration['peserta_id'] ?? $session['peserta_id'] ?? null,
            'bukti_bayar_url' => $registration['bukti_bayar_url'] ?? $session['bukti_bayar_url'] ?? null,
            'nama_grup' => $namaGrup,
            'group' => $group,
            'players' => $players,
            'registration_mode' => $group ? 'group' : ($session['registration_mode'] ?? 'single'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sessionFromCheck(array $data, string $noHp, $idKategori, string $mode = 'single'): array
    {
        $pemain = is_array($data['pemain'] ?? null) ? $data['pemain'] : null;
        $registration = is_array($data['registration'] ?? null) ? $data['registration'] : null;
        $group = is_array($data['group'] ?? null) ? $data['group'] : null;

        return [
            'registration_mode' => $group ? 'group' : $mode,
            'nama_grup' => $group['nama'] ?? null,
            'no_hp' => (string) ($data['no_hp'] ?? $noHp),
            'id_kategori' => $data['kategori_id'] ?? $idKategori,
            'registered' => (bool) ($data['registered'] ?? false),
            'pemain_exists' => (bool) ($data['pemain_exists'] ?? false),
            'nama' => $pemain['nama'] ?? null,
            'gender' => $pemain['gender'] ?? null,
            'foto_url' => $pemain['foto_url'] ?? null,
            'registration_status' => $registration['status'] ?? null,
            'peserta_id' => $registration['peserta_id'] ?? null,
            'bukti_bayar_url' => $registration['bukti_bayar_url'] ?? null,
            'players' => is_array($group['members'] ?? null) ? $group['members'] : [[
                'no_hp' => (string) ($data['no_hp'] ?? $noHp),
                'pemain_exists' => (bool) ($data['pemain_exists'] ?? false),
                'nama' => $pemain['nama'] ?? null,
                'gender' => $pemain['gender'] ?? null,
                'foto_url' => $pemain['foto_url'] ?? null,
            ]],
            'group' => $group,
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<int, array<string, mixed>>
     */
    private function sessionPlayers(array $session, int $expectedSize): array
    {
        $players = is_array($session['players'] ?? null) ? array_values($session['players']) : [];

        if ($players === [] && ! empty($session['no_hp'])) {
            $players[] = [
                'no_hp' => $session['no_hp'] ?? '',
                'pemain_exists' => ! empty($session['pemain_exists']),
                'nama' => $session['nama'] ?? null,
                'gender' => $session['gender'] ?? null,
                'foto_url' => $session['foto_url'] ?? null,
            ];
        }

        while (count($players) < $expectedSize) {
            $players[] = [
                'no_hp' => '',
                'pemain_exists' => false,
                'nama' => null,
                'gender' => null,
                'foto_url' => null,
            ];
        }

        return array_slice($players, 0, max(1, $expectedSize));
    }

    /**
     * @param  array<string, mixed>  $tournament
     * @param  array<string, mixed>  $session
     * @param  array<string, mixed>  $validated
     */
    private function submitGroupRegister(
        Request $request,
        int $id,
        array $tournament,
        array $session,
        array $validated,
        $idKategori,
        int $groupSize,
        string $noHp
    ): RedirectResponse {
        $sessionPlayers = $this->sessionPlayers($session, $groupSize);
        $namaGrup = trim((string) ($validated['nama_grup'] ?? $session['nama_grup'] ?? ''));

        $players = [[
            'nama' => $validated['nama'],
            'no_hp' => $noHp,
            'gender' => $validated['gender'],
            'tgl_lahir' => $validated['tgl_lahir'] ?? null,
            'rating' => 0,
        ]];
        $fotos = [$request->file('foto')];

        for ($n = 2; $n <= $groupSize; $n++) {
            $player = is_array($request->input('player_'.$n)) ? $request->input('player_'.$n) : [];
            $expectedPhone = (string) ($sessionPlayers[$n - 1]['no_hp'] ?? '');
            $submittedPhone = trim((string) ($player['no_hp'] ?? ''));

            if ($expectedPhone === '' || $submittedPhone !== $expectedPhone) {
                return redirect()
                    ->route('public.mahjong-tournaments.register', $id)
                    ->withErrors(['form' => 'Nomor HP pemain '.$n.' tidak sesuai. Silakan periksa ulang.']);
            }

            $players[] = [
                'nama' => trim((string) ($player['nama'] ?? '')),
                'no_hp' => $submittedPhone,
                'gender' => (string) ($player['gender'] ?? ''),
                'tgl_lahir' => $player['tgl_lahir'] ?? null,
                'rating' => 0,
            ];
            $fotos[] = $request->file('foto_'.$n);
        }

        $result = BornpadelMahjongTournaments::registerGroup([
            'id_turnamen' => $id,
            'id_kategori' => $idKategori,
            'nama_grup' => $namaGrup,
            'players' => $players,
        ], $fotos);

        if ($result['error'] !== null) {
            return back()
                ->withInput()
                ->withErrors(['form' => $result['error']]);
        }

        $registerData = is_array($result['data'] ?? null) ? $result['data'] : [];
        $check = BornpadelMahjongTournaments::checkRegistration($id, $noHp, $idKategori);
        $checkData = is_array($check['data'] ?? null) ? $check['data'] : [];
        $group = is_array($registerData['group'] ?? null)
            ? $registerData['group']
            : (is_array($checkData['group'] ?? null) ? $checkData['group'] : null);
        $registration = is_array($checkData['registration'] ?? null) ? $checkData['registration'] : null;
        $pemain = is_array($checkData['pemain'] ?? null) ? $checkData['pemain'] : null;
        $resultPlayers = is_array($registerData['players'] ?? null) ? $registerData['players'] : $players;

        $session = [
            'registration_mode' => 'group',
            'nama_grup' => $registerData['nama_grup'] ?? $namaGrup,
            'no_hp' => $noHp,
            'id_kategori' => $registerData['kategori_id'] ?? $checkData['kategori_id'] ?? $idKategori,
            'registered' => true,
            'pemain_exists' => true,
            'nama' => $pemain['nama'] ?? $validated['nama'],
            'gender' => $pemain['gender'] ?? $validated['gender'],
            'foto_url' => $pemain['foto_url'] ?? ($registerData['foto_url'] ?? null),
            'registration_status' => $registration['status'] ?? $registerData['status'] ?? 'unpaid',
            'peserta_id' => $registration['peserta_id'] ?? $registerData['peserta_id'] ?? null,
            'bukti_bayar_url' => $registration['bukti_bayar_url'] ?? null,
            'players' => $resultPlayers,
            'group' => $group,
            'just_registered' => true,
        ];
        $session = $this->attachSubmittedReceipt($request, $id, $session);
        session()->put($this->registerSessionKey($id), $session);

        return redirect()
            ->route('public.mahjong-tournaments.register.status', $id)
            ->with('success', $result['message'] ?? 'Pendaftaran tim berhasil dikirim.');
    }

    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    private function attachSubmittedReceipt(Request $request, int $id, array $session): array
    {
        $file = $request->file('bukti_bayar');
        if (! $file) {
            return $session;
        }

        $result = BornpadelMahjongTournaments::uploadPaymentReceipt([
            'id_turnamen' => $id,
            'id_kategori' => $session['id_kategori'] ?? null,
            'no_hp' => $session['no_hp'] ?? null,
            'peserta_id' => $session['peserta_id'] ?? null,
        ], $file);

        if ($result['error'] !== null) {
            session()->flash('warning', $result['error']);

            return $session;
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $session['registration_status'] = $data['status'] ?? $session['registration_status'] ?? null;
        $session['bukti_bayar_url'] = $data['bukti_bayar_url'] ?? $session['bukti_bayar_url'] ?? null;
        $session['peserta_id'] = $data['peserta_id'] ?? $session['peserta_id'] ?? null;

        return $session;
    }
}
