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
        StatusConstants::COMPLETED => 'success text-white',
        StatusConstants::SUCCESSFUL => 'success',
        StatusConstants::PENDING => 'primary',
        StatusConstants::PROCESSING => 'primary text-white',
        StatusConstants::ACTIVE => 'success text-white',
        StatusConstants::DECLINED => 'danger',
        StatusConstants::INACTIVE => 'warning',
        StatusConstants::CANCELLED => 'danger',
        StatusConstants::FAILED => 'danger',
        StatusConstants::AWAITING_REVIEW => 'warning',
        StatusConstants::AWAITING_CONFIRMATION => 'warning',
        StatusConstants::REFUNDED => 'warning',
        // Status display values (from getStatus() method)
        'Awaiting Review' => 'warning',
        'Awaiting Confirmation' => 'warning',
        'Pending' => 'primary',
        'Processing' => 'primary text-white',
        'Completed' => 'success text-white',
        'Declined' => 'danger',
        'Cancelled' => 'danger',
        // Payment status
        'Paid' => 'success text-white',
        'Unpaid' => 'warning',
        // Booking type
        'Form' => 'info text-white',
        'Direct' => 'primary text-white',
        // Generic status values
        'unlocked' => 'success text-white',
        'success' => 'success text-white',
        'failed' => 'danger text-white',
        'locked' => 'danger text-white',
        'credit' => 'success text-white',
        'debit' => 'danger text-white',
        'Verified' => 'success text-white',
        'Unverified' => 'danger text-white',
        // Additional constants (if they exist elsewhere)
        'Approved' => 'success text-white',
        'Available' => 'success text-white',
        'Unused' => 'success text-white',
        'Skipped' => 'warning',
        'Deleted' => 'danger',
        'Rejected' => 'dark text-white',
    ];
}
