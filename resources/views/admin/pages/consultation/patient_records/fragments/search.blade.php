<form class="mt-2 mb-2 row" action="{{ url()->current() }}">
    <div class="form-group col-3">
        <label for="">Search</label>
        <input type="text" name="search" placeholder="Search with patient`s name or email" class="form-control" value="{{ request()->search }}">
    </div>
    <div class="form-group col-2">
        <label for="">Doctor Name</label>
        <input type="text" name="doctor_name" placeholder="Search with doctor`s name" class="form-control" value="{{ request()->doctor_name }}">
    </div>
    <div class="form-group col-auto">
        <label for="">Date of Birth</label>
        <input type="date" name="dob" class="form-control" value="{{ request()->dob }}">
    </div>
    <div class="form-group col-auto">
        <label for="">Sex</label>
        <select name="sex" class="form-control">
            <option value="" disabled selected>Select Option</option>
            @foreach (["Male" , "Female"] as $option)
            <option value="{{ $option }}" {{ request()->sex == $option ? 'selected' : '' }}>
                {{ $option }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-auto">
        <label for="">From</label>
        <input type="date" name="from" class="form-control" value="{{ request()->from }}">
    </div>
    <div class="form-group col-auto">
        <label for="">To</label>
        <input type="date" name="to" class="form-control" value="{{ request()->to }}">
    </div>
    <div class="form-group col-auto">
        <button class="btn btn-success btn-sm text-white mt-4">Filter</button>
        <a href="{{ url()->current() }}" class="btn btn-info btn-sm text-white mt-4">Reset</a>
    </div>
</form>
