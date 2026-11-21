@php
    $requests = collect($clearanceRequests ?? []);
    $defaults = $clearanceDefaults ?? [];
    $value = fn (string $key, mixed $fallback = '') => old($key, $defaults[$key] ?? $fallback);
@endphp

<div class="px-5 py-5 border-b border-slate-100 bg-slate-50/60">
    <h3 class="font-semibold text-slate-900">CSC Request for Clearance</h3>
    <p class="text-sm text-slate-500 mt-1">Fill this in every year. Saving the same year updates that copy. Print it and leave the CSC section blank for their office to complete.</p>

    @if($requests->isNotEmpty())
        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Date</th>
                        <th>Purpose</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $item)
                        <tr>
                            <td class="font-medium">{{ $item->year }}</td>
                            <td class="text-sm text-slate-600">{{ $item->request_date?->format('m/d/Y') }}</td>
                            <td class="text-sm text-slate-600">{{ $item->purposeLabel() }}</td>
                            <td class="text-right whitespace-nowrap space-x-2">
                                @if($clearancePrintRoute)
                                    <a href="{{ route($clearancePrintRoute, array_merge($routeParams ?? [], ['clearance' => $item])) }}" target="_blank" class="btn-primary btn-sm">Print form</a>
                                @endif
                                @if($clearanceAction)
                                    <a href="{{ route('staff.files201.index', ['clearance_year' => $item->year]) }}" class="btn-secondary btn-sm">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($clearanceAction)
        <form method="POST" action="{{ $clearanceAction }}" class="mt-4 grid md:grid-cols-2 gap-4">
            @csrf
            <input type="hidden" name="category" value="clearance_property_money_csc7">
            <div>
                <label class="form-label">Year*</label>
                <input type="number" name="year" min="2000" max="2100" required value="{{ $value('year') }}" class="form-input">
                @error('year')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Date*</label>
                <input type="date" name="request_date" required value="{{ $value('request_date') }}" class="form-input">
                @error('request_date')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Lastname*</label>
                <input type="text" name="last_name" required value="{{ $value('last_name') }}" class="form-input">
                @error('last_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Firstname*</label>
                <input type="text" name="first_name" required value="{{ $value('first_name') }}" class="form-input">
                @error('first_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">M.I</label>
                <input type="text" name="middle_initial" maxlength="10" value="{{ $value('middle_initial') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Contact No.*</label>
                <input type="text" name="contact_no" required value="{{ $value('contact_no') }}" class="form-input">
                @error('contact_no')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Home Address*</label>
                <input type="text" name="home_address" required value="{{ $value('home_address') }}" class="form-input">
                @error('home_address')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Email Address*</label>
                <input type="email" name="email" required value="{{ $value('email') }}" class="form-input">
                @error('email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Present Position*</label>
                <input type="text" name="present_position" required value="{{ $value('present_position') }}" class="form-input">
                @error('present_position')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Name of Agency*</label>
                <input type="text" name="agency_name" required value="{{ $value('agency_name') }}" class="form-input">
                @error('agency_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Address of Agency*</label>
                <input type="text" name="agency_address" required value="{{ $value('agency_address') }}" class="form-input">
                @error('agency_address')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Purpose* <span class="text-slate-400 font-normal">(put an X on the printed form)</span></label>
                <div class="flex flex-wrap gap-4 mt-1">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="purpose" value="promotion" @checked($value('purpose') === 'promotion') required>
                        Promotion
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="purpose" value="retirement" @checked($value('purpose') === 'retirement')>
                        Retirement
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="purpose" value="others" @checked($value('purpose') === 'others')>
                        Others
                    </label>
                </div>
                @error('purpose')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Others, please specify</label>
                <input type="text" name="purpose_other" value="{{ $value('purpose_other') }}" class="form-input" placeholder="Only if Others is selected">
                @error('purpose_other')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="btn-primary">Save clearance form</button>
            </div>
        </form>
    @elseif($requests->isEmpty())
        <p class="mt-3 text-sm text-slate-500">The employee fills this form from My 201 File.</p>
    @endif
</div>
