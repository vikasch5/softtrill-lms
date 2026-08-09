@extends('lms.common.master')

@section('content')

<div class="dashboard-main-body">

    <div class="row gy-4">

        <div class="col-lg-12">

            <div class="card">

                <div class="card-header d-flex align-items-center justify-content-between">

                    <h5 class="card-title mb-0">
                        Offers List
                    </h5>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark">
                            {{ isset($offers) ? $offers->total() : 0 }}
                            {{ isset($offers) && $offers->total() == 1 ? 'Offer' : 'Offers' }}
                        </span>
                        
                        <a href="{{ route('lms.offer.add') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Add Offer
                        </a>
                    </div>

                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table striped-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Image</th>
                                    <th>Heading</th>
                                    <th>Description</th>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <input type="hidden" id="deleteUrl" value="{{ route('lms.offers.delete') }}">
                                @if(isset($offers) && $offers->count())
                                    @foreach($offers as $key => $item)
                                        <tr>
                                            <td>{{ $offers->firstItem() + $key }}</td>
                                            
                                            <td>
                                                @if($item->image)
                                                    <img src="{{ asset('storage/' . $item->image) }}" alt="Offer Image" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                @else
                                                    <div class="bg-light-subtle rounded d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px;">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="fw-medium text-dark">{{ $item->heading }}</td>
                                            
                                            <td>
                                                <span title="{{ $item->description }}">
                                                    {{ \Illuminate\Support\Str::limit($item->description, 60) }}
                                                </span>
                                            </td>
                                            
                                            <td>
                                                @if($item->url)
                                                    <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="text-primary d-inline-flex align-items-center gap-1">
                                                        <i class="bi bi-box-arrow-up-right"></i> Link
                                                    </a>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            
                                            <td>
                                                @if($item->status)
                                                    <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill text-sm">Active</span>
                                                @else
                                                    <span class="bg-secondary text-white px-24 py-4 rounded-pill text-sm">Inactive</span>
                                                @endif
                                            </td>

                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="{{ route('lms.offer.add', ['id' => $item->id]) }}" 
                                                        class="w-32-px h-32-px bg-success-focus text-success-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                        title="Edit">
                                                        <iconify-icon icon="lucide:edit"></iconify-icon>
                                                    </a>

                                                    <a href="javascript:void(0)"
                                                        class="deleteRecord w-32-px h-32-px bg-danger-focus text-danger-main rounded-circle d-inline-flex align-items-center justify-content-center"
                                                        data-id="{{ $item->id }}" title="Delete">
                                                        <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No offers found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    @if(isset($offers) && $offers->hasPages())
                        <div class="mt-4">
                            {{ $offers->links() }}
                        </div>
                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@include('lms.common.footer')

@endsection