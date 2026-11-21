@props([
    'regular' => ['am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00'],
    'saturday' => ['in' => '08:00', 'out' => '12:00'],
])

@php
    $regular = array_merge(['am_in' => '08:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'pm_out' => '17:00'], $regular);
    $saturday = array_merge(['in' => '08:00', 'out' => '12:00'], $saturday);
@endphp

<div class="form-group">
    <label class="form-label">Official Hours (Regular)</label>
    <div class="time-setter">
        <label class="time-setter-slot">
            <span>AM In</span>
            <input type="time" name="regular_am_in" value="{{ old('regular_am_in', $regular['am_in']) }}" step="60" class="form-input time-setter-input">
        </label>
        <span class="time-setter-dash" aria-hidden="true">–</span>
        <label class="time-setter-slot">
            <span>AM Out</span>
            <input type="time" name="regular_am_out" value="{{ old('regular_am_out', $regular['am_out']) }}" step="60" class="form-input time-setter-input">
        </label>
        <span class="time-setter-amp" aria-hidden="true">&amp;</span>
        <label class="time-setter-slot">
            <span>PM In</span>
            <input type="time" name="regular_pm_in" value="{{ old('regular_pm_in', $regular['pm_in']) }}" step="60" class="form-input time-setter-input">
        </label>
        <span class="time-setter-dash" aria-hidden="true">–</span>
        <label class="time-setter-slot">
            <span>PM Out</span>
            <input type="time" name="regular_pm_out" value="{{ old('regular_pm_out', $regular['pm_out']) }}" step="60" class="form-input time-setter-input">
        </label>
    </div>
    <p class="text-xs text-slate-400 mt-1">Weekdays: morning and afternoon. Click a time to open the clock.</p>
</div>

<div class="form-group">
    <label class="form-label">Official Hours (Saturday)</label>
    <div class="time-setter">
        <label class="time-setter-slot">
            <span>In</span>
            <input type="time" name="saturday_in" value="{{ old('saturday_in', $saturday['in']) }}" step="60" class="form-input time-setter-input">
        </label>
        <span class="time-setter-dash" aria-hidden="true">–</span>
        <label class="time-setter-slot">
            <span>Out</span>
            <input type="time" name="saturday_out" value="{{ old('saturday_out', $saturday['out']) }}" step="60" class="form-input time-setter-input">
        </label>
    </div>
    <p class="text-xs text-slate-400 mt-1">Saturday morning window. Click a time to open the clock.</p>
</div>
