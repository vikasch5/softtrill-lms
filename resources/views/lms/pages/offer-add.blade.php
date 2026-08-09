@extends('lms.common.master')

@section('content')
<div class="dashboard-main-body">

    <div class="row gy-4">

        <div class="col-lg-12">

            <div class="card border-0 shadow-sm">

                <div class="card-header d-flex align-items-center justify-content-between">

                    <h5 class="card-title mb-0">
                        {{ isset($offer->id) ? 'Edit Offer' : 'Add Offer' }}
                    </h5>

                    <a href="{{ route('lms.offers.list') }}"
                       class="btn btn-primary btn-sm">
                        Offers List
                    </a>

                </div>

                <div class="card-body">

                    <form action="{{ route('lms.offers.store') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="row g-4 ajaxForm">

                        @csrf

                        <input type="hidden"
                               name="offer_id"
                               value="{{ $offer->id ?? '' }}">


                        {{-- OFFER INFORMATION --}}

                        <div class="col-12">

                            <div class="section-label">
                                Offer Information
                            </div>

                        </div>


                        {{-- HEADING --}}

                        <div class="col-lg-6 col-md-6">

                            <label class="form-label">
                                Heading
                            </label>

                            <input type="text"
                                   name="heading"
                                   class="form-control"
                                   placeholder="Enter offer heading"
                                   value="{{ old('heading', $offer->heading ?? '') }}"
                                   required>

                        </div>


                        {{-- STATUS --}}

                        <div class="col-lg-6 col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status"
                                    class="form-control"
                                    required>

                                <option value="1"
                                    @selected(old(
                                        'status',
                                        $offer->status ?? 1
                                    ) == 1)>
                                    Active
                                </option>

                                <option value="0"
                                    @selected(old(
                                        'status',
                                        $offer->status ?? 1
                                    ) == 0)>
                                    Inactive
                                </option>

                            </select>

                        </div>

                        {{-- START DATE --}}
                        
                        <div class="col-lg-6 col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $offer->start_date ?? '') }}">
                        </div>

                        {{-- END DATE --}}
                        
                        <div class="col-lg-6 col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $offer->end_date ?? '') }}">
                        </div>

                        {{-- DESCRIPTION --}}

                        <div class="col-lg-12">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea name="description"
                                      rows="5"
                                      class="form-control"
                                      placeholder="Enter offer description"
                                      required>{{ old('description', $offer->description ?? '') }}</textarea>

                        </div>


                        {{-- IMAGE --}}

                        <div class="col-lg-12">

                            <label class="form-label">
                                Offer Image
                            </label>

                            <input type="file"
                                   name="image"
                                   class="form-control"
                                   accept="image/*">

                            @if(isset($offer->image))
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $offer->image) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-image"></i> View Current Image
                                    </a>
                                </div>
                            @endif

                        </div>


                        {{-- URL --}}

                        <div class="col-lg-12">

                            <label class="form-label">
                                URL
                            </label>

                            <input type="url"
                                   name="url"
                                   class="form-control"
                                   placeholder="https://example.com/offer"
                                   value="{{ old('url', $offer->url ?? '') }}">

                        </div>


                        {{-- SUBMIT --}}

                        <div class="col-12 mt-2">

                            <button type="submit"
                                    class="btn btn-primary px-4">

                                {{ isset($offer->id) ? 'Update Offer' : 'Create Offer' }}

                            </button>

                            <a href="{{ route('lms.offers.list') }}"
                               class="btn btn-light px-4">

                                Cancel

                            </a>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@include('lms.common.footer')

@endsection