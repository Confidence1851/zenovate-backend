@extends('admin.layout.app')
@section('content')
    <!-- Page Content -->
    <main id="main">

        <!-- Breadcrumbs-->
        <div class="bg-white border-bottom py-3 mb-5">
            <div
                class="container-fluid d-flex justify-content-between align-items-start align-items-md-center flex-column flex-md-row">
                <nav class="mb-0" aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.form-sessions.index') }}">Form Sessions</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Form Session Information </li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                    @if ($can_review)
                        <a class="btn btn-sm btn-success text-white me-3" href="#"data-bs-toggle="modal"
                            data-bs-target="#review_order">Review</a>
                        @include('admin.pages.sessions.fragments.modals.review', ['key' => 'review_order'])
                    @endif
                    @if ($session->isDirectCheckout() && $session->completedPayment)
                        @php
                            $canModify = !in_array($session->status, [
                                \App\Helpers\StatusConstants::COMPLETED,
                                \App\Helpers\StatusConstants::CANCELLED,
                                \App\Helpers\StatusConstants::REFUNDED,
                            ]);
                        @endphp
                        @if ($canModify)
                            <button class="btn btn-sm btn-success text-white me-2" data-bs-toggle="modal"
                                data-bs-target="#mark_completed_modal">
                                Mark as Completed
                            </button>
                            <button class="btn btn-sm btn-warning text-white me-2" data-bs-toggle="modal"
                                data-bs-target="#mark_unfulfilled_modal">
                                Mark as Unfulfilled
                            </button>
                            <button class="btn btn-sm btn-danger text-white me-2" data-bs-toggle="modal"
                                data-bs-target="#mark_refunded_modal">
                                Refund Order
                            </button>
                            @include('admin.pages.sessions.fragments.modals.completed', [
                                'key' => 'mark_completed_modal',
                                'session' => $session,
                            ])
                            @include('admin.pages.sessions.fragments.modals.unfulfilled', [
                                'key' => 'mark_unfulfilled_modal',
                                'session' => $session,
                            ])
                            @include('admin.pages.sessions.fragments.modals.refund', [
                                'key' => 'mark_refunded_modal',
                                'session' => $session,
                            ])
                        @endif
                    @endif
                    <a class="btn btn-sm btn-primary" href="{{ url()->previous() ?? 'N/A' }}"><i
                            class="arrow-left align-bottom"></i>Back</a>

                </div>
            </div>
        </div> <!-- / Breadcrumbs-->

        <!-- Content-->

        <section class="container-fluid">
            @include('notifications.flash_messages')
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Form Session Information <b>#{{ $session->reference }}</b></h6>
                        </div>
                        <div class="card-body">
                            <h6>Basic Details</h6>
                            <hr>
                            <p>
                                <b>Unique Reference:</b> {{ $dto->reference() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Status:</b> {{ $dto->status() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Booking Type:</b>
                                <x-status-badge :value="$session->getBookingTypeDisplay()" />
                            </p>
                            @php
                                $sourcePath = $session->metadata['source_path'] ?? '';
                                $portalOrigin = null;
                                if (str_contains($sourcePath, 'pinksky')) {
                                    $portalOrigin = 'Pinksky';
                                } elseif (str_contains($sourcePath, 'cccportal') || str_contains($sourcePath, 'canada')) {
                                    $portalOrigin = 'CCCPortal';
                                }
                            @endphp
                            @if ($portalOrigin)
                                <p>
                                    <b>Portal Origin:</b>
                                    <span class="badge bg-dark text-white">{{ $portalOrigin }}</span>
                                </p>
                            @endif
                            <p>
                                <b>Signed Document:</b>
                                @if (!empty(($url = $session->docuseal_url)))
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">View</a>
                                @else
                                    N/A
                                @endif
                            </p>
                            <br>
                            <hr>
                            <h6>Personal Details</h6>
                            <hr>
                            <p>
                                <b>Name:</b> {{ $dto->fullName() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Email:</b> {{ $dto->email() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Phone:</b> {{ $dto->phone() ?? 'N/A' }}
                            </p>
                            @php
                                $raw = $session->metadata['raw'] ?? [];
                            @endphp
                            @if (!empty($raw['businessName']))
                                <p>
                                    <b>Business Name:</b> {{ $raw['businessName'] }}
                                </p>
                            @endif
                            @if (!empty($raw['medicalDirectorName']))
                                <p>
                                    <b>Medical Director Name:</b> {{ $raw['medicalDirectorName'] }}
                                </p>
                            @endif
                            <p>
                                <b>Preferred Contact Method:</b> {{ $dto->preferredContact() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Date of Birth:</b> {{ $dto->dob() ?? 'N/A' }}
                            </p>
                            <br>
                            <hr>
                            <h6>Location Details</h6>
                            <hr>
                            <p>
                                <b>Street Address:</b> {{ $dto->streetAddress() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>City:</b> {{ $dto->city() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>State/Province:</b> {{ $dto->stateProvince() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Zip Code:</b> {{ $dto->postalZipCode() ?? 'N/A' }}
                            </p>
                            <p>
                                <b>Country:</b> {{ $dto->country() ?? 'N/A' }}
                            </p>
                            <br>
                            <hr>
                            @if ($session->isDirectCheckout())
                                <h6>Direct Booking Additional Information</h6>
                                <hr>
                                @php
                                    $raw = $session->metadata['raw'] ?? [];
                                @endphp
                                @if (!empty($raw['businessName']))
                                    <p>
                                        <b>Business Name:</b> {{ $raw['businessName'] }}
                                    </p>
                                @endif
                                @if (!empty($raw['medicalDirectorName']))
                                    <p>
                                        <b>Medical Director Name:</b> {{ $raw['medicalDirectorName'] }}
                                    </p>
                                @endif
                                @if (!empty($raw['account_number']))
                                    <p>
                                        <b>Account Number:</b> {{ $raw['account_number'] }}
                                    </p>
                                @endif
                                @if (!empty($raw['location']))
                                    <p>
                                        <b>Location:</b> {{ $raw['location'] }}
                                    </p>
                                @endif
                                @if (!empty($raw['shipping_address']))
                                    <p>
                                        <b>Shipping Address:</b> {{ $raw['shipping_address'] }}
                                    </p>
                                @endif
                                @if (!empty($raw['additional_information']))
                                    <p>
                                        <b>Additional Information:</b> {{ $raw['additional_information'] }}
                                    </p>
                                @endif
                                <br>
                                <hr>
                            @endif
                            <h6>Payment Details</h6>
                            <hr>
                            @if (empty($dto->payment))
                                <p>
                                    No payment records at the moment
                                </p>
                            @else
                                @php
                                    $orderType = $dto->payment->order_type ?? 'regular';
                                    $isOrderSheet = $orderType === 'order_sheet';
                                @endphp
                                <div class="mb-3">
                                    <a href="{{ route('dashboard.payments.show', $dto->payment->id) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                        View Full Payment Details
                                    </a>
                                </div>
                                <p>
                                    <b>Order Type:</b>
                                    @if ($isOrderSheet)
                                        <span class="badge bg-info text-white">Order Sheet</span>
                                    @else
                                        <span class="badge bg-secondary text-white">Regular Checkout</span>
                                    @endif
                                </p>
                                <p>
                                    <b>Checkout Type:</b>
                                    @if ($session->isDirectCheckout())
                                        <span class="badge bg-primary text-white">Direct Checkout</span>
                                    @else
                                        <span class="badge bg-secondary text-white">Form-Based</span>
                                    @endif
                                </p>
                                <p>
                                    <b>Currency:</b> {{ strtoupper($dto->payment->currency) }}
                                </p>
                                <p>
                                    <b>Sub Total:</b> {{ strtoupper($dto->payment->currency) }}
                                    {{ number_format($dto->payment->sub_total, 2) }}
                                </p>
                                @if (!empty($dto->payment->discount_code) && !empty($dto->payment->discount_amount))
                                    <p>
                                        <b>Discount Code:</b> {{ $dto->payment->discount_code }}
                                    </p>
                                    <p>
                                        <b>Discount Amount:</b> -{{ strtoupper($dto->payment->currency) }}
                                        {{ number_format($dto->payment->discount_amount, 2) }}
                                    </p>
                                @endif
                                <p>
                                    <b>Shipping Fee:</b> {{ strtoupper($dto->payment->currency) }}
                                    {{ number_format($dto->payment->shipping_fee ?? 0, 2) }}
                                </p>
                                @if (!empty($dto->payment->tax_rate) || !empty($dto->payment->tax_amount))
                                    <p>
                                        <b>Tax:</b> {{ !empty($dto->payment->tax_rate) ? number_format($dto->payment->tax_rate, 2) . '%' : 'N/A' }}
                                    </p>
                                    <p>
                                        <b>Tax Amount:</b> {{ strtoupper($dto->payment->currency) }}
                                        {{ number_format($dto->payment->tax_amount ?? 0, 2) }}
                                    </p>
                                @endif
                                <p>
                                    <b>Total:</b> {{ strtoupper($dto->payment->currency) }}
                                    {{ number_format($dto->payment->total, 2) }}
                                </p>
                                <p>
                                    <b>Paid At:</b> {{ $dto->payment->paid_at ?? 'N/A' }}
                                </p>
                                <p>
                                    <b>Receipt:</b>
                                    @if (!empty($dto->payment->receipt_url))
                                        <a href="{{ $dto->payment->receipt_url }}" target="_blank"
                                            rel="noopener noreferrer">View</a>
                                    @else
                                        N/A
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Products Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($dto->paymentProducts() as $paymentProduct)
                                        <tr>
                                            <td>{{ $paymentProduct->product->name ?? 'Unknown Product' }}</td>
                                            <td>{{ $paymentProduct->quantity ?? 1 }}</td>
                                            <td>{{ $paymentProduct->getPrice() }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">No products selected at the moment</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header justify-content-between align-items-center d-flex">
                            <h6 class="card-title m-0">Activities</h6>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($activities as $activity)
                                        <tr>
                                            <td>{{ $activity->activity }}</td>
                                            <td>{{ $activity->message }}</td>
                                            <td>{{ $activity->created_at->format('Y-m-d h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">No activities at the moment</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if ($session->isFormBooking())
                <div class="card mb-4">
                    <div class="card-header justify-content-between align-items-center d-flex">
                        <h6 class="card-title m-0">Questionnaire</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th class="p_5">QUESTION</th>
                                    <th class="p_5">ANSWER</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dto->questions() as $question)
                                    @if ($question['type'] == 'group')
                                        <tr>
                                            <td colspan="2"><br></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2">
                                                <h6 class="title">{{ $question['title'] }}</h6>
                                                <small class="text-grey">{{ $question['subtitle'] }}</small>
                                                <hr>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td style="width: 40%;"><b>{{ $question['question'] }}</b></td>
                                            <td>
                                                {{ $question['value'] ?? 'N/A' }}
                                                @if (!empty(($sub = $question['sub'] ?? null)) && !empty(($v = $sub['value'])))
                                                    <br><br>
                                                    <div class="alert alert-dark">
                                                        <div><b class="text-grey">{{ $sub['placeholder'] }}</b></div>
                                                        <div>{{ $v }}</div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </section>
        <!-- / Content-->

    </main>
    <!-- /Page Content -->
@endsection
