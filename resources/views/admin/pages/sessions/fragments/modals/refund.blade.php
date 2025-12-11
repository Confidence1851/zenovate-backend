<div class="modal fade" id="{{ $key }}" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refundModalLabel">Refund Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('dashboard.form-sessions.mark-refunded', $session->id) }}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>Important:</strong> This will cancel the order and process a refund through Stripe.
                        Please provide a reason for the refund.
                    </div>
                    <div class="form-group">
                        <label for="refund_reason">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="refund_reason" required class="form-control" rows="4"
                            placeholder="Please provide a reason for refunding this order (minimum 10 characters)..." minlength="10"
                            maxlength="500">{{ old('reason') }}</textarea>
                        <small class="form-text text-muted">Minimum 10 characters, maximum 500 characters.</small>
                    </div>
                    @if ($session->completedPayment)
                        <div class="alert alert-info mt-3">
                            <strong>Payment Details:</strong><br>
                            Reference: {{ $session->completedPayment->reference }}<br>
                            Amount: {{ $session->completedPayment->currency }}
                            {{ number_format($session->completedPayment->total, 2) }}
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Process Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
