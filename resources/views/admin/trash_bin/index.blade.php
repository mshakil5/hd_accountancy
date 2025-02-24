@extends('admin.layouts.admin')

@section('content')

<section class="content" id="contentContainer">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card card-danger">
          <div class="card-header">
            <h3 class="card-title">Trash Bin</h3>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
          </div>
          <div class="card-body mt-2">
            <table id="trashTable" class="table cell-border table-striped">
              <thead>
                <tr>
                  <th>Module</th>
                  <th>Deleted At</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($deletedData as $model => $records)
                  @foreach ($records as $record)
                  <tr>
                    <td>{{ $model }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->deleted_at)->format('H:i:s | d-m-Y') }}</td>
                    <td>
                      <button class="btn btn-info btn-sm viewBtn" 
                      data-model="{{ $model }}" 
                      data-id="{{ $record->id }}" 
                      data-details="{{ json_encode($record->getAttributes()) }}">
                      <i class="fas fa-eye"></i>
                    </button>
                      <a href="{{ route('restore.record', ['model' => $model, 'id' => $record->id]) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-undo"></i> Restore
                      </a>
                      <a href="{{ route('forceDelete.record', ['model' => $model, 'id' => $record->id]) }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash-alt"></i> Delete Permanently
                      </a>
                    </td>
                  </tr>
                  @endforeach
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Deleted Record Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalContent">
        <p>Loading...</p>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')

<script>
  $(function () {
    $("#trashTable").DataTable({
        "pageLength": 100
    });

    $(document).on("click", ".viewBtn", function () {
        let details = $(this).data("details");

        let html = "<table class='table table-bordered'>";
        $.each(details, function (key, value) {
            value = value === null ? "" : value;
            html += `<tr><th>${key.replace('_', ' ').toUpperCase()}</th><td>${value}</td></tr>`;
        });
        html += "</table>";

        $("#modalContent").html(html);
        $("#viewModal").modal("show");
    });


  });
</script>

@endsection