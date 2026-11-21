@php
    $d = $personalDataSheetDefaults ?? [];
    $address = fn (string $side, string $field) => old($side.'.'.$field, $d[$side][$field] ?? '');
    $person = fn (string $who, string $field) => old($who.'.'.$field, $d[$who][$field] ?? '');
    $blank = [
        'child' => ['name' => '', 'birth_date' => ''],
        'education' => ['level' => '', 'school' => '', 'course' => '', 'from' => '', 'to' => '', 'units' => '', 'year_graduated' => '', 'honors' => ''],
        'eligibility' => ['career' => '', 'rating' => '', 'exam_date' => '', 'place' => '', 'license_no' => '', 'license_validity' => ''],
        'work' => ['from' => '', 'to' => '', 'position' => '', 'office' => '', 'salary' => '', 'grade' => '', 'status' => '', 'government' => ''],
        'training' => ['title' => '', 'from' => '', 'to' => '', 'hours' => '', 'type' => '', 'sponsor' => ''],
        'voluntary' => ['organization' => '', 'from' => '', 'to' => '', 'hours' => '', 'position' => ''],
    ];
@endphp

<div class="px-5 py-5 border-b border-slate-100 bg-slate-50/60">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-900">Personal Data Sheet</h3>
            <p class="text-sm text-slate-500 mt-1">CS Form No. 212, Revised 2026. Tick the boxes, indicate N/A if not applicable, and do not abbreviate. Print all four pages.</p>
        </div>
        @if(($personalDataSheetReady ?? false) && ($personalDataSheetPrintUrl ?? null))
            <a href="{{ $personalDataSheetPrintUrl }}" target="_blank" class="btn-primary btn-sm">Print form</a>
        @endif
    </div>

    @if($personalDataSheetAction ?? null)
        <form method="POST" action="{{ $personalDataSheetAction }}" class="mt-4 space-y-6"
            x-data="{
                children: @js(array_values(old('children', $d['children'] ?? [$blank['child']]))),
                education: @js(array_values(old('education', $d['education'] ?? []))),
                eligibility: @js(array_values(old('eligibility', $d['eligibility'] ?? [$blank['eligibility']]))),
                work: @js(array_values(old('work', $d['work'] ?? [$blank['work']]))),
                training: @js(array_values(old('training', $d['training'] ?? [$blank['training']]))),
                voluntary: @js(array_values(old('voluntary', $d['voluntary'] ?? [$blank['voluntary']]))),
                skills: @js(array_values(old('skills', $d['skills'] ?? ['']))),
                recognitions: @js(array_values(old('recognitions', $d['recognitions'] ?? ['']))),
                memberships: @js(array_values(old('memberships', $d['memberships'] ?? ['']))),
                blank: @js($blank)
            }">
            @csrf
            <input type="hidden" name="category" value="personal_data_sheet">

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">I. Personal information</h4>
                <div class="grid md:grid-cols-4 gap-3">
                    <div><label class="form-label">1. Surname*</label><input name="surname" required value="{{ old('surname', $d['surname'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">2. First name*</label><input name="first_name" required value="{{ old('first_name', $d['first_name'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">Name extension (JR., SR)</label><input name="name_extension" value="{{ old('name_extension', $d['name_extension'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">Middle name</label><input name="middle_name" value="{{ old('middle_name', $d['middle_name'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">3. Date of birth</label><input type="date" name="birth_date" value="{{ old('birth_date', $d['birth_date'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">4. Place of birth</label><input name="place_of_birth" value="{{ old('place_of_birth', $d['place_of_birth'] ?? '') }}" class="form-input"></div>
                    <div>
                        <label class="form-label">5. Sex at birth</label>
                        <select name="sex" class="form-select">
                            <option value="">Select</option>
                            <option value="male" @selected(old('sex', $d['sex'] ?? '') === 'male')>Male</option>
                            <option value="female" @selected(old('sex', $d['sex'] ?? '') === 'female')>Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">6. Civil status</label>
                        <select name="civil_status" class="form-select">
                            <option value="">Select</option>
                            @foreach(['single' => 'Single', 'married' => 'Married', 'widowed' => 'Widowed', 'separated' => 'Separated', 'others' => 'Other/s'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('civil_status', $d['civil_status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="form-label">Other/s, specify</label><input name="civil_status_other" value="{{ old('civil_status_other', $d['civil_status_other'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">7. Height (m)</label><input name="height" value="{{ old('height', $d['height'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">8. Weight (kg)</label><input name="weight" value="{{ old('weight', $d['weight'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">9. Blood type</label><input name="blood_type" value="{{ old('blood_type', $d['blood_type'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">10. UMID ID no.</label><input name="umid" value="{{ old('umid', $d['umid'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">11. PAG-IBIG ID no.</label><input name="pagibig" value="{{ old('pagibig', $d['pagibig'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">12. PhilHealth no.</label><input name="philhealth" value="{{ old('philhealth', $d['philhealth'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">13. PhilSys Card Number (PCN)</label><input name="philsys" value="{{ old('philsys', $d['philsys'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">14. TIN no.</label><input name="tin" value="{{ old('tin', $d['tin'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">15. Agency employee no.</label><input name="agency_employee_no" value="{{ old('agency_employee_no', $d['agency_employee_no'] ?? '') }}" class="form-input"></div>
                </div>
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="form-label">16. Citizenship</label>
                        <select name="citizenship" class="form-select">
                            <option value="filipino" @selected(old('citizenship', $d['citizenship'] ?? 'filipino') === 'filipino')>Filipino</option>
                            <option value="dual" @selected(old('citizenship', $d['citizenship'] ?? '') === 'dual')>Dual Citizenship</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">If dual citizenship</label>
                        <select name="dual_by" class="form-select">
                            <option value="">Select</option>
                            <option value="birth" @selected(old('dual_by', $d['dual_by'] ?? '') === 'birth')>by birth</option>
                            <option value="naturalization" @selected(old('dual_by', $d['dual_by'] ?? '') === 'naturalization')>by naturalization</option>
                        </select>
                    </div>
                    <div><label class="form-label">Please indicate country</label><input name="citizenship_country" value="{{ old('citizenship_country', $d['citizenship_country'] ?? '') }}" class="form-input"></div>
                </div>
                @foreach(['residential' => '17. Residential address', 'permanent' => '18. Permanent address'] as $side => $title)
                    <div>
                        <p class="form-label">{{ $title }}</p>
                        <div class="grid md:grid-cols-4 gap-3">
                            @foreach(['house' => 'House/Block/Lot No.', 'street' => 'Street', 'subdivision' => 'Subdivision/Village', 'barangay' => 'Barangay', 'city' => 'City/Municipality', 'province' => 'Province', 'zip' => 'ZIP code'] as $field => $label)
                                <div><label class="form-label">{{ $label }}</label><input name="{{ $side }}[{{ $field }}]" value="{{ $address($side, $field) }}" class="form-input"></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="grid md:grid-cols-3 gap-3">
                    <div><label class="form-label">19. Telephone no.</label><input name="telephone" value="{{ old('telephone', $d['telephone'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">20. Mobile no.</label><input name="mobile" value="{{ old('mobile', $d['mobile'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">21. E-mail address</label><input name="email" value="{{ old('email', $d['email'] ?? '') }}" class="form-input"></div>
                </div>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">II. Family background</h4>
                <div class="grid md:grid-cols-4 gap-3">
                    @foreach(['surname' => "22. Spouse's surname", 'first_name' => 'First name', 'extension' => 'Name extension', 'middle_name' => 'Middle name', 'occupation' => 'Occupation', 'employer' => 'Employer/Business name', 'business_address' => 'Business address', 'telephone' => 'Telephone no.'] as $field => $label)
                        <div><label class="form-label">{{ $label }}</label><input name="spouse[{{ $field }}]" value="{{ $person('spouse', $field) }}" class="form-input"></div>
                    @endforeach
                </div>
                <p class="form-label">23. Name of children</p>
                <template x-for="(row, index) in children" :key="index">
                    <div class="grid md:grid-cols-2 gap-3">
                        <input :name="`children[${index}][name]`" x-model="row.name" class="form-input" placeholder="Full name">
                        <div class="flex gap-2">
                            <input type="date" :name="`children[${index}][birth_date]`" x-model="row.birth_date" class="form-input">
                            <button type="button" class="btn-secondary btn-sm" x-show="children.length > 1" @click="children.splice(index, 1)">Remove</button>
                        </div>
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" @click="children.push({...blank.child})">Add child</button>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="grid grid-cols-2 gap-3">
                        <p class="col-span-2 form-label">24. Father's name</p>
                        @foreach(['surname' => 'Surname', 'first_name' => 'First name', 'extension' => 'Name extension', 'middle_name' => 'Middle name'] as $field => $label)
                            <div><label class="form-label">{{ $label }}</label><input name="father[{{ $field }}]" value="{{ $person('father', $field) }}" class="form-input"></div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <p class="col-span-2 form-label">25. Mother's maiden name</p>
                        @foreach(['surname' => 'Surname', 'first_name' => 'First name', 'middle_name' => 'Middle name'] as $field => $label)
                            <div><label class="form-label">{{ $label }}</label><input name="mother[{{ $field }}]" value="{{ $person('mother', $field) }}" class="form-input"></div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">III. Educational background</h4>
                <template x-for="(row, index) in education" :key="index">
                    <div class="grid md:grid-cols-4 gap-2">
                        <input :name="`education[${index}][level]`" x-model="row.level" class="form-input" placeholder="Level">
                        <input :name="`education[${index}][school]`" x-model="row.school" class="form-input" placeholder="Name of school">
                        <input :name="`education[${index}][course]`" x-model="row.course" class="form-input" placeholder="Degree / course">
                        <input :name="`education[${index}][from]`" x-model="row.from" class="form-input" placeholder="From (yyyy)">
                        <input :name="`education[${index}][to]`" x-model="row.to" class="form-input" placeholder="To (yyyy)">
                        <input :name="`education[${index}][units]`" x-model="row.units" class="form-input" placeholder="Units earned">
                        <input :name="`education[${index}][year_graduated]`" x-model="row.year_graduated" class="form-input" placeholder="Year graduated">
                        <input :name="`education[${index}][honors]`" x-model="row.honors" class="form-input" placeholder="Scholarship / honors">
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" @click="education.push({...blank.education})">Add another school</button>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">IV. Civil service eligibility</h4>
                <template x-for="(row, index) in eligibility" :key="index">
                    <div class="grid md:grid-cols-3 gap-2">
                        <input :name="`eligibility[${index}][career]`" x-model="row.career" class="form-input md:col-span-3" placeholder="Career service / RA 1080 / CES / CSEE">
                        <input :name="`eligibility[${index}][rating]`" x-model="row.rating" class="form-input" placeholder="Rating">
                        <input type="date" :name="`eligibility[${index}][exam_date]`" x-model="row.exam_date" class="form-input">
                        <input :name="`eligibility[${index}][place]`" x-model="row.place" class="form-input" placeholder="Place of examination">
                        <input :name="`eligibility[${index}][license_no]`" x-model="row.license_no" class="form-input" placeholder="License number">
                        <input type="date" :name="`eligibility[${index}][license_validity]`" x-model="row.license_validity" class="form-input">
                        <button type="button" class="btn-secondary btn-sm" x-show="eligibility.length > 1" @click="eligibility.splice(index, 1)">Remove</button>
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" @click="eligibility.push({...blank.eligibility})">Add eligibility</button>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">V. Work experience</h4>
                <p class="text-xs text-slate-500">Include private employment. Start from the most recent. Describe the duties on the Work Experience Sheet.</p>
                <template x-for="(row, index) in work" :key="index">
                    <div class="grid md:grid-cols-4 gap-2">
                        <input type="date" :name="`work[${index}][from]`" x-model="row.from" class="form-input">
                        <input :name="`work[${index}][to]`" x-model="row.to" class="form-input" placeholder="To (or Present)">
                        <input :name="`work[${index}][position]`" x-model="row.position" class="form-input" placeholder="Position title">
                        <input :name="`work[${index}][office]`" x-model="row.office" class="form-input" placeholder="Department / agency / company">
                        <input :name="`work[${index}][salary]`" x-model="row.salary" class="form-input" placeholder="Monthly salary">
                        <input :name="`work[${index}][grade]`" x-model="row.grade" class="form-input" placeholder="Salary grade (00-0)">
                        <input :name="`work[${index}][status]`" x-model="row.status" class="form-input" placeholder="Status of appointment">
                        <select :name="`work[${index}][government]`" x-model="row.government" class="form-select">
                            <option value="">Gov't service</option>
                            <option value="Y">Y</option>
                            <option value="N">N</option>
                        </select>
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" @click="work.push({...blank.work})">Add work experience</button>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">VI. Learning and development</h4>
                <template x-for="(row, index) in training" :key="index">
                    <div class="grid md:grid-cols-3 gap-2">
                        <input :name="`training[${index}][title]`" x-model="row.title" class="form-input md:col-span-3" placeholder="Title of training program">
                        <input type="date" :name="`training[${index}][from]`" x-model="row.from" class="form-input">
                        <input type="date" :name="`training[${index}][to]`" x-model="row.to" class="form-input">
                        <input :name="`training[${index}][hours]`" x-model="row.hours" class="form-input" placeholder="Number of hours">
                        <input :name="`training[${index}][type]`" x-model="row.type" class="form-input" placeholder="Managerial / Supervisory / Technical">
                        <input :name="`training[${index}][sponsor]`" x-model="row.sponsor" class="form-input" placeholder="Conducted / sponsored by">
                        <button type="button" class="btn-secondary btn-sm" x-show="training.length > 1" @click="training.splice(index, 1)">Remove</button>
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" @click="training.push({...blank.training})">Add training</button>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">VII. Voluntary work</h4>
                <template x-for="(row, index) in voluntary" :key="index">
                    <div class="grid md:grid-cols-3 gap-2">
                        <input :name="`voluntary[${index}][organization]`" x-model="row.organization" class="form-input md:col-span-3" placeholder="Name and address of organization">
                        <input type="date" :name="`voluntary[${index}][from]`" x-model="row.from" class="form-input">
                        <input type="date" :name="`voluntary[${index}][to]`" x-model="row.to" class="form-input">
                        <input :name="`voluntary[${index}][hours]`" x-model="row.hours" class="form-input" placeholder="Number of hours">
                        <input :name="`voluntary[${index}][position]`" x-model="row.position" class="form-input md:col-span-2" placeholder="Position / nature of work">
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" @click="voluntary.push({...blank.voluntary})">Add voluntary work</button>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">VIII. Other information</h4>
                <div class="grid md:grid-cols-3 gap-4">
                    @foreach(['skills' => '31. Special skills and hobbies', 'recognitions' => '32. Non-academic distinctions / recognition', 'memberships' => '33. Membership in association/organization'] as $key => $title)
                        <div class="space-y-2">
                            <p class="form-label">{{ $title }}</p>
                            <template x-for="(item, index) in {{ $key }}" :key="index">
                                <input :name="`{{ $key }}[${index}]`" x-model="{{ $key }}[index]" class="form-input">
                            </template>
                            <button type="button" class="btn-secondary btn-sm" @click="{{ $key }}.push('')">Add</button>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">Questions 34 to 40</h4>
                @foreach([
                    'q34a' => '34.a Related within the third degree?',
                    'q34b' => '34.b Related within the fourth degree (LGU career)?',
                    'q35a' => '35.a Found guilty of an administrative offense?',
                    'q35b' => '35.b Criminally charged before any court?',
                    'q36' => '36. Convicted of any crime or violation of law?',
                    'q37' => '37. Separated from the service?',
                    'q38a' => '38.a Candidate in a national or local election within the last year?',
                    'q38b' => '38.b Resigned during the three-month period before the last election?',
                    'q39' => '39. Immigrant or permanent resident of another country?',
                    'q40a' => '40.a Member of any indigenous group?',
                    'q40b' => '40.b Person with disability?',
                    'q40c' => '40.c Solo parent?',
                ] as $name => $label)
                    <div class="grid md:grid-cols-3 gap-3 items-end">
                        <div class="md:col-span-2">
                            <label class="form-label">{{ $label }}</label>
                            <select name="{{ $name }}" class="form-select">
                                <option value="">Select</option>
                                <option value="yes" @selected(old($name, $d[$name] ?? '') === 'yes')>YES</option>
                                <option value="no" @selected(old($name, $d[$name] ?? '') === 'no')>NO</option>
                            </select>
                        </div>
                        @if(in_array($name, ['q40b', 'q40c'], true))
                            <div><label class="form-label">ID no.</label><input name="{{ $name }}_id" value="{{ old($name.'_id', $d[$name.'_id'] ?? '') }}" class="form-input"></div>
                        @else
                            <div><label class="form-label">If YES, give details</label><input name="{{ $name }}_details" value="{{ old($name.'_details', $d[$name.'_details'] ?? '') }}" class="form-input"></div>
                        @endif
                    </div>
                @endforeach
                <div class="grid md:grid-cols-2 gap-3">
                    <div><label class="form-label">35.b Date filed</label><input type="date" name="q35b_date" value="{{ old('q35b_date', $d['q35b_date'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">35.b Status of case/s</label><input name="q35b_status" value="{{ old('q35b_status', $d['q35b_status'] ?? '') }}" class="form-input"></div>
                </div>
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">41. References</h4>
                @foreach(range(0, 2) as $index)
                    <div class="grid md:grid-cols-3 gap-3">
                        <input name="references[{{ $index }}][name]" value="{{ old('references.'.$index.'.name', $d['references'][$index]['name'] ?? '') }}" class="form-input" placeholder="Name">
                        <input name="references[{{ $index }}][address]" value="{{ old('references.'.$index.'.address', $d['references'][$index]['address'] ?? '') }}" class="form-input" placeholder="Office / residential address">
                        <input name="references[{{ $index }}][contact]" value="{{ old('references.'.$index.'.contact', $d['references'][$index]['contact'] ?? '') }}" class="form-input" placeholder="Contact no. and/or email">
                    </div>
                @endforeach
            </section>

            <section class="space-y-3">
                <h4 class="text-sm font-semibold text-slate-800">42. Declaration</h4>
                <p class="text-sm text-slate-500">I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct, and complete statement.</p>
                <div class="grid md:grid-cols-2 gap-3">
                    <div><label class="form-label">Government issued ID</label><input name="gov_id" value="{{ old('gov_id', $d['gov_id'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">ID/License/Passport no.</label><input name="gov_id_no" value="{{ old('gov_id_no', $d['gov_id_no'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">Date/Place of issuance</label><input name="gov_id_issuance" value="{{ old('gov_id_issuance', $d['gov_id_issuance'] ?? '') }}" class="form-input"></div>
                    <div><label class="form-label">Date accomplished</label><input type="date" name="date_accomplished" value="{{ old('date_accomplished', $d['date_accomplished'] ?? '') }}" class="form-input"></div>
                </div>
            </section>

            @foreach($errors->all() as $message)
                <p class="text-xs text-red-600">{{ $message }}</p>
            @endforeach
            <button type="submit" class="btn-primary">Save personal data sheet</button>
        </form>
    @elseif(! ($personalDataSheetReady ?? false))
        <p class="mt-3 text-sm text-slate-500">The employee fills this form from My 201 File.</p>
    @endif
</div>
