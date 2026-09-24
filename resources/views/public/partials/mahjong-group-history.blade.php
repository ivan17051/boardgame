@php
  $history = $groupHistory ?? [];
  $historyKind = $groupHistoryKind ?? 'babak';
  $historyTitle = $historyKind === 'meja' ? 'Riwayat Meja' : 'Riwayat Babak';
  $unitLabel = $historyKind === 'meja' ? 'meja' : 'grup';
@endphp

@if (! empty($history))
  <div class="card group-card group-history-card">
    <div class="group-card-header">
      <span class="group-card-title">
        <i class="bi bi-clock-history me-1"></i>{{ $historyTitle }}
      </span>
    </div>
    <div class="p-3">
      <div class="group-history-tabs" role="tablist">
        @foreach ($history as $sectionIndex => $section)
          <button
            type="button"
            class="group-history-tab {{ $sectionIndex === 0 ? 'is-active' : '' }}"
            data-history-target="history-babak-{{ $section['babak'] ?? $sectionIndex }}"
            data-no-loading
          >
            Babak {{ $section['babak'] ?? ($sectionIndex + 1) }}
          </button>
        @endforeach
      </div>

      @foreach ($history as $sectionIndex => $section)
        <div
          class="group-history-pane {{ $sectionIndex === 0 ? 'is-active' : '' }}"
          id="history-babak-{{ $section['babak'] ?? $sectionIndex }}"
        >
          @forelse (($section['rondes'] ?? []) as $rondeSection)
            <div class="mb-3">
              <h6 class="text-secondary text-uppercase small mb-2">
                @if ($historyKind === 'meja')
                  Ronde seating {{ $rondeSection['ronde'] ?? '—' }}
                @else
                  Ronde {{ $rondeSection['ronde'] ?? '—' }}
                @endif
                <span class="fw-normal text-lowercase">
                  — {{ count($rondeSection['groups'] ?? []) }} {{ $unitLabel }}
                </span>
              </h6>
              @foreach (($rondeSection['groups'] ?? []) as $historyGroup)
                <details class="group-history-item">
                  <summary>
                    <span class="d-flex flex-wrap align-items-center gap-2 w-100">
                      <span>
                        <i class="bi {{ ($historyGroup['kind'] ?? '') === 'meja' ? 'bi-table' : 'bi-diagram-3' }} me-1"></i>
                        {{ $historyGroup['nama'] ?? ($unitLabel === 'meja' ? 'Meja' : 'Grup') }}
                      </span>
                      <span class="badge text-bg-secondary ms-auto">
                        {{ count($historyGroup['members'] ?? []) }} pemain
                      </span>
                    </span>
                  </summary>
                  <div class="group-history-item-body">
                    @include('public.partials.mahjong-group-table', [
                      'group' => $historyGroup,
                      'canInputScores' => false,
                      'embedded' => true,
                    ])
                  </div>
                </details>
              @endforeach
            </div>
          @empty
            <p class="text-secondary small mb-0">Tidak ada ronde tersimpan untuk babak ini.</p>
          @endforelse
        </div>
      @endforeach
    </div>
  </div>
@endif
