<div class="modal fade" id="{{$key}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        @if (empty(optional($record)->id))
        <form action="{{ route('dashboard.consultation.patient-records.store') }}" method="post">
            @csrf
        @else
        <form action="{{ route('dashboard.consultation.patient-records.update' , $record->id) }}" method="post">
            @csrf @method("put")
        @endif
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        {{ empty($record) ? "Create" : "Edit"}} {{ $header_label }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="">Doctor Name</label>
                        <input class="form-control" type="text" name="doctor_name" placeholder="Enter doctor`s name.." value="{{ optional($record)->doctor_name ?? old("doctor_name")}}" />
                    </div>
                    <div class="form-group">
                        <input type="hidden" name="redirect_url" value="{{ url()->current() }}">
                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                        <input type="hidden" name="type" value="{{optional($record)->type ?? $type }}">
                        <br>
                        <label for="">Enter {{ $header_label }}</label>
                        <textarea class="form-control tinymceEditor" rows="5" type="text" required name="body" placeholder="Enter {{ strtolower($header_label)}}...">{{ optional($record)->body ?? old("body")}}</textarea>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" onclick="tinyMCE.triggerSave(true,true);">Save changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
