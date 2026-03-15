@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">
<div class="row">
<div class="col-lg-12">

<div class="card shadow-sm border-0">

<div class="card-header d-flex justify-content-between align-items-center">
<h5 class="mb-0">Create Custom Lead Fields</h5>

<button type="button" class="btn btn-success" id="addRow">
<i class="ri-add-line"></i> Add Field
</button>

</div>

<div class="card-body">

<form action="{{ route('lms.lead-fields.store') }}" method="POST" class="ajaxForm">
@csrf

<!-- Table Header -->

<div class="row fw-semibold text-muted mb-3">

<div class="col-md-2">Field Name</div>
<div class="col-md-3">Label</div>
<div class="col-md-2">Field Type</div>
<div class="col-md-2">Sort Order</div>
<div class="col-md-1 text-center">Required</div>
<div class="col-md-2 text-center">Action</div>

</div>

<div id="fieldsContainer">

<div class="row align-items-center mb-3 field-row">

<div class="col-md-2">
<input type="text" name="fields[0][field_name]" class="form-control" placeholder="pan">
</div>

<div class="col-md-3">
<input type="text" name="fields[0][label]" class="form-control" placeholder="PAN Number">
</div>

<div class="col-md-2">
<select name="fields[0][field_type]" class="form-control">
<option value="text">Text</option>
<option value="number">Number</option>
<option value="date">Date</option>
<option value="select">Select</option>
<option value="textarea">Textarea</option>
<option value="boolean">Yes/No</option>
</select>
</div>

<div class="col-md-2">
<input type="number" name="fields[0][sort_order]" class="form-control" value="0">
</div>

<div class="col-md-1 text-center">

<div class="form-check">
<input class="form-check-input" type="checkbox" name="fields[0][is_required]" value="1">
</div>

</div>

<div class="col-md-2 text-center">

<button type="button" class="btn btn-danger removeRow">
<i class="ri-delete-bin-line"></i>
</button>

</div>

</div>

</div>

<div class="mt-4">
<button type="submit" class="btn btn-primary">
<i class="ri-save-line"></i> Save Fields
</button>
</div>

</form>

</div>
</div>

</div>
</div>
</div>

@endsection

@section('scripts')

<script>

$(document).ready(function(){

let rowIndex = 1

$('#addRow').click(function(){

let html = `
<div class="row align-items-center mb-3 field-row">

<div class="col-md-2">
<input type="text" name="fields[${rowIndex}][field_name]" class="form-control">
</div>

<div class="col-md-3">
<input type="text" name="fields[${rowIndex}][label]" class="form-control">
</div>

<div class="col-md-2">
<select name="fields[${rowIndex}][field_type]" class="form-control">
<option value="text">Text</option>
<option value="number">Number</option>
<option value="date">Date</option>
<option value="select">Select</option>
<option value="textarea">Textarea</option>
<option value="boolean">Yes/No</option>
</select>
</div>

<div class="col-md-2">
<input type="number" name="fields[${rowIndex}][sort_order]" class="form-control" value="0">
</div>

<div class="col-md-1 text-center">
<div class="form-check">
<input class="form-check-input" type="checkbox" name="fields[${rowIndex}][is_required]" value="1">
</div>
</div>

<div class="col-md-2 text-center">
<button type="button" class="btn btn-danger removeRow">
<i class="ri-delete-bin-line"></i>
</button>
</div>

</div>
`

$('#fieldsContainer').append(html)

rowIndex++

})

$(document).on('click','.removeRow',function(){
$(this).closest('.field-row').remove()
})

})

</script>

@endsection