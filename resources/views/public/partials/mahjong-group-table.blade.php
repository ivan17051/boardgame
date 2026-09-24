@php
  $members = $group['members'] ?? [];
  $rounds = $group['rounds'] ?? [];
  $embedded = ! empty($embedded);
  $showInput = ! $embedded && ! empty($canInputScores) && count($members) === 4;
  $inputMembers = collect($members)->map(function ($member) {
    return [
      'id' => (int) ($member['id_grup_member'] ?? 0),
      'nama' => $member['nama'] ?? 'Pemain',
    ];
  })->values();
  $colCount = 1 + count($members);
  $showAkumulasi = collect($members)->contains(function ($member) {
    return (int) ($member['poin_akumulasi'] ?? 0) !== 0;
  });
  $showAdjustment = collect($members)->contains(function ($member) {
    return (int) ($member['poin_penyesuaian'] ?? 0) !== 0;
  });
  $kind = $group['kind'] ?? (! empty($group['id_meja']) ? 'meja' : 'grup');
  $titleIcon = $kind === 'meja' ? 'bi-table' : 'bi-diagram-3';
@endphp
@if (! $embedded)
<article class="card group-card">
  <div class="group-card-header">
    <div class="d-flex flex-wrap align-items-center gap-2 min-w-0">
      <span class="group-card-title">
        <i class="bi {{ $titleIcon }} me-1"></i>{{ $group['nama'] ?? ($kind === 'meja' ? 'Meja' : 'Grup') }}
      </span>
      @if (! empty($group['babak']))
        <small class="text-secondary">— Babak {{ $group['babak'] }}</small>
      @endif
      @if ($kind === 'meja' && ! empty($group['ronde']))
        <small class="text-secondary">— Seating {{ $group['ronde'] }}</small>
      @endif
      <span class="badge text-bg-info">{{ count($members) }} pemain</span>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      @if ($showInput)
        <button
          type="button"
          class="btn btn-sm btn-primary js-input-poin"
          data-grup-id="{{ $group['id'] ?? '' }}"
          data-grup-name="{{ $group['nama'] ?? 'Grup' }}"
          data-babak="{{ $group['babak'] ?? '' }}"
          data-members='@json($inputMembers)'
          data-no-loading
          title="Input poin untuk semua pemain di {{ $kind === 'meja' ? 'meja' : 'grup' }}"
        >
          <i class="bi bi-pencil-square me-1"></i>Input Poin
        </button>
      @endif
    </div>
  </div>
@endif
  <div class="table-responsive">
    <table class="table table-sm table-bordered table-hover mb-0 align-middle group-score-table">
      <thead>
        <tr>
          <th class="text-center" style="width:4.5rem">Ronde</th>
          @foreach ($members as $member)
            <th class="text-center">
              <div class="fw-semibold">{{ $member['nama'] ?? '—' }}</div>
              @if (! empty($member['tim']))
                <div class="small text-secondary fw-normal">{{ $member['tim'] }}</div>
              @endif
              @if ($showAkumulasi)
                <div class="small text-secondary fw-normal mt-1">Akumulasi {{ (int) ($member['poin_akumulasi'] ?? 0) }}</div>
              @endif
            </th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse ($rounds as $roundIndex => $round)
          <tr>
            <td class="text-center fw-semibold text-secondary">{{ $roundIndex + 1 }}</td>
            @foreach ($members as $memberIndex => $member)
              @php
                $entry = is_array($round) ? ($round[$memberIndex] ?? null) : null;
              @endphp
              <td class="text-center">
                @if (is_array($entry))
                  <span class="badge text-bg-light text-dark border {{ ! empty($entry['is_winner']) ? 'border-warning' : '' }}">
                    @if (! empty($entry['is_winner']))
                      <i class="bi bi-trophy-fill text-warning me-1" title="Pemenang ronde"></i>
                    @endif
                    {{ (int) ($entry['poin'] ?? 0) > 0 ? '+' : '' }}{{ (int) ($entry['poin'] ?? 0) }}
                  </span>
                @else
                  <span class="text-secondary">—</span>
                @endif
              </td>
            @endforeach
          </tr>
        @empty
          <tr>
            <td colspan="{{ $colCount }}" class="text-center text-secondary py-3">
              Belum ada ronde.
            </td>
          </tr>
        @endforelse
      </tbody>
      @if (! empty($members))
        <tfoot>
          @if ($showAdjustment)
            <tr>
              <th>Bonus/Penalti</th>
              @foreach ($members as $member)
                @php $adj = (int) ($member['poin_penyesuaian'] ?? 0); @endphp
                <th class="text-center">{{ $adj > 0 ? '+' : '' }}{{ $adj }}</th>
              @endforeach
            </tr>
          @endif
          <tr>
            <th>Subtotal</th>
            @foreach ($members as $member)
              <th class="text-center">
                {{ (int) ($member['poin_didapat'] ?? 0) }}
                ({{ (int) ($member['menang'] ?? 0) }})
              </th>
            @endforeach
          </tr>
          @if ($showAkumulasi)
            <tr>
              <th>Total</th>
              @foreach ($members as $member)
                <th class="text-center">
                  <span class="badge text-bg-primary">{{ (int) ($member['total_poin'] ?? 0) }}</span>
                </th>
              @endforeach
            </tr>
          @endif
        </tfoot>
      @endif
    </table>
  </div>
@if (! $embedded)
</article>
@endif
