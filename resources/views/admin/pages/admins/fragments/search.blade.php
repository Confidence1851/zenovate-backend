<form class="mt-2 mb-2 row" action="{{ url()->current() }}">
    <div class="form-group col-3">
        <label for="">Search</label>
        <input type="text" name="search" placeholder="Search with customer name email or reference " class="form-control" value="{{ request()->search }}">
    </div>
    <div class="form-group col-auto">
        <label for="">Status</label>
        <select name="status" class="form-control">
            <option value="">Select Option</option>
            @foreach ($statuses as $option)
            <option value="{{ $option }}" {{ request()->status == $option ? 'selected' : '' }}>
                {{ $option }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-auto">
        <button class="btn btn-success btn-sm text-white mt-4">Filter</button>
        <a href="{{ url()->current() }}" class="btn btn-info btn-sm text-white mt-4">Reset</a>
    </div>
</form>
