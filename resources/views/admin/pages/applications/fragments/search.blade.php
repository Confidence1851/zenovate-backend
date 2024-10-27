<form class="mt-2 mb-2 row" action="{{ url()->current() }}">
    <div class="form-group col-4">
        <label for="">Search</label>
        <input type="text" name="search" placeholder="Search with name, email or session" class="form-control" value="{{ request()->search }}">
    </div>
    <div class="form-group col-auto">
        <label for="">Service</label>
        <select name="service" class="form-control">
            <option value="" disabled selected>Select Option</option>
            @foreach ($services as $service)
            <option value="{{ $service }}" {{ request()->service == $service ? 'selected' : '' }}>
                {{ ucwords(str_replace("_" , " " , $service)) }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-auto">
        <label for="">Package</label>
        <select name="package_id" class="form-control">
            <option value="" disabled selected>Select Option</option>
            @foreach ($packages as $package)
            <option value="{{ $package->id }}" {{ request()->package_id == $package->id ? 'selected' : '' }}>
                {{ $package->name }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-auto">
        <button class="btn btn-success btn-sm text-white mt-4">Filter</button>
        <a href="{{ url()->current() }}" class="btn btn-info btn-sm text-white mt-4">Reset</a>
    </div>
</form>
