<div class="table-responsive">
    <table class="table m-0 table-striped">
        <thead>
            <tr>
                <th>SN</th>
                <th>User</th>
                <th>Service</th>
                <th>Package</th>
                <th>Amount</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($applications as $application)
                <tr>
                    <td>{{ $sn++ }}</td>
                    <td>{{ optional($application->user)->name ?? "N/A" }}</td>
                    <td>{{ $application->getService() }}</td>
                    <td>{{ optional($application->package)->name ?? "N/A" }}</td>
                    <td>{{ optional($application->payment)->getAmount() ?? "N/A" }}</td>
                    <td>{{ $application->created_at }}</td>
                    <td>
                        <a href="{{ route("dashboard.applications.show" , $application->id )}}" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
