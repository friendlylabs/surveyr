<div class="">
    <table class="table fs-9 mb-0 border-translucent">
        <thead>
            <tr>
                <th></th>
                <th>Form Name</th>
                <th>Reports</th>
                @if($collabCol)<th class="text-center">Collaborators</th>@endif
                <th class="text-center">Status</th>
                <th>Modified</th>
                <th class="text-end"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($forms as $form)
                <tr>
                    <td>
                        <!-- TODO: bulky form actions -->
                        <input type="checkbox" class="formChecklist d-none" name="formsChecklist[]">
                    </td>
                    <td class="p-1">
                        <div style="max-width: 450px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <a class="fw-bold" href="@route('forms.submissions', $form->id, $form->slug)">
                                {{ ucfirst(strtolower($form->title)) }}
                            </a> <br>
                            <span class="text-body-tertiary fs-9">
                                {{ !$form->description ? $form->title : substring($form->description, 100) }}
                            </span>
                        </div>
                    </td>
                    <td class="">
                        <a href="@route('reports.list', $form->id)" class="fw-semibold">
                            <b>{{ $form->reports->count() }}</b> Reports
                        </a>
                    </td>
                    @if($collabCol)
                        <td class="text-center">
                            <div class="avatar-group avatar-group-dense d-block mx-auto">
                                <div class="avatar avatar-s rounded-circle">
                                    <img class="rounded-circle" src="{{ urlPath($form->user->avatar) }}" alt="{{ $form->user->fullname }}">
                                </div>
                                @if(count($form->collaborators) > 0)
                                    @php 
                                        $collaborators = \App\Models\User::whereIn('id', $form->collaborators)->get();
                                        $shownCollaborators = $collaborators->take(2); 
                                    @endphp
                                    @foreach($shownCollaborators as $collaborator)
                                        <div class="avatar avatar-s rounded-circle">
                                            <img class="rounded-circle" src="{{ urlPath($collaborator->avatar) }}" alt="{{ $collaborator->fullname }}">
                                        </div>
                                    @endforeach
                                    @if($collaborators->count() > 3)
                                        <div class="avatar avatar-s rounded-circle">
                                            <img class="rounded-circle" src="/assets/images/users/avatar-plus.png" alt="More collaborators" style="filter: grayscale(1) contrast(0.5)">
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </td>
                    @endif
                    <td class="text-center">
                        @if($form->is_indefinite)
                            <span class="text-success fs-9">Indefinite</span>                            
                        @elseif($form->end_date->isPast() && !$form->is_indefinite)
                            <i class="fa-solid fa-circle text-danger"></i>
                        @elseif($form->start_date->isFuture())
                            <span class="text-info fs-9"> 
                                Starts In {{ str_replace('from now', '', $form->start_date->diffForHumans()) }}
                            </span>
                        @else
                            <span class="text-success fs-9"> 
                                Ends In {{ str_replace('from now', '', $form->end_date->diffForHumans()) }}
                            </span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="dropdown table-dropdown">
                            <a class="btn btn-sm dropdown-toggle py-0" href="javascript:void(0)" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-v"></i>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                <li>
                                    <a class="dropdown-item" href="@route('forms.preview', $form->id, $form->slug)">
                                        <i class="fa-solid fa-eye me-2"></i> Preview
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="@route('forms.submissions', $form->id, $form->slug)">
                                        <i class="fa-solid fa-chart-line me-2"></i> Submissions
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="@route('forms.setup', $form->id, $form->slug)">
                                        <i class="fa-solid fa-cogs me-2"></i>Form Setups
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="@route('reports.list', $form->id, $form->slug)">
                                        <i class="fa-solid fa-align-left me-2"></i> Form Reports
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="@route('forms.customize', $form->id, $form->slug)">
                                        <i class="fa-solid fa-edit me-2"></i> Customize Form
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="@route('forms.delete', $form->id)" onclick="confirmDelete(event)" 
                                        data-delete-msg="Deleting this form will delete all its submissions. Are you sure you want to proceed?">
                                        <i class="fa-solid fa-trash me-2"></i> Delete Form
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>

    </table>
</div>