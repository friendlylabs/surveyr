<div class="modal fade" id="createSpaceModal" tabindex="-1" aria-labelledby="createSpaceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-8" id="createSpaceModalLabel">Create a new space</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="@route('spaces.store')" method="post" id="createSpaceForm" onsubmit="submitForm(event)">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="spaceName" class="form-label">Space Name</label>
                        <input type="text" class="form-control" id="spaceName" name="spaceName" placeholder="Enter space name" required>
                    </div>

                    <div class="mb-3">
                        <label for="spaceDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="spaceDescription" name="spaceDescription" placeholder="Spece Description" rows="2" required></textarea>
                    </div>

                    <!-- hex color picker -->
                    <div class="mb-3">
                        <label for="spaceColor" class="form-label">Space Color</label>
                        <input type="color" class="form-control" id="spaceColor" name="spaceColor" required style="height: 40px;">
                    </div>

                    <div class="mb-3">
                        <label for="spaceMembers" class="form-label d-block mb-2">Members</label>
                        <select class="form-selector" id="spaceMembers" name="spaceMembers[]" data-live-search="true" title="select space collaborators" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->fullname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <button type="submit" id="createSpacebtn" class="btn btn-primary w-100">Create Space</button>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
</div>