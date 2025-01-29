<form id="detailsForm">
  <div class="row my-4">
    <div class="col-lg-9">
      <div class="row px-3 my-2 txt-theme">
        <div class="col-lg-7">
          <table class="w-100 text-capitalize">
          <tr>
              <td><b>Client Name:</b></td>
              <td><span>{{ $client->name }}</span></td>
            </tr>
            <tr>
              <td><b>Client Reference ID:</b></td>
              <td><span>{{ $client->refid }}</span></td>
            </tr>
            <tr>
              <td><b>Client Type:</b></td>
              <td><span>{{ $client->clientType ? $client->clientType->name : '&nbsp;' }}</span></td>
            </tr>
            <tr>
              <td><b>Client Manager:</b></td>
              <td><span>{{ $client->manager ? $client->manager->first_name . '' . $client->manager->last_name : '&nbsp;' }}</span></td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-3 text-center">
      <div class="img mb-2">
        <img src="{{ $client->photo ? asset('images/client/' . $client->photo) : asset('assets/img/human-placeholder.jpg') }}" id="imagePreview" width="150" class="border-theme border-2 rounded-3">
      </div>
    </div>

    @if ($client->recentUpdates->count() > 0)
    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>RECENT UPDATE</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
        <div class="row px-3 my-2 txt-theme">
          <div class="col-3">
            <b>
              Date
            </b>
          </div>
          <div class="col-5">
            <b>
              Note
            </b>
          </div>
          <div class="col-4">
            <b>
              Created By
            </b>
          </div>
          <hr class="mt-2" style="height: 1px; background-color: #233969;">
          @foreach ($client->recentUpdates as $recentUpdate)
          <div class="col-3">
              {{ $recentUpdate ? \Carbon\Carbon::parse($recentUpdate->created_at)->format('d-m-Y') : '&nbsp;' }}
          </div>
          <div class="col-5">
              {!! $recentUpdate ? $recentUpdate->note : '&nbsp;' !!}
          </div>
          <div class="col-4">
              {{ $recentUpdate->user ? $recentUpdate->user->first_name : '&nbsp;' }}
              {{ $recentUpdate->user ? $recentUpdate->user->last_name : '&nbsp;' }} ({{ $recentUpdate->user ? $recentUpdate->user->type : '&nbsp;' }})
          </div>
      @endforeach
        </div>
    </div>
    @endif

    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>CLIENT CONTACT DETAILS</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
      <div class="row px-3 my-2 txt-theme">
          <div class="col-lg-12">
              <div class="row mb-2">
                  <div class="col-3"><b>Email:</b></div>
                  <div class="col-9">{{ $client->email }}</div>
              </div>
              <div class="row mb-2">
                  <div class="col-3"><b>Primary Phone:</b></div>
                  <div class="col-9">{{ $client->phone }}</div>
              </div>
              <div class="row mb-2">
                  <div class="col-3"><b>Secondary Phone:</b></div>
                  <div class="col-9">{{ $client->phone2 }}</div>
              </div>
              <div class="row mb-2">
                  <div class="col-3"><b>Trading Address:</b></div>
                  <div class="col-9">{{ $client->trading_address }}</div>
              </div>
              <div class="row mb-2">
                  <div class="col-3"><b>Registered Address:</b></div>
                  <div class="col-9">
                      {{ $client->address_line1 }} {{ $client->address_line2 }} 
                      {{ $client->city }} {{ $client->town }} {{ $client->postcode }}
                  </div>
              </div>
          </div>
      </div>
    </div>

    @if ($client->businessInfo)
    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>Business Tax Details</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
        <div class="row px-3 my-2 txt-theme">
            <div class="col-lg-6 d-flex flex-column">
                <table class="w-100 text-capitalize" style="table-layout: fixed;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;"><b>Company Status:</b></td>
                        <td style="width: 50%; vertical-align: top;"><span>{{ $client->businessInfo->status == 1 ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                    <tr>
                        <td><b>Nature of Business:</b></td>
                        <td><span>{{ $client->businessInfo->nature_of_business }}</span></td>
                    </tr>
                    <tr>
                        <td><b>Company Number:</b></td>
                        <td><span>{{ $client->businessInfo->company_number }}</span></td>
                    </tr>
                    <tr>
                        <td><b>Company Auth Code:</b></td>
                        <td><span>{{ $client->businessInfo->authorization_code }}</span></td>
                    </tr>
                    <tr>
                        <td><b>Company UTR Number:</b></td>
                        <td><span>{{ $client->businessInfo->company_utr }}</span></td>
                    </tr>
                    <tr>
                        <td><b>CT Authorization:</b></td>
                        <td><span>{{ $client->businessInfo->ct_authorization == 1 ? 'Yes' : 'No' }}</span></td>
                    </tr>
                    <tr>
                        <td><b>PAYE Ref Number:</b></td>
                        <td><span>{{ $client->businessInfo->paye_ref_number }}</span></td>
                    </tr>
                    <tr>
                        <td><b>Paye Authorization:</b></td>
                        <td><span>{{ $client->businessInfo->paye_authorization == 1 ? 'Yes' : 'No' }}</span></td>
                    </tr>
                </table>
            </div>
            <div class="col-lg-6 d-flex flex-column">
                <table class="w-100 text-capitalize" style="table-layout: fixed;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;"><b>Account Office Ref No:</b></td>
                        <td style="width: 50%; vertical-align: top;"><span>{{ $client->businessInfo->account_office_ref_number }}</span></td>
                    </tr>
                    <tr>
                        <td><b>VAT Number:</b></td>
                        <td><span>{{ $client->businessInfo->vat_number }}</span></td>
                    </tr>
                    <tr>
                        <td><b>VAT Authorization:</b></td>
                        <td><span>{{ $client->businessInfo->vat_authorization == 1 ? 'Yes' : 'No' }}</span></td>
                    </tr>
                    <tr>
                        <td><b>Year End Date:</b></td>
                        <td><span>{{ \Carbon\Carbon::parse($client->businessInfo->year_end_date)->format('d-m-Y') }}</span></td>
                    </tr>
                    <tr>
                        <td><b>Confirmation Date:</b></td>
                        <td><span>{{ \Carbon\Carbon::parse($client->businessInfo->confirmation_due_date)->format('d-m-Y') }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if ($client->directorInfos->count() > 0)
    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>DIRECTOR INFO</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
        <div class="row px-3 my-2 txt-theme">
            @foreach ($client->directorInfos as $director)
                <div class="col-lg-6">
                    <table class="w-100 text-capitalize" style="table-layout: fixed; width: 100%;">
                        <tr>
                            <td style="width: 50%; vertical-align: top;"><b>Director No:</b></td>
                            <td style="width: 50%; vertical-align: top;"><span>{{ $loop->iteration }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Director Name:</b></td>
                            <td><span>{{ $director ? $director->name : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Phone:</b></td>
                            <td><span>{{ $director ? $director->phone : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Email:</b></td>
                            <td><span>{{ $director ? $director->email : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Address Line:</b></td>
                            <td><span>{{ $director ? $director->address : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Date of Birth:</b></td>
                            <td><span>{{ $director && $director->dob ? \Carbon\Carbon::parse($director->dob)->format('d-m-Y') : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>NI Number:</b></td>
                            <td><span>{{ $director ? $director->ni_number : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Director's Tax Return:</b></td>
                            <td><span>{{ $director->directors_tax_return == 1 ? 'Yes' : 'No' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>UTR Number:</b></td>
                            <td><span>{{ $director ? $director->utr_number : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>DIR UTR Authorization:</b></td>
                            <td><span>{{ $director->utr_authorization == 1 ? 'Yes' : 'No' }}</span></td>
                        </tr>
                    </table>
                </div>
                @if ($loop->iteration % 2 == 0 && !$loop->last)
                    <div class="col-12">
                        <hr>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    @endif

    @php
      $approvedServices = $client->clientServices->filter(function ($service) {
          return $service->is_admin_approved == 1;
      });
    @endphp

    @if ($approvedServices->count() > 0)

    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>AGREED SERVICE LIST</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
      <div class="row px-3 my-2 txt-theme">
        <div class="col-12 pb-2">
            <b>Service Name:</b>
        </div>
        <div class="col-lg-6">
            @php $number = 1; @endphp
            <ol class="list-unstyled">
                @foreach ($approvedServices as $key => $service)
                    @if ($key % 2 == 0)
                        <li>{{ $number++ }}. {{ $service->service ? $service->service->name : '&nbsp;' }}</li>
                    @endif
                @endforeach
            </ol>
        </div>
        <div class="col-lg-6">
            @php $number = ceil(count($approvedServices) / 2) + 1; @endphp
            <ol class="list-unstyled">
                @foreach ($approvedServices as $key => $service)
                    @if ($key % 2 != 0)
                        <li>{{ $number++ }}. {{ $service->service ? $service->service->name : '&nbsp;' }}</li>
                    @endif
                @endforeach
            </ol>
        </div>
      </div>
    </div>

    @endif

    @if ($client->contactInfos->count() > 0)
    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>CONTACT PERSON</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
        <div class="row px-3 my-2 txt-theme">
            @foreach ($client->contactInfos as $contact)
                <div class="col-lg-6">
                    <table class="w-100 text-capitalize" style="table-layout: fixed; width: 100%;">
                        <tr>
                            <td style="width: 50%; vertical-align: top;"><b>Contact No:</b></td>
                            <td style="width: 50%; vertical-align: top;"><span>{{ $loop->iteration }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Contact Name:</b></td>
                            <td>
                                <span>
                                    {{ $contact ? $contact->greeting : '&nbsp;' }} 
                                    {{ $contact ? $contact->first_name : '&nbsp;' }} 
                                    {{ $contact ? $contact->last_name : '&nbsp;' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><b>Job Title:</b></td>
                            <td><span>{{ $contact ? $contact->job_title : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Company:</b></td>
                            <td><span>{{ $contact ? $contact->company : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Email:</b></td>
                            <td><span>{{ $contact ? $contact->email : '&nbsp;' }}</span></td>
                        </tr>
                        <tr>
                            <td><b>Phone:</b></td>
                            <td><span>{{ $contact ? $contact->phone : '&nbsp;' }}</span></td>
                        </tr>
                    </table>
                </div>
                @if ($loop->iteration % 2 == 0 && !$loop->last)
                    <div class="col-12">
                        <hr>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    @if(isset($client->about_business))
    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>ABOUT BUSINESS</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
        <div class="row px-3 my-2 txt-theme">
            {!! $client->about_business !!}
        </div>
    </div>
    @endif

    @if ($client->accountancyFee)

    <div class="sub-box-header mt-3">
        <div class="row w-100">
            <div class="col-lg-6 d-flex txt-theme">
                <span>ABOUT BUSINESS</span>
            </div>
        </div>
    </div>

    <div class="col-lg-12 pt-3">
        <div class="row px-3 my-2 txt-theme">
            <div class="col-lg-12">
                <div class="row mb-2">
                    <div class="col-3"><b>Annual Agreed Fees:</b></div>
                    <div class="col-9">{{ $client->accountancyFee->annual_agreed_fees ?? '&nbsp;' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-3"><b>Company:</b></div>
                    <div class="col-9">{{ $client->accountancyFee->monthly_standing_order == 1 ? 'Yes' : 'No' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-3"><b>Monthly Amount:</b></div>
                    <div class="col-9">{{ $client->accountancyFee->monthly_amount ?? '&nbsp;' }}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-3"><b>Next Review:</b></div>
                    <div class="col-9">
                        {{ $client->accountancyFee->next_review ? \Carbon\Carbon::parse($client->accountancyFee->next_review)->format('d-m-Y') : '&nbsp;' }}
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-3"><b>Comment:</b></div>
                    <div class="col-9">{!! $client->accountancyFee->comment ?? '&nbsp;' !!}</div>
                </div>
                <div class="row mb-2">
                    <div class="col-3"><b>Fees Discussion:</b></div>
                    <div class="col-9">{!! $client->accountancyFee->fees_discussion ?? '&nbsp;' !!}</div>
                </div>
            </div>
        </div>
    </div>

    @endif

  </div>
</form>