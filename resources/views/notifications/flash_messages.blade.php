@if ($errors->all())
   <div class="mt-3">
    @foreach ($errors->all() as $error)
    <div class="alert alert-danger">{{ $error }}</div>
    @endforeach
   </div>
@endif

@if (session()->has("error_message"))
<div class="alert alert-danger mt-3">{{ session()->get("error_message") }}</div>
@endif


@if (session()->has("success_message"))
<div class="alert alert-success mt-3">{{ session()->get("success_message") }}</div>
@endif



@if (session()->has("info_message"))
<div class="alert alert-info mt-3">{{ session()->get("info_message") }}</div>
@endif
