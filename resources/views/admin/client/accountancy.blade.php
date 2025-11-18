<form id="accountancyForm">
    <div class="row my-4">
      <input type="hidden" name="client_id" value="{{ $client->id ?? '' }}">
        <div class="col-lg-4">
            <label for="annual_agreed_fees">Annually Agreed Fees</label>
            <input type="number" class="form-control my-2" id="annual_agreed_fees" name="annual_agreed_fees" placeholder="Enter anually agreed fees" value="{{ $client->accountancyFee->annual_agreed_fees ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="monthly_standing_order">Monthly Standing Order <span class="text-danger">*</span></label>
            <div class="mt-2">
                <select class="form-control my-2" name="monthly_standing_order" id="monthly_standing_order">
                    <option value="0" {{ isset($client->accountancyFee->monthly_standing_order) && $client->accountancyFee->monthly_standing_order == 0 ? 'selected' : '' }}>No</option>
                    <option value="1" {{ isset($client->accountancyFee->monthly_standing_order) && $client->accountancyFee->monthly_standing_order == 1 ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
        <div class="col-lg-4">
            <label for="monthly_amount">Monthly Amount</label>
            <input type="number" class="form-control my-2" id="monthly_amount" name="monthly_amount" placeholder="Enter company number" value="{{ $client->accountancyFee->monthly_amount ?? '' }}">
        </div>
        <div class="col-lg-4">
            <label for="next_review">Next Review</label>
            <input type="date" class="form-control my-2" id="next_review" name="next_review" value="{{ $client->accountancyFee->next_review ?? '' }}">
        </div>
        <div class="col-lg-4">
          <label for="comment">Comment</label>
          <textarea class="form-control my-2" id="comment" name="comment" placeholder="Enter your comment">{{ $client->accountancyFee->comment ?? '' }}</textarea>
        </div>
        <div class="col-lg-4">
          <label for="fees_discussion">Fees Discussion</label>
          <textarea class="form-control my-2" id="fees_discussion" name="fees_discussion" placeholder="Enter your comment">{{ $client->accountancyFee->fees_discussion ?? '' }}</textarea>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-4 mx-auto text-center">
            <button class="btn btn-sm bg-theme text-light btn-outline-dark">Update</button>
        </div>
    </div>
</form>