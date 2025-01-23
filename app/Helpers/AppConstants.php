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

}
