<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Meja;
use App\Models\Rental;
use App\Models\RentalAdditionalItem;
use App\Support\RentalCheckout;
use App\Support\RentalInvoice;
use App\Support\RentalPayment;
use App\Support\TokoScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
class RentalHistoryController extends Controller
{
    private const ORDER_COLUMNS = [
        0 => 'rental.id',
        1 => 'rental.waktu_start',
        2 => 'rental.nama_customer',
        3 => 'rental.id',
        4 => 'rental.tipe_customer',
        5 => 'rental.status',
        6 => 'rental.total_harga',
        7 => 'rental.metode_pembayaran',
    ];

    public function index()
    {
        return view('rental.history');
    }

    public function data(Request $request): JsonResponse
    {
        $baseQuery = TokoScope::scopeRentals(Rental::query())
            ->with(['meja.toko']);

        $recordsTotal = (clone $baseQuery)->count();

        $query = clone $baseQuery;

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($q) use ($like) {
                $q->where('rental.nama_customer', 'like', $like)
                    ->orWhere('rental.id', 'like', $like)
                    ->orWhereHas('meja', function ($mq) use ($like) {
                        $mq->where('nama', 'like', $like)
                            ->orWhereHas('toko', function ($tq) use ($like) {
                                $tq->where('nama', 'like', $like);
                            });
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderColumnIndex = (int) $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn = self::ORDER_COLUMNS[$orderColumnIndex] ?? 'rental.waktu_start';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 25);
        if ($length < 1) {
            $length = 25;
        }
        if ($length > 100) {
            $length = 100;
        }

        $rows = $query
            ->orderByRaw($orderColumn.' '.$orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($rows as $rental) {
            $data[] = $this->rowPayload($rental);
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function show(Rental $rental): JsonResponse
    {
        TokoScope::authorizeRental($rental);
        $rental->loadMissing('meja.toko');

        return response()->json([
            'id' => $rental->id,
            'nama_customer' => $rental->nama_customer,
            'tipe_customer' => $rental->tipe_customer,
            'status' => $rental->status,
            'nama_meja' => $rental->meja ? $rental->meja->nama : '—',
            'nama_toko' => $rental->meja && $rental->meja->toko ? $rental->meja->toko->nama : '—',
            'metode_pembayaran' => $rental->metode_pembayaran,
            'jumlah_bayar' => $rental->jumlah_bayar !== null ? (float) $rental->jumlah_bayar : null,
            'total_harga' => (float) $rental->billTotal(),
            'waktu_start' => $rental->waktu_start ? $rental->waktu_start->format('Y-m-d\TH:i') : '',
            'waktu_end' => $rental->waktu_end ? $rental->waktu_end->format('Y-m-d\TH:i') : '',
            'waktu_pembayaran' => $rental->waktu_pembayaran ? $rental->waktu_pembayaran->format('Y-m-d\TH:i') : '',
            'can_invoice' => RentalInvoice::canIssue($rental),
        ]);
    }

    public function update(Request $request, Rental $rental): JsonResponse
    {
        TokoScope::authorizeRental($rental);
        $rental->loadMissing('meja');

        if ($rental->isActive()) {
            return $this->updateActive($request, $rental);
        }

        return $this->updateCompleted($request, $rental);
    }

    public function destroy(Rental $rental): JsonResponse
    {
        TokoScope::authorizeRental($rental);

        DB::transaction(function () use ($rental) {
            $locked = Rental::query()->whereKey($rental->id)->lockForUpdate()->firstOrFail();

            if ($locked->isActive()) {
                Meja::query()
                    ->whereKey($locked->id_meja)
                    ->update(['status' => 'active']);
            }

            if ($locked->bukti_transaksi) {
                $disk = Storage::disk('public');
                if ($disk->exists($locked->bukti_transaksi)) {
                    $disk->delete($locked->bukti_transaksi);
                }
            }

            RentalAdditionalItem::query()->where('id_rental', $locked->id)->delete();
            CashFlow::query()->where('id_rental', $locked->id)->delete();
            $locked->delete();
        });

        return response()->json(['message' => 'Data sewa berhasil dihapus.']);
    }

    private function updateActive(Request $request, Rental $rental): JsonResponse
    {
        $validated = $request->validate([
            'nama_customer' => ['required', 'string', 'max:255'],
            'tipe_customer' => ['required', Rule::in([
                RentalCheckout::CUSTOMER_MEMBER,
                RentalCheckout::CUSTOMER_NON_MEMBER,
            ])],
        ]);

        if (! $rental->meja) {
            abort(404);
        }

        $rate = RentalCheckout::rateForMeja($rental->meja, $validated['tipe_customer']);

        $rental->update([
            'nama_customer' => $validated['nama_customer'],
            'tipe_customer' => $validated['tipe_customer'],
            'harga' => $rate,
        ]);

        return response()->json(['message' => 'Data sewa aktif berhasil diperbarui.']);
    }

    private function updateCompleted(Request $request, Rental $rental): JsonResponse
    {
        $validated = $request->validate([
            'nama_customer' => ['required', 'string', 'max:255'],
            'tipe_customer' => ['required', Rule::in([
                RentalCheckout::CUSTOMER_MEMBER,
                RentalCheckout::CUSTOMER_NON_MEMBER,
            ])],
            'metode_pembayaran' => ['nullable', 'string', 'max:100', Rule::in(['tunai', 'transfer', 'qris', 'kartu', 'lainnya'])],
            'jumlah_bayar' => ['nullable', 'numeric', 'min:0'],
            'total_harga' => ['nullable', 'numeric', 'min:0'],
            'waktu_pembayaran' => ['nullable', 'date'],
        ]);

        $totalHarga = isset($validated['total_harga'])
            ? (float) $validated['total_harga']
            : (float) $rental->billTotal();

        $rental->update([
            'nama_customer' => $validated['nama_customer'],
            'tipe_customer' => $validated['tipe_customer'],
            'total_harga' => $totalHarga,
            'total' => $totalHarga,
        ]);

        if (! empty($validated['metode_pembayaran']) && $validated['jumlah_bayar'] !== null) {
            RentalPayment::saveOnRental(
                $rental,
                $validated['metode_pembayaran'],
                (float) $validated['jumlah_bayar'],
                null,
                ! empty($validated['waktu_pembayaran'])
                    ? \Carbon\Carbon::parse($validated['waktu_pembayaran'])
                    : ($rental->waktu_pembayaran ?? $rental->waktu_end)
            );
        } elseif (! empty($validated['waktu_pembayaran'])) {
            $rental->update(['waktu_pembayaran' => $validated['waktu_pembayaran']]);
            RentalPayment::syncCashFlowWaktuFromRental($rental->fresh());
        }

        $this->syncCashFlowKeterangan($rental->fresh());

        return response()->json(['message' => 'Data sewa berhasil diperbarui.']);
    }

    private function syncCashFlowKeterangan(Rental $rental): void
    {
        $rental->loadMissing('meja.toko');
        $mejaNama = $rental->meja ? $rental->meja->nama : 'Meja';
        $tokoNama = $rental->meja && $rental->meja->toko ? $rental->meja->toko->nama : '';
        $deskripsiSewa = $tokoNama !== ''
            ? "Sewa meja {$mejaNama} ({$tokoNama}) — {$rental->nama_customer}"
            : "Sewa meja {$mejaNama} — {$rental->nama_customer}";

        CashFlow::query()
            ->where('id_rental', $rental->id)
            ->where('kategori_pendapatan', CashFlow::KATEGORI_SEWA_MEJA)
            ->update(['keterangan' => $deskripsiSewa]);

        CashFlow::query()
            ->where('id_rental', $rental->id)
            ->where('kategori_pendapatan', CashFlow::KATEGORI_ADDITIONAL_FB)
            ->update([
                'keterangan' => "Additional Item (F&B) — {$rental->nama_customer} · {$mejaNama}",
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowPayload(Rental $rental): array
    {
        $meja = $rental->meja;
        $toko = $meja ? $meja->toko : null;
        $statusLabel = $rental->status === 'active' ? 'Aktif' : ($rental->status === 'completed' ? 'Selesai' : $rental->status);
        $statusClass = $rental->status === 'active' ? 'warning' : 'success';
        $metode = $rental->metode_pembayaran
            ? CashFlow::metodePembayaranLabel($rental->metode_pembayaran)
            : '—';

        return [
            'id' => $rental->id,
            'waktu_start' => $rental->waktu_start ? $rental->waktu_start->format('d/m/Y H:i') : '—',
            'waktu_end' => $rental->waktu_end ? $rental->waktu_end->format('d/m/Y H:i') : '—',
            'nama_customer' => e($rental->nama_customer),
            'meja_toko' => e(($meja ? $meja->nama : '—').($toko ? ' · '.$toko->nama : '')),
            'tipe_customer' => $rental->isMember() ? 'Member' : 'Non-Member',
            'status_html' => '<span class="badge text-bg-'.$statusClass.'">'.e($statusLabel).'</span>',
            'total_harga' => 'Rp '.number_format($rental->billTotal(), 0, ',', '.'),
            'metode_pembayaran' => e($metode),
            'pembayaran_html' => $this->pembayaranBadge($rental),
            'actions' => $this->actionButtons($rental),
        ];
    }

    private function pembayaranBadge(Rental $rental): string
    {
        switch ($rental->kelengkapanStatus()) {
            case 'lengkap':
                return '<span class="badge text-bg-success">Lengkap</span>';
            case 'sebagian':
                return '<span class="badge text-bg-info text-dark">Sebagian</span>';
            default:
                return '<span class="badge text-bg-warning text-dark">Belum</span>';
        }
    }

    private function actionButtons(Rental $rental): string
    {
        $invoiceUrl = RentalInvoice::canIssue($rental) ? route('rental.invoice', $rental) : '';

        $print = $invoiceUrl !== ''
            ? '<a href="'.e($invoiceUrl).'" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer" title="Cetak invoice" data-no-page-loader><i class="bi bi-printer"></i></a>'
            : '<button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Invoice belum lengkap"><i class="bi bi-printer"></i></button>';

        $edit = '<button type="button" class="btn btn-outline-primary btn-sm btn-rental-edit" data-id="'.(int) $rental->id.'" title="Edit"><i class="bi bi-pencil-square"></i></button>';

        $delete = '<button type="button" class="btn btn-outline-danger btn-sm btn-rental-delete" data-id="'.(int) $rental->id.'" data-customer="'.e($rental->nama_customer).'" title="Hapus"><i class="bi bi-trash"></i></button>';

        return '<div class="btn-group btn-group-sm" role="group">'.$print.$edit.$delete.'</div>';
    }
}
