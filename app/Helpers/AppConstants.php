<?php

namespace App\Helpers;

class AppConstants
{
    const ROLE_SUDO = "Sudo";
    const ROLE_CUSTOMER = "Customer";
    const ROLE_ADMIN = "Admin";
    const TEAM_ZENOVATE = "Zenovate";
    const TEAM_SKYCARE = "Skycare";
    const ACIVITY_SUBMITTED = "Submitted";
    const ACIVITY_REVIEWED = "Reviewed";
    const ACIVITY_SIGNED = "Signed";
    const ACIVITY_CONFIRMED = "Confirmed";
    const ACIVITY_RECREATE = "Recreate";
    const CAD = "CAD";

    const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_CUSTOMER,
    ];

    const ADMIN_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_SUDO,
    ];

    const PILL_CLASSES = [
        // Status Constants
        StatusConstants::COMPLETED => 'bg-success text-white',
        StatusConstants::SUCCESSFUL => 'bg-success',
        StatusConstants::PENDING => 'bg-primary',
        StatusConstants::PROCESSING => 'bg-primary text-white',
        StatusConstants::ACTIVE => 'bg-success text-white',
        StatusConstants::DECLINED => 'bg-danger',
        StatusConstants::INACTIVE => 'bg-warning',
        StatusConstants::CANCELLED => 'bg-danger',
        StatusConstants::FAILED => 'bg-danger',
        StatusConstants::AWAITING_REVIEW => 'bg-warning',
        StatusConstants::AWAITING_CONFIRMATION => 'bg-warning',
        StatusConstants::REFUNDED => 'bg-warning',
        // Status display values (from getStatus() method)
        'Awaiting Review' => 'bg-warning',
        'Awaiting Confirmation' => 'bg-warning',
        'Pending' => 'bg-primary',
        'Processing' => 'bg-primary text-white',
        'Completed' => 'bg-success text-white',
        'Declined' => 'bg-danger',
        'Cancelled' => 'bg-danger',
        // Lowercase versions (for backward compatibility)
        'pending' => 'bg-primary',
        'processing' => 'bg-primary text-white',
        'completed' => 'bg-success text-white',
        'cancelled' => 'bg-danger',
        'successful' => 'bg-success',
        'declined' => 'bg-danger',
        'failed' => 'bg-danger',
        'active' => 'bg-success text-white',
        'inactive' => 'bg-warning',
        // Payment status
        'Paid' => 'bg-success text-white',
        'Unpaid' => 'bg-warning',
        // Booking type
        'Form' => 'bg-info text-white',
        'Direct' => 'bg-primary text-white',
        // Generic status values
        'unlocked' => 'bg-success text-white',
        'success' => 'bg-success text-white',
        'failed' => 'bg-danger text-white',
        'locked' => 'bg-danger text-white',
        'credit' => 'bg-success text-white',
        'debit' => 'bg-danger text-white',
        'Verified' => 'bg-success text-white',
        'Unverified' => 'bg-danger text-white',
        // Additional constants (if they exist elsewhere)
        'Approved' => 'bg-success text-white',
        'Available' => 'bg-success text-white',
        'Unused' => 'bg-success text-white',
        'Skipped' => 'bg-warning',
        'Deleted' => 'bg-danger',
        'Rejected' => 'bg-dark text-white',
    ];
}
