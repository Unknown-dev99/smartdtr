<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentSignatory;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SignatorySettingsController extends Controller
{
    public function __construct(protected AuditLogService $auditLog) {}

    public function index(): View
    {
        $definitions = DocumentSignatory::definitions();
        $active = [];
        foreach (array_keys($definitions) as $key) {
            $active[$key] = DocumentSignatory::activeRecord($key);
        }
        $schedules = DocumentSignatory::query()
            ->whereNotNull('effective_from')
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('key');

        return view('admin.settings.signatories', compact('definitions', 'active', 'schedules'));
    }

    public function update(Request $request): RedirectResponse
    {
        if (! $this->adminUser()->hasPermission('*')) {
            abort(403);
        }

        $validated = $request->validate([
            'key' => ['required', Rule::in(array_keys(DocumentSignatory::definitions()))],
            'name' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $signatory = DocumentSignatory::query()->updateOrCreate(
            ['key' => $validated['key'], 'effective_from' => $validated['effective_from']],
            [
                'name' => $this->clean($validated['name']),
                'title' => $this->clean($validated['title']),
                'effective_until' => $validated['effective_until'] ?? null,
            ]
        );

        $definition = DocumentSignatory::definitions()[$validated['key']];
        $this->auditLog->log(
            'settings.signatory_scheduled',
            "Scheduled {$definition['label']} effective {$signatory->effective_from->format('F d, Y')}",
            $signatory
        );

        return back()->with(
            'success',
            $signatory->effective_from->isFuture()
                ? 'Signatory change scheduled. It will activate automatically on '.$signatory->effective_from->format('F d, Y').'.'
                : 'Signatory updated and is now active for newly generated papers.'
        );
    }

    public function destroy(DocumentSignatory $signatory): RedirectResponse
    {
        if (! $this->adminUser()->hasPermission('*')) {
            abort(403);
        }
        abort_if($signatory->effective_from === null, 422, 'The original default cannot be removed.');

        $label = DocumentSignatory::definitions()[$signatory->key]['label'] ?? 'signatory';
        $date = $signatory->effective_from->format('F d, Y');
        $signatory->delete();
        $this->auditLog->log('settings.signatory_removed', "Removed {$label} schedule effective {$date}");

        return back()->with('success', 'Scheduled signatory entry removed.');
    }

    public function reset(): RedirectResponse
    {
        if (! $this->adminUser()->hasPermission('*')) {
            abort(403);
        }

        DB::transaction(function (): void {
            DocumentSignatory::query()->delete();
            foreach (DocumentSignatory::definitions() as $key => $definition) {
                DocumentSignatory::query()->create([
                    'key' => $key,
                    'name' => $definition['name'],
                    'title' => $definition['title'],
                ]);
            }
        });

        $this->auditLog->log('settings.signatories_reset', 'Reset document signatories to defaults');

        return back()->with('success', 'Default signatory details restored.');
    }

    private function clean(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
