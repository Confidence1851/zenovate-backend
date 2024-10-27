<div class="modal fade" id="{{ $key }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ empty($user) ? "Create" : "Edit" }} Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @if (empty($user))
            <form action="{{ route("dashboard.admins.store")}}" method="post">
                @else
                <form action="{{ route("dashboard.admins.update" , $user->id)}}" method="post">
                    @method("put")
                    @endif
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="">Name</label>
                            <input type="text" required class="form-control" name="name" value="{{ old("name") ?? optional($user)->name }}">
                        </div>
                        <div class="form-group">
                            <label for="">Email</label>
                            <input type="email" required name="email" class="form-control" value="{{ old("email") ?? optional($user)->email }}">
                        </div>
                        <div class="form-group">
                            <label for="">Sex</label>
                            <select name="sex" class="form-control">
                                <option value="" disabled selected>Select Option</option>
                                @foreach (["Male" , "Female"] as $option)
                                <option value="{{ $option }}" {{ (old("sex") ?? optional($user)->sex) == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="">Password</label>
                            <input type="password" {{ empty($user) ? "required" : ""}} name="password" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
        </div>
    </div>
</div>
