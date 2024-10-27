<div class="card mb-4 h-100">
    <div class="card-header justify-content-between align-items-center d-flex">
        <h6 class="card-title m-0">Patient Details <b>#{{ $patient->id }}</b></h6>
        <a href="{{ route('dashboard.consultation.patients.edit', $patient->id) }}"
            class="btn btn-outline-secondary btn-sm text-body"><i class="ri-edit-line align-middle"></i>
            Edit</a>
    </div>
    <div class="card-body">
        @foreach ($patient->toArray() as $column => $value)
            @if (!in_array($column, ['id', 'records', 'createdBy', 'user_id', 'deleted_at']))
                <p>
                    <b>{{ ucwords(str_replace('_', ' ', $column)) }}: </b>
                    @if ($column == 'created_by')
                        {{ optional($patient->createdBy)->name ?? 'N/A' }}
                    @elseif (in_array($column, ['created_at', 'updated_at']))
                        {{ $patient->$column->format('Y-m-d h:i A') }}
                    @else
                        {{ $value ?? 'N/A' }}
                    @endif
                </p>
            @endif
        @endforeach
    </div>
</div>
