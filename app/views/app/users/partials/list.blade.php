<div class="card" style="min-height: calc(100vh - 240px);">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="ps-2"><i class="fa-solid fa-circle {{ ($user->status === 'active') ? 'text-success' : 'text-danger' }}"></i></td>
                        <td>{{ $user->fullname }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ ucfirst($user->role) }}</td>
                        <td class="text-end">
                            <div class="dropdown table-dropdown">
                                <button class="btn btn-sm btn-link dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" id="editUserBtn" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user='{!! $user !!}'>
                                            <i class="fa-solid fa-pencil me-2"></i>
                                            Edit User
                                        </a>
                                    </li>
                                    @if($user->status === 'active')
                                        <li>
                                            <a class="dropdown-item text-danger" href="@route('users.suspend', $user->id)">
                                                <i class="fa-solid fa-stop-circle me-2"></i>
                                                Suspend User
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <a class="dropdown-item text-success" href="@route('users.activate', $user->id)">
                                                <i class="fa-solid fa-play-circle me-2"></i>
                                                Activate User
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>