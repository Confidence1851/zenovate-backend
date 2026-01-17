@php
    // Direct color mapping based on status value (not CSS classes)
    $colorMap = [
        // Exact matches
        'Pending' => ['bg' => '#007bff', 'text' => 'white'],
        'pending' => ['bg' => '#007bff', 'text' => 'white'],
        'Processing' => ['bg' => '#ff6c00', 'text' => 'white'],
        'processing' => ['bg' => '#ff6c00', 'text' => 'white'],
        'Completed' => ['bg' => '#28a745', 'text' => 'white'],
        'completed' => ['bg' => '#28a745', 'text' => 'white'],
        'Successful' => ['bg' => '#28a745', 'text' => 'white'],
        'successful' => ['bg' => '#28a745', 'text' => 'white'],
        'Cancelled' => ['bg' => '#dc3545', 'text' => 'white'],
        'cancelled' => ['bg' => '#dc3545', 'text' => 'white'],
        'Declined' => ['bg' => '#dc3545', 'text' => 'white'],
        'declined' => ['bg' => '#dc3545', 'text' => 'white'],
        'Failed' => ['bg' => '#dc3545', 'text' => 'white'],
        'failed' => ['bg' => '#dc3545', 'text' => 'white'],
        'Awaiting Review' => ['bg' => '#ffc107', 'text' => '#212529'],
        'awaiting review' => ['bg' => '#ffc107', 'text' => '#212529'],
        'Awaiting Confirmation' => ['bg' => '#ffc107', 'text' => '#212529'],
        'awaiting confirmation' => ['bg' => '#ffc107', 'text' => '#212529'],
        'Active' => ['bg' => '#28a745', 'text' => 'white'],
        'active' => ['bg' => '#28a745', 'text' => 'white'],
        'Inactive' => ['bg' => '#ffc107', 'text' => '#212529'],
        'inactive' => ['bg' => '#ffc107', 'text' => '#212529'],
        'Unpaid' => ['bg' => '#ffc107', 'text' => '#212529'],
        'unpaid' => ['bg' => '#ffc107', 'text' => '#212529'],
        'Paid' => ['bg' => '#28a745', 'text' => 'white'],
        'paid' => ['bg' => '#28a745', 'text' => 'white'],
    ];
    
    $colors = $colorMap[$value] ?? ['bg' => '#6c757d', 'text' => 'white'];
    $style = "background-color: {$colors['bg']}; color: {$colors['text']};";
@endphp

<span class="badge" style="{{ $style }}; padding: 0.375rem 0.75rem; border-radius: 0.25rem; display: inline-block; font-weight: 500;">
    {{ $value }}
</span>
