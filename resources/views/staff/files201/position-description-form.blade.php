@php
    $defaults = $positionDescriptionDefaults ?? [];
    $supervised = old('supervised', $defaults['supervised'] ?? [['title' => '', 'item' => '']]);
    $duties = old('duties', $defaults['duties'] ?? [['percent' => '', 'duty' => '']]);
    $blankSupervised = ['title' => '', 'item' => ''];
    $blankDuty = ['percent' => '', 'duty' => ''];
    $contacts = old('contacts', $defaults['contacts'] ?? []);
    $unit = old('lg_unit', $defaults['lg_unit'] ?? '');
    $class = old('lg_class', $defaults['lg_class'] ?? '');
@endphp

<div class="px-5 py-5 border-b border-slate-100 bg-slate-50/60">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-900">Position Description Form</h3>
            <p class="text-sm text-slate-500 mt-1">DBM-CSC Form No. 1 (Revised Version No. 1, s. 2017). Fill in the numbered items, then print the form.</p>
        </div>
        @if(($positionDescriptionReady ?? false) && ($positionDescriptionPrintUrl ?? null))
            <a href="{{ $positionDescriptionPrintUrl }}" target="_blank" class="btn-primary btn-sm">Print form</a>
        @endif
    </div>

    @if($positionDescriptionAction ?? null)
        <form method="POST" action="{{ $positionDescriptionAction }}" class="mt-4 space-y-5"
            x-data="{ supervised: @js(array_values($supervised)), duties: @js(array_values($duties)), blankSupervised: @js($blankSupervised), blankDuty: @js($blankDuty) }">
            @csrf
            <input type="hidden" name="category" value="position_description_forms">

            <div>
                <label class="form-label">1. Position title*</label>
                <input type="text" name="position_title" required value="{{ old('position_title', $defaults['position_title'] ?? '') }}" class="form-input">
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">2. Item number*</label>
                    <input type="text" name="item_number" required value="{{ old('item_number', $defaults['item_number'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">3. Salary grade</label>
                    <input type="text" name="salary_grade" value="{{ old('salary_grade', $defaults['salary_grade'] ?? '') }}" class="form-input" placeholder="SG-11">
                </div>
            </div>

            <fieldset>
                <legend class="form-label">4. For local government position, governmental unit and class</legend>
                <div class="flex flex-wrap gap-4 text-sm text-slate-700">
                    <label class="inline-flex items-center gap-2"><input type="radio" name="lg_unit" value="" @checked($unit === '')> Not applicable</label>
                    @foreach(['province' => 'Province', 'city' => 'City', 'municipality' => 'Municipality'] as $value => $label)
                        <label class="inline-flex items-center gap-2"><input type="radio" name="lg_unit" value="{{ $value }}" @checked($unit === $value)> {{ $label }}</label>
                    @endforeach
                </div>
                <div class="mt-2 flex flex-wrap gap-4 text-sm text-slate-700">
                    <label class="inline-flex items-center gap-2"><input type="radio" name="lg_class" value="" @checked($class === '')> Not applicable</label>
                    @foreach(['1st' => '1st Class', '2nd' => '2nd Class', '3rd' => '3rd Class', '4th' => '4th Class', '5th' => '5th Class', '6th' => '6th Class', 'special' => 'Special'] as $value => $label)
                        <label class="inline-flex items-center gap-2"><input type="radio" name="lg_class" value="{{ $value }}" @checked($class === $value)> {{ $label }}</label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">5. Department, corporation or agency / local government*</label>
                    <input type="text" name="department" required value="{{ old('department', $defaults['department'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">6. Bureau or office*</label>
                    <input type="text" name="bureau" required value="{{ old('bureau', $defaults['bureau'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">7. Department / branch / division*</label>
                    <input type="text" name="division" required value="{{ old('division', $defaults['division'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">8. Workstation / place of work*</label>
                    <input type="text" name="workstation" required value="{{ old('workstation', $defaults['workstation'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">9. Present approp act</label>
                    <input type="text" name="present_approp" value="{{ old('present_approp', $defaults['present_approp'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">10. Previous approp act</label>
                    <input type="text" name="previous_approp" value="{{ old('previous_approp', $defaults['previous_approp'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">11. Salary authorized</label>
                    <input type="text" name="salary_authorized" value="{{ old('salary_authorized', $defaults['salary_authorized'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">12. Other compensation</label>
                    <input type="text" name="other_compensation" value="{{ old('other_compensation', $defaults['other_compensation'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">13. Position title of immediate supervisor</label>
                    <input type="text" name="immediate_supervisor" value="{{ old('immediate_supervisor', $defaults['immediate_supervisor'] ?? '') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">14. Position title of next higher supervisor</label>
                    <input type="text" name="next_supervisor" value="{{ old('next_supervisor', $defaults['next_supervisor'] ?? '') }}" class="form-input">
                </div>
            </div>

            <div class="space-y-3">
                <p class="form-label">15. Position title and item of those directly supervised</p>
                <p class="text-xs text-slate-500">If more than seven, list only by their item numbers and titles.</p>
                <template x-for="(row, index) in supervised" :key="index">
                    <div class="grid md:grid-cols-2 gap-3">
                        <input type="text" :name="`supervised[${index}][title]`" x-model="row.title" class="form-input" placeholder="Position title">
                        <div class="flex gap-2">
                            <input type="text" :name="`supervised[${index}][item]`" x-model="row.item" class="form-input" placeholder="Item number">
                            <button type="button" class="btn-secondary btn-sm" x-show="supervised.length > 1" @click="supervised.splice(index, 1)">Remove</button>
                        </div>
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" x-show="supervised.length < 7" @click="supervised.push({...blankSupervised})">Add supervised position</button>
            </div>

            <div>
                <label class="form-label">16. Machine, equipment, tools, etc., used regularly in performance of work</label>
                <textarea name="machines" rows="2" class="form-input">{{ old('machines', $defaults['machines'] ?? '') }}</textarea>
            </div>

            <div>
                <p class="form-label">17. Contacts / clients / stakeholders</p>
                <div class="grid md:grid-cols-2 gap-4 mt-2">
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-slate-700">17a. Internal</p>
                        @foreach(['executive' => 'Executive / Managerial', 'supervisors' => 'Supervisors', 'non_supervisors' => 'Non-Supervisors', 'staff' => 'Staff'] as $key => $label)
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span>{{ $label }}</span>
                                <span class="flex gap-3">
                                    @foreach(['occasional' => 'Occasional', 'frequent' => 'Frequent'] as $value => $freq)
                                        <label class="inline-flex items-center gap-1">
                                            <input type="radio" name="contacts[internal][{{ $key }}]" value="{{ $value }}" @checked(($contacts['internal'][$key] ?? '') === $value)>
                                            {{ $freq }}
                                        </label>
                                    @endforeach
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-slate-700">17b. External</p>
                        @foreach(['general_public' => 'General Public', 'other_agencies' => 'Other Agencies', 'others' => 'Others'] as $key => $label)
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <span>{{ $label }}</span>
                                <span class="flex gap-3">
                                    @foreach(['occasional' => 'Occasional', 'frequent' => 'Frequent'] as $value => $freq)
                                        <label class="inline-flex items-center gap-1">
                                            <input type="radio" name="contacts[external][{{ $key }}]" value="{{ $value }}" @checked(($contacts['external'][$key] ?? '') === $value)>
                                            {{ $freq }}
                                        </label>
                                    @endforeach
                                </span>
                            </div>
                        @endforeach
                        <input type="text" name="others_specify" value="{{ old('others_specify', $defaults['others_specify'] ?? '') }}" class="form-input" placeholder="Others (please specify)">
                    </div>
                </div>
            </div>

            <fieldset>
                <legend class="form-label">18. Working condition</legend>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="work_office" value="1" @checked(old('work_office', $defaults['work_office'] ?? false))> Office Work</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="work_field" value="1" @checked(old('work_field', $defaults['work_field'] ?? false))> Field Work</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" name="work_other" value="1" @checked(old('work_other', $defaults['work_other'] ?? false))> Other/s</label>
                </div>
                <input type="text" name="work_other_specify" value="{{ old('work_other_specify', $defaults['work_other_specify'] ?? '') }}" class="form-input mt-2" placeholder="Other/s (please specify)">
            </fieldset>

            <div>
                <label class="form-label">19. Brief description of the general function of the unit or section</label>
                <textarea name="unit_function" rows="3" class="form-input">{{ old('unit_function', $defaults['unit_function'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="form-label">20. Brief description of the general function of the position (Job Summary)</label>
                <textarea name="job_summary" rows="3" class="form-input">{{ old('job_summary', $defaults['job_summary'] ?? '') }}</textarea>
            </div>

            <div>
                <p class="form-label">21. Qualification standards</p>
                <div class="grid md:grid-cols-2 gap-4 mt-2">
                    <div>
                        <label class="form-label">21a. Education</label>
                        <textarea name="education" rows="2" class="form-input">{{ old('education', $defaults['education'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">21b. Experience</label>
                        <textarea name="experience" rows="2" class="form-input">{{ old('experience', $defaults['experience'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">21c. Training</label>
                        <textarea name="training" rows="2" class="form-input">{{ old('training', $defaults['training'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">21d. Eligibility</label>
                        <textarea name="eligibility" rows="2" class="form-input">{{ old('eligibility', $defaults['eligibility'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">21e. Core competencies</label>
                        <textarea name="core_competencies" rows="2" class="form-input">{{ old('core_competencies', $defaults['core_competencies'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Core competency level</label>
                        <input type="text" name="core_level" value="{{ old('core_level', $defaults['core_level'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">21f. Leadership competencies</label>
                        <textarea name="leadership_competencies" rows="2" class="form-input">{{ old('leadership_competencies', $defaults['leadership_competencies'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Leadership competency level</label>
                        <input type="text" name="leadership_level" value="{{ old('leadership_level', $defaults['leadership_level'] ?? '') }}" class="form-input">
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <p class="form-label">22. Statement of duties and responsibilities*</p>
                <template x-for="(row, index) in duties" :key="index">
                    <div class="grid md:grid-cols-4 gap-3">
                        <input type="text" :name="`duties[${index}][percent]`" x-model="row.percent" required class="form-input" placeholder="% of working time">
                        <div class="md:col-span-3 flex gap-2">
                            <input type="text" :name="`duties[${index}][duty]`" x-model="row.duty" required class="form-input" placeholder="Duty or responsibility">
                            <button type="button" class="btn-secondary btn-sm" x-show="duties.length > 1" @click="duties.splice(index, 1)">Remove</button>
                        </div>
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" x-show="duties.length < 12" @click="duties.push({...blankDuty})">Add duty</button>
                <div>
                    <label class="form-label">Competency level</label>
                    <input type="text" name="duties_level" value="{{ old('duties_level', $defaults['duties_level'] ?? '') }}" class="form-input">
                </div>
            </div>

            <div>
                <p class="form-label">23. Acknowledgment and acceptance</p>
                <p class="text-sm text-slate-500 mb-3">I have received a copy of this position description. It has been discussed with me and I have freely chosen to comply with the performance and behavior/conduct expectations contained herein.</p>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Employee's name*</label>
                        <input type="text" name="employee_name" required value="{{ old('employee_name', $defaults['employee_name'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Employee's date*</label>
                        <input type="date" name="employee_date" required value="{{ old('employee_date', $defaults['employee_date'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Supervisor's name</label>
                        <input type="text" name="supervisor_name" value="{{ old('supervisor_name', $defaults['supervisor_name'] ?? '') }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Supervisor's date</label>
                        <input type="date" name="supervisor_date" value="{{ old('supervisor_date', $defaults['supervisor_date'] ?? '') }}" class="form-input">
                    </div>
                </div>
            </div>

            @foreach($errors->all() as $message)
                <p class="text-xs text-red-600">{{ $message }}</p>
            @endforeach

            <button type="submit" class="btn-primary">Save position description form</button>
        </form>
    @elseif(! ($positionDescriptionReady ?? false))
        <p class="mt-3 text-sm text-slate-500">The employee fills this form from My 201 File.</p>
    @endif
</div>
