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
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-end align-items-center mt-3 mt-md-0">
                    {{-- <a class="btn btn-sm btn-primary" href="#"><i class="ri-add-circle-line align-bottom"></i> New
                        User</a> --}}
                </div>
            </div>
        </div> <!-- / Breadcrumbs-->

        <!-- Content-->
        <section class="container-fluid">
            <div class="card mb-4 h-100">
                <div class="card-header justify-content-between align-items-center d-flex">
                    <h6 class="card-title m-0">Users</h6>
                    {{-- <a href="#" class="btn btn-outline-secondary btn-sm text-body"><i
                            class="ri-download-2-line align-middle"></i> Export</a> --}}
                </div>
                <div class="card-body">
                    @include("admin.pages.users.fragments.search")
                    <div class="table-responsive">
                        <table class="table m-0 table-striped">
                            <thead>
                                <tr>
                                    <th>SN</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Sex</th>
                                    <th>Gender</th>
                                    <th>D.O.B</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $user)
                                    <tr>
                                        <td>{{ $sn++ }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->sex }}</td>
                                        <td>{{ $user->gender }}</td>
                                        <td>{{ $user->dob }}</td>
                                        <td>{{ $user->location ?? "N/A" }}</td>
                                        <td>{{ $user->created_at }}</td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {!! $users->links("pagination::bootstrap-4") !!}
                </div>
            </div>
        </section>
        <!-- / Content-->

    </main>
    <!-- /Page Content -->
@endsection
