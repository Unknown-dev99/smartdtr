@if(auth('admin')->user()?->canWrite())
<div class="app-card mb-6">
    <div class="app-card-body">
        <form method="POST" action="{{ route('admin.files201.categories.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            @if(isset($personnel) && $personnel instanceof \App\Models\Personnel)
                <input type="hidden" name="personnel_id" value="{{ $personnel->id }}">
            @endif
            <div class="flex-1 min-w-[16rem]">
                <label class="form-label" for="new-201-category">Add category</label>
                <input id="new-201-category" type="text" name="label" value="{{ old('label') }}" required maxlength="120" class="form-input" placeholder="e.g. Barangay clearance">
            </div>
            <button type="submit" class="btn-primary">Add category</button>
        </form>
        <p class="text-xs text-slate-500 mt-2">The new category is added to every employee's 201 file, including the employee portal.</p>
    </div>
</div>
@endif
