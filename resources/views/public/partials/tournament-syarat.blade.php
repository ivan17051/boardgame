@if (! empty($tournament['syarat']))
@once
@push('styles')
<style>
  .guest-tournament-syarat {
    max-width: 36rem;
    margin: 0.85rem auto 0;
    width: 100%;
    text-align: start;
    background: #fff;
    border: 1px solid rgba(0, 97, 49, 0.12);
    border-radius: 0.85rem;
    box-shadow: 0 2px 12px rgba(0, 60, 30, 0.04);
  }
  .guest-tournament-syarat .card-body {
    padding: 0.85rem 1rem;
  }
  .guest-tournament-syarat__label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6c757d;
    margin-bottom: 0.4rem;
  }
  .guest-tournament-syarat__text {
    font-size: 0.875rem;
    color: #495057;
    white-space: pre-line;
    line-height: 1.45;
  }
</style>
@endpush
@endonce
    <div class="card guest-tournament-syarat">
      <div class="card-body">
        <div class="guest-tournament-syarat__label">
          <i class="bi bi-card-text me-1" aria-hidden="true"></i>Syarat &amp; Ketentuan
        </div>
        <div class="guest-tournament-syarat__text">{{ $tournament['syarat'] }}</div>
      </div>
    </div>
@endif
