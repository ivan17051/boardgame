<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\Toko;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalController extends Controller
{
    public function index()
    {
        $tokos = Toko::query()
            ->with([
                'meja' => function ($q) {
                    $q->orderBy('nama')->with('activeRental');
                },
            ])
            ->orderBy('nama')
            ->get();

        $mejasAvailable = Meja::query()
            ->with('toko')
            ->where('status', 'active')
            ->orderBy('id_toko')
            ->orderBy('nama')
            ->get();

        return view('rental.index', compact('tokos', 'mejasAvailable'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_meja' => ['required', 'integer', 'exists:m_meja,id'],
            'nama_customer' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated) {
            $meja = Meja::query()
                ->whereKey($validated['id_meja'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $meja) {
                throw ValidationException::withMessages([
                    'id_meja' => ['Meja tidak tersedia atau sedang disewa.'],
                ]);
            }

            $now = now();

            Rental::query()->create([
                'id_meja' => $meja->id,
                'nama_customer' => $validated['nama_customer'],
                'waktu_start' => $now,
                'waktu_end' => null,
                'total_durasi' => null,
                'harga' => $meja->harga,
                'total_harga' => null,
                'status' => 'active',
                'idc' => auth()->user()->id ?? 0,
                'idm' => auth()->user()->id ?? 0,
            ]);

            $meja->update(['status' => 'rented']);
        });

        return response()->json(['message' => 'Sewa dimulai.']);
    }

    public function checkoutPreview(Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        $rental->loadMissing('meja.toko');

        $calc = $this->computeCheckoutTotals($rental);

        return response()->json([
            'rental_id' => $rental->id,
            'nama_meja' => $rental->meja->nama,
            'nama_toko' => $rental->meja->toko->nama ?? '',
            'nama_customer' => $rental->nama_customer,
            'waktu_start' => $rental->waktu_start->format('d/m/Y H:i:s'),
            'durasi_menit' => $calc['total_minutes'],
            'durasi_menit_formatted' => number_format($calc['total_minutes'], 2, ',', '.'),
            'harga_per_jam' => (float) $rental->harga,
            'harga_per_jam_formatted' => number_format((float) $rental->harga, 3, ',', '.'),
            'total_harga' => $calc['total_harga'],
            'total_harga_formatted' => number_format($calc['total_harga'], 3, ',', '.'),
            'breakdown_html' => $calc['breakdown_html'],
        ]);
    }

    public function checkout(Rental $rental): JsonResponse
    {
        if (! $rental->isActive()) {
            abort(404);
        }

        DB::transaction(function () use ($rental) {
            $locked = Rental::query()
                ->whereKey($rental->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                abort(404);
            }

            $calc = $this->computeCheckoutTotals($locked);
            $now = now();

            $locked->update([
                'waktu_end' => $now,
                'total_durasi' => $calc['total_minutes'],
                'total_harga' => $calc['total_harga'],
                'status' => 'completed',
            ]);

            Meja::query()
                ->whereKey($locked->id_meja)
                ->lockForUpdate()
                ->update(['status' => 'active']);

            $locked->loadMissing('meja.toko');
            $mejaNama = $locked->meja->nama ?? 'Meja';
            $tokoNama = $locked->meja->toko->nama ?? '';
            $deskripsi = $tokoNama !== ''
                ? "Sewa meja {$mejaNama} ({$tokoNama}) — {$locked->nama_customer}"
                : "Sewa meja {$mejaNama} — {$locked->nama_customer}";

            CashFlow::query()->firstOrCreate(
                ['id_rental' => $locked->id],
                [
                    'tipe_transaksi' => 'income',
                    'total' => $calc['total_harga'],
                    'keterangan' => $deskripsi,
                    'metode_pembayaran' => null,
                    'waktu_pembayaran' => $now,
                    'idc' => auth()->id(),
                    'idm' => auth()->id(),
                    'doc' => $now,
                    'dom' => $now,
                ]
            );
        });

        return response()->json(['message' => 'Sewa selesai. Meja dikembalikan ke aktif.']);
    }

    /**
     * @return array{total_minutes: float, total_harga: float, breakdown_html: string}
     */
    private function computeCheckoutTotals(Rental $rental): array
    {
        $start = $rental->waktu_start;
        $now = now();
        $totalSeconds = max(0, $now->getTimestamp() - $start->getTimestamp());
        $totalMinutes = $totalSeconds / 60;
        $hargaPerJam = (float) $rental->harga;
        $totalHarga = ($totalMinutes / 60) * $hargaPerJam;

        $menitStr = number_format($totalMinutes, 2, ',', '.');
        $hargaStr = number_format($hargaPerJam, 3, ',', '.');
        $totalStr = number_format($totalHarga, 3, ',', '.');

        $jamDecimal = number_format($totalMinutes / 60, 4, ',', '.');
        $breakdownHtml = '<ul class="mb-0 ps-3">'
            . '<li><strong>Total durasi</strong>: '.$menitStr.' menit (dari mulai hingga sekarang)</li>'
            . '<li><strong>Tarif meja</strong>: Rp '.$hargaStr.' / jam</li>'
            . '<li><strong>Rumus</strong>: (total durasi menit ÷ 60) × tarif per jam = ('.$menitStr.' ÷ 60) × '.$hargaStr.'</li>'
            . '<li><strong>Jam pemakaian setara</strong>: '.$jamDecimal.' jam</li>'
            . '<li class="mt-2"><strong>Total tagihan</strong>: <span class="text-primary">Rp '.$totalStr.'</span></li>'
            . '</ul>';

        return [
            'total_minutes' => round($totalMinutes, 2),
            'total_harga' => round($totalHarga, 3),
            'breakdown_html' => $breakdownHtml,
        ];
    }
}
