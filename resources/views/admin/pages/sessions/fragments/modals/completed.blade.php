<div class="modal fade" id="{{ $key }}" tabindex="-1" aria-labelledby="completedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completedModalLabel">Mark Order as Completed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('dashboard.form-sessions.mark-completed', $session->id) }}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        Are you sure you want to mark this order as completed? This action will update the order status
                        and create an activity record.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Mark as Completed</button>
                </div>
            </form>
        </div>
    </div>
</div>
