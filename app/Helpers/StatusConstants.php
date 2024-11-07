<?php

namespace App\Helpers;

class StatusConstants
{
    const ACTIVE = "Active";
	const INACTIVE = "Inactive";
	const PENDING = "Pending";
    const PROCESSING = "Processing";
    const COMPLETED = "Completed";
    const STOPPED = "Stopped";
    const SUCCESSFUL = "Successful";
    const CANCELLED = "Cancelled";
    const FAILED = "Failed";

	 const ACTIVE_STATUSES = [
		self::PENDING , self::PROCESSING
	 ];

	const STATUSES = [
		self::ACTIVE => self::ACTIVE,
        self::INACTIVE => self::INACTIVE,
	];

}
