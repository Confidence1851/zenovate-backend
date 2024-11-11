<?php

namespace App\Helpers;

class ApiConstants
{
    const SERVER_ERR_CODE = 500;

    const BAD_REQ_ERR_CODE = 400;

    const AUTH_ERR_CODE = 401;

    const FORBIDDEN_ERR_CODE = 403;
    const NOT_FOUND_ERR_CODE = 404;

    const VALIDATION_ERR_CODE = 422;

    const GOOD_REQ_CODE = 200;

    const AUTH_TOKEN_EXP = 60; // auth otp token expiry in minutes

    const OTP_DEFAULT_LENGTH = 7;

    const MAX_PROFILE_PIC_SIZE = 2048;

    const MALE = 'Male';

    const FEMALE = 'Female';

    const OTHERS = 'Others';

    const GENDERS = [
        self::MALE,
        self::FEMALE,
        self::OTHERS,
    ];

    const LIVE_SESSION = 'LIVE_SESSION';

    const FREELANCE = 'FREELANCE';

    const HOME_SERVICE = 'HOME_SERVICE';

    const JOB_TYPES = [
        self::FREELANCE,
        self::LIVE_SESSION,
        self::HOME_SERVICE,
    ];

    const PENDING_TRANSACTION = 0;

    const SUCCESSFUL_TRANSACTION = 1;

    const FAILED_TRANSACTION = 2;

    const CANCELLED_TRANSACTION = 3;

    const GG_PROVIDER = 'google';

    const FB_PROVIDER = 'facebook';

    const PAGINATION_SIZE_WEB = 50;

    const PAGINATION_SIZE_API = 20;

    const PENDING_STATUS = 'Pending';

    const ACTIVE_STATUS = 'Active';

    const INACTIVE_STATUS = 'Inactive';

    const APPROVED_STATUS = 'Approved';

    const DECLINED_STATUS = 'Declined';

    const PASSWORD_PIN = 'password';

    const ZERO = 0;

    const ONE = 1;
}
