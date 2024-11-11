<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        hr {
            border-color: black solid 1px;
        }

        .w_50 {
            width: 50%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
        }

        td {
            border: 0.7px solid;
            vertical-align: baseline;
        }

        .no_border {
            border: none !important;
        }

        .p_5 {
            padding: 5px;
        }

        .text-grey {
            color: grey;
        }

        .title {
            font-size: 15px;
            color: #162c15 !important
        }

        .logo-text {
            font-size: 30px;
            color: #162c15 !important
        }

        body {
            font-size: 70%;
        }
    </style>
</head>

<body>
    <table class="no_border">
        <tbody>
            <tr class="no_border">
                <td style="width:80%">
                    <h1 class="logo-text">ZENOVATE</h1>
                </td>
                <td style="text-align: right;width:15%">
                    <div class=""> <b>Unique Session ID:</b></div>
                    <div class="">{{ $dto->reference() }}</div>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    {{-- <div class="text-center">
        <h3>{{ ucwords($session->service) }} session Form</h3>
    </div>
    <hr> --}}
    <table class="no_border">
        <tbody>
            <tr class="no_border">
                <td class="w_50 no_border">
                    <table class="no_border">
                        <tbody>
                            <tr>
                                <td class="p_5"><b>Name:</b> <br></td>
                                <td class="p_5"> {{ $dto->fullName() }} </td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>Email:</b></td>
                                <td class="p_5"> {{ $dto->email() }} </td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>Phone:</b></td>
                                <td class="p_5"> {{ $dto->phone() }} </td>
                            </tr>
                            <tr>
                                <td class="p_5" style="width:130px"><b>Date of Birth:</b> <br></td>
                                <td class="p_5"> {{ $dto->dob() }}</td>
                            </tr>
                            <tr>
                                <td class="p_5" style="width:130px"><b>Contact Method:</b> <br></td>
                                <td class="p_5"> {{ $dto->preferredContact() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td class="w_50 no_border">
                    <table class="no_border">
                        <tbody>
                            <tr>
                                <td class="p_5" style="width:130px"><b>Address:</b> <br></td>
                                <td class="p_5"> {{ $dto->streetAddress() }} </td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>City:</b></td>
                                <td class="p_5"> {{ $dto->city() }} </td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>State/Province:</b></td>
                                <td class="p_5"> {{ $dto->stateProvince() }} </td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>ZIP Code:</b></td>
                                <td class="p_5"> {{ $dto->postalZipCode() }} </td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>Country:</b></td>
                                <td class="p_5"> {{ $dto->country() }} </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    <h3>Product Information</h3>
    <table class="no_border">
        <tbody>
            <tr class="no_border">
                <td class=" no_border">
                    <table class="no_border">
                        <tbody>
                            @forelse ($dto->selectedProducts() as $product)
                                <tr>
                                    <td class="p_5" style="width: 40%"><b>Name:</b> <br></td>
                                    <td class="p_5"> {{ $product->name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="p_5" colspan="2">No products selected at the moment</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    <h3>Payment Information</h3>
    <table class="no_border">
        <tbody>
            <tr class="no_border">
                <td class=" no_border">
                    <table class="no_border">
                        <tbody>
                            @if (empty($dto->payment))
                                <tr>
                                    <td class="p_5" colspan="2">No payment records at the moment</td>
                                </tr>
                            @else
                                <tr>
                                    <td class="p_5" style="width: 40%"><b>Sub Total:</b> <br></td>
                                    <td class="p_5">{{ strtoupper($dto->payment->currency) }}
                                        {{ number_format($dto->payment->sub_total, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="p_5"><b>Shipping Fee:</b></td>
                                    <td class="p_5">{{ strtoupper($dto->payment->currency) }}
                                        {{ number_format($dto->payment->shipping_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="p_5"><b>Total:</b></td>
                                    <td class="p_5">{{ strtoupper($dto->payment->currency) }}
                                        {{ number_format($dto->payment->total, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="p_5"><b>Paid At:</b></td>
                                    <td class="p_5"> {{ $dto->payment->paid_at }}</td>
                                </tr>
                                <tr>
                                    <td class="p_5"><b>Payment Receipt:</b></td>
                                    <td class="p_5">
                                        <a href="{{ $dto->payment->receipt_url }}" target="_blank"
                                            rel="noopener noreferrer">View</a>
                                    </td>
                                </tr>
                            @endif

                        </tbody>
                    </table>
                </td>

            </tr>

        </tbody>
    </table>
    {{-- <hr>
    <h3>Delivery Information</h3>
    <table class="no_border">
        <tbody>
            <tr class="no_border">
                <td class=" no_border">
                    <table class="no_border">
                        <tbody>
                            <tr>
                                <td class="p_5" style="width: 20%"><b>Address 1:</b> <br></td>
                                <td class="p_5"> {{ optional($user->deliveryAddress)->address_1 }}</td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>Address 2:</b> <br></td>
                                <td class="p_5"> {{ optional($user->deliveryAddress)->address_2 }}</td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>Phone:</b> <br></td>
                                <td class="p_5"> {{ optional($user->deliveryAddress)->phone }}</td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>Postal Code:</b> <br></td>
                                <td class="p_5"> {{ optional($user->deliveryAddress)->postal_code }}</td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>City:</b> <br></td>
                                <td class="p_5"> {{ optional($user->deliveryAddress)->city }}</td>
                            </tr>
                            <tr>
                                <td class="p_5"><b>Province:</b> <br></td>
                                <td class="p_5"> {{ optional($user->deliveryAddress)->province }}</td>
                            </tr>

                        </tbody>
                    </table>
                </td>

            </tr>

        </tbody>
    </table> --}}
    <hr>
    <h3>Questionnaire</h3>
    <table>
        <thead>
            <tr>
                <th class="p_5">
                    <h4>QUESTION</h4>
                </th>
                <th class="p_5">
                    <h4>ANSWER</h4>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dto->questions() as $question)
                @if ($question['type'] == 'group')
                    <tr>
                        <td colspan="2"><br></td>
                    </tr>
                    <tr>
                        <td class="p_5" colspan="2">
                            <h4 class="title">{{ $question['title'] }}</h4>
                            <small class="text-grey">{{ $question['subtitle'] }}</small>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td class="p_5" style="width: 40%;"><b>{{ $question['question'] }}</b></td>
                        <td class="p_5">
                            {{ $question['value'] ?? 'N/A' }}
                            @if (!empty(($sub = $question['sub'] ?? null)) && !empty(($v = $sub['value'])))
                                <br><br>
                                <div class="p_5">
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
    <hr>
    <h3>Signatures</h3>
    <table class="no_border">
        <tbody>
            <tr class="no_border">
                <td class="p_5" colspan="2">Name: @{{Full Name;role=zenovate_admin}}</td>
            </tr>
            <tr class="no_border">
                <td class="p_5" colspan="2">
                    <br>
                    Signature: @{{Signature;role=zenovate_admin;type=signature}}</td>
            </tr>
        </tbody>
    </table>
    <br>
    <br>
    <br>
    <table class="no_border">
        <tbody>
            <tr class="no_border">
                <td class="p_5" colspan="2">Name: @{{Full Name;role=skycare_admin}}</td>
            </tr>
            <tr class="no_border">
                <td class="p_5" colspan="2">
                    <br>
                    Signature: @{{Signature;role=skycare_admin;type=signature}}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
