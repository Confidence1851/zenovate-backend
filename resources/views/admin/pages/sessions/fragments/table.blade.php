<table class="table m-0 table-striped">
    <thead>
        <tr>
            <th>SN</th>
            <th>Unique Reference</th>
            {{-- <th>Stage</th> --}}
            <th>Status</th>
            <th>Date</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sessions as $session)
            <tr>
                <td>{{ $sn++ }}</td>
                <td>{{ $session->reference }}</td>
                {{-- <td>{{ $session->stage }}</td> --}}
                <td>{{ $session->getStatus() }}</td>
                <td>{{ $session->created_at }}</td>
                <td>
                    <a class="btn btn-sm btn-primary" href="{{ route('dashboard.form-sessions.show', $session->id) }}">
                        <i class="ri-eye-line align-bottom"></i> View Details
                    </a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
