@php
  $index = (int) ($index ?? 0);
  $n = $index + 1;
  $player = is_array($player ?? null) ? $player : [];
  $prefix = $n === 1 ? '' : 'player_'.$n;
  $fieldName = function (string $name) use ($prefix) {
      return $prefix === '' ? $name : $prefix.'['.$name.']';
  };
  $dotKey = function (string $name) use ($prefix) {
      return $prefix === '' ? $name : $prefix.'.'.$name;
  };
  $namaValue = old($dotKey('nama'), $player['nama'] ?? '');
  $genderValue = old($dotKey('gender'), $player['gender'] ?? '');
  $tglLahirValue = old($dotKey('tgl_lahir'));
  $phoneValue = old($dotKey('no_hp'), $player['no_hp'] ?? '');
  $fotoName = $n === 1 ? 'foto' : 'foto_'.$n;
  $fotoId = $n === 1 ? 'foto' : 'foto_'.$n;
  $previewId = $n === 1 ? 'fotoPreview' : 'fotoPreview_'.$n;
  $previewImgId = $n === 1 ? 'fotoPreviewImg' : 'fotoPreviewImg_'.$n;
  $exists = ! empty($player['pemain_exists']);
@endphp

<div class="register-player-block {{ $index > 0 ? 'border-top pt-4 mt-1' : '' }} mb-4">
  @if ($showHeading ?? true)
    <h2 class="h6 fw-bold mb-3" style="color: var(--brand-dark);">
      <i class="bi bi-person-circle me-1"></i>Pemain {{ $n }}
    </h2>
  @endif

  @if ($exists)
    <div class="alert alert-info py-2 small">
      Nama dan jenis kelamin sudah diisi dari data pemain. Anda dapat mengubahnya jika perlu.
    </div>
  @endif

  <div class="mb-3">
    <label for="{{ $fotoId }}" class="form-label fw-semibold">
      Profile Picture <span class="text-muted fw-normal">(opsional)</span>
    </label>
    <input
      type="file"
      name="{{ $fotoName }}"
      id="{{ $fotoId }}"
      class="form-control js-foto-input @error($fotoName) is-invalid @enderror"
      accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
      data-preview="#{{ $previewId }}"
      data-preview-img="#{{ $previewImgId }}"
    />
    <div class="form-text">Format JPG, PNG, atau WebP. Maks. 5 MB.</div>
    @error($fotoName)
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="foto-preview" id="{{ $previewId }}">
      <img src="" alt="Pratinjau foto pemain {{ $n }}" id="{{ $previewImgId }}" />
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label fw-semibold">Nomor HP</label>
    <input type="text" class="form-control bg-light" value="{{ $phoneValue }}" readonly />
    <input type="hidden" name="{{ $fieldName('no_hp') }}" value="{{ $phoneValue }}" />
  </div>

  <div class="mb-3">
    <label for="{{ $dotKey('nama') }}" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
    <input
      type="text"
      name="{{ $fieldName('nama') }}"
      id="{{ $dotKey('nama') }}"
      class="form-control @error($dotKey('nama')) is-invalid @enderror"
      value="{{ $namaValue }}"
      placeholder="Masukkan nama lengkap"
      required
      autocomplete="name"
    />
    @error($dotKey('nama'))
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="{{ $dotKey('gender') }}" class="form-label fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
    <select
      name="{{ $fieldName('gender') }}"
      id="{{ $dotKey('gender') }}"
      class="form-select @error($dotKey('gender')) is-invalid @enderror"
      required
    >
      <option value="" disabled {{ $genderValue ? '' : 'selected' }}>Pilih jenis kelamin</option>
      <option value="male" {{ $genderValue === 'male' ? 'selected' : '' }}>Laki-laki</option>
      <option value="female" {{ $genderValue === 'female' ? 'selected' : '' }}>Perempuan</option>
    </select>
    @error($dotKey('gender'))
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>

  <div class="mb-3">
    <label for="{{ $dotKey('tgl_lahir') }}" class="form-label fw-semibold">
      Tanggal Lahir <span class="text-muted fw-normal">(opsional)</span>
    </label>
    <input
      type="date"
      name="{{ $fieldName('tgl_lahir') }}"
      id="{{ $dotKey('tgl_lahir') }}"
      class="form-control @error($dotKey('tgl_lahir')) is-invalid @enderror"
      value="{{ $tglLahirValue }}"
      max="{{ date('Y-m-d', strtotime('-1 day')) }}"
    />
    @error($dotKey('tgl_lahir'))
      <div class="invalid-feedback">{{ $message }}</div>
    @enderror
  </div>
</div>
