@props([
    'name' => 'no_hp',
    'id' => null,
    'label' => 'Nomor HP / WhatsApp',
    'value' => '',
    'required' => true,
    'errorKey' => null,
])

@php
    $phoneService = app(\App\Services\PhoneNumberService::class);
    $parsed = $phoneService->parse($value);
    $countries = $phoneService->countries();
    $inputId = $id ?: $name;
    $countryField = $name.'_country';
    $localField = $name.'_local';
    $errorKey = $errorKey ?? $name;
@endphp

<div class="phone-input-group" data-phone-input data-target-name="{{ $name }}">
    <label for="{{ $inputId }}_local" class="form-label fw-semibold">
        {{ $label }}
        @if ($required)
            <span class="text-danger">*</span>
        @endif
    </label>

    <div class="input-group">
        <select name="{{ $countryField }}"
                id="{{ $inputId }}_country"
                class="form-select phone-country-select @error($errorKey) is-invalid @enderror"
                data-phone-country>
            @foreach ($countries as $country)
                <option value="{{ $country['code'] }}"
                    {{ $parsed['country_code'] === $country['code'] ? 'selected' : '' }}>
                    {{ $country['flag'] }} {{ $country['code'] }}
                </option>
            @endforeach
        </select>

        <input type="tel"
               name="{{ $localField }}"
               id="{{ $inputId }}_local"
               class="form-control @error($errorKey) is-invalid @enderror"
               value="{{ old($localField, $parsed['local_number']) }}"
               placeholder="8123456789"
               inputmode="tel"
               autocomplete="tel-national"
               {{ $required ? 'required' : '' }}
               data-phone-local>

        <input type="hidden"
               name="{{ $name }}"
               value="{{ old($name, $parsed['full'] ?: $value) }}"
               data-phone-hidden>
    </div>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@once
    @push('scripts')
        <script src="{{ asset('public/js/phone-input.js') }}"></script>
    @endpush
@endonce
