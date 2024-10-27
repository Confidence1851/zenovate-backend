<div class="table-responsive">
    <table class="table m-0 table-striped">
        <thead>
            <tr>
                <th>SN</th>
                @if (!($preview ?? false))
                <th>Created By</th>
                <th>Doctor Name</th>
                @endif
                <th>Patient</th>
                <th>Prescription</th>
                <th>Comment</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
            <tr>
                <td>{{ $sn++ }}</td>
                @if (!($preview ?? false))
                <td>{{ optional($record->createdBy)->name }}</td>
                <td>{{ $record->doctor_name ?? "N/A" }}</td>
                @endif
                <td>{{ optional($record->patient)->full_name }}</td>
                <td>{{ $record->prescription  ?? "N/A"}}</td>
                <td>{{ $record->comment  ?? "N/A"}}</td>
                <td>{{ $record->created_at }}</td>
                <td>
                    <a href="{{ route("dashboard.consultation.patient-records.show" , $record->id )}}" class="btn btn-sm btn-info"><i class="ri-eye-line align-middle"></i></a>
                    @if (!($preview ?? false))
                    <a href="{{ route("dashboard.consultation.patient-records.edit" , $record->id)}}" class="btn btn-outline-secondary btn-sm text-body"><i class="ri-edit-line align-middle"></i></a>
                    <a href="{{ route("dashboard.consultation.patient-records.print" , $record->id)}}" class="btn btn-outline-warning btn-sm text-body"><i class=" ri-printer-fill align-middle"></i></a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
