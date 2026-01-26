<table class="table m-0 table-striped">
    <thead>
        <tr>
            <th>SN</th>
            <th>Unique Reference</th>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Phone</th>
            {{-- <th>Stage</th> --}}
            <th>Status</th>
            <th>Payment Status</th>
            <th>Booking Type</th>
            <th>Origin</th>
            <th>Date</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sessions as $session)
            <tr>
                <td>{{ $sn++ }}</td>
                <td>{{ $session->reference }}</td>
                <td>{{ $session->getCustomerName() }}</td>
                <td>{{ $session->getCustomerEmail() }}</td>
                <td>{{ $session->getCustomerPhone() }}</td>
                {{-- <td>{{ $session->stage }}</td> --}}
                <td>
                    <x-status-badge :value="$session->getStatus()" />
                </td>
                <td>
                    <x-status-badge :value="$session->getPaymentStatus()" />
                </td>
                <td>
                    <x-status-badge :value="$session->getBookingTypeDisplay()" />
                </td>
                <td>
                    @php
                        $sourcePath = strtolower($session->metadata['source_path'] ?? '');
                        $portalOrigin = null;
                        if (str_contains($sourcePath, 'products')) {
                            $portalOrigin = 'Products';
                        } elseif (str_contains($sourcePath, 'pinksky')) {
                            $portalOrigin = 'Pinksky';
                        } elseif (str_contains($sourcePath, 'cccportal') || str_contains($sourcePath, 'canada')) {
                            $portalOrigin = 'CCCPortal';
                        } elseif (str_contains($sourcePath, 'professional')) {
                            $portalOrigin = 'Professional';
                        }
                    @endphp
                    @if ($portalOrigin)
                        <span class="badge bg-dark text-white">{{ $portalOrigin }}</span>
                    @else
                        N/A
                    @endif
                </td>
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
