<div class="modal fade" id="{{ $key }}" tabindex="-1" aria-labelledby="unfulfilledModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unfulfilledModalLabel">Mark Order as Unfulfilled</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('dashboard.form-sessions.mark-unfulfilled', $session->id) }}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This will cancel the order. Please provide a reason for marking this
                        order as unfulfilled.
                    </div>
                    <div class="form-group">
                        <label for="unfulfilled_reason">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="unfulfilled_reason" required class="form-control" rows="4"
                            placeholder="Please provide a reason for marking this order as unfulfilled (minimum 10 characters)..."
                            minlength="10" maxlength="500">{{ old('reason') }}</textarea>
                        <small class="form-text text-muted">Minimum 10 characters, maximum 500 characters.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Mark as Unfulfilled</button>
                </div>
            </form>
        </div>
    </div>
</div>
