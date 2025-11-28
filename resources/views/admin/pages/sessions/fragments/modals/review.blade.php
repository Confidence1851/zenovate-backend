<div class="modal fade" id="{{ $key }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Review Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('dashboard.form-sessions.update', $session->id) }}" method="post">
                @method('put')
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="">Approve Request?</label>
                        <select name="status" id="status_input" required class="form-control">
                            <option value="" disabled selected>Select Option</option>
                            @foreach ($statuses as $option)
                                <option value="{{ $option }}" {{ old('status') == $option ? 'selected' : '' }}>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="">Comment</label>
                        <textarea name="comment" id="comment_input" placeholder="Put in a comment if your are rejecting the order request..."
                            class="form-control">{{ old('comment') }}</textarea>
                    </div>

                    <div class="mt-3 alert alert-info">
                        Upon approval, an email will be sent to <b>{{ config('emails.zenovate_admin') }}</b> to sign
                        order.
                        {{-- After signing, the document will also be forwarded to <b>Skycare</b> to confirm and sign. --}}
                    </div>
                    <div class="mt-3 alert alert-warning">
                        If you decide to deny this request, an email will be sent to the customer along with your
                        comment. <b class="text-danger">Also note that a refund will be made to the customer!</b>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Proceed</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $("#status_input").on("change", function() {
            const val = $(this).val();
            if (val == "Yes") {
                $("#comment_input").removeAttr("required");
            } else {
                $("#comment_input").attr("required", true);
            }
        });
        $("#status_input").trigger("change");
    </script>
@endpush
