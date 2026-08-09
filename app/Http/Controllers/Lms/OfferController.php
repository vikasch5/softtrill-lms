<?php

namespace App\Http\Controllers\Lms;

use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OfferController extends Controller
{
    public function offerList()
    {
        $offers = Offer::paginate(20);
        return view('lms.pages.offer-list', compact('offers'));
    }

    public function offerAdd(Request $request, $id = null)
    {
        $offer = $id ? Offer::find($id) : new Offer();
        return view('lms.pages.offer-add', compact('offer'));
    }

    public function offerStoreOrUpdate(Request $request)
    {
        $request->validate([
            'offer_id' => ['nullable', 'integer'],
            'heading' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $tenantId = auth()->id();
        $addedBy = auth()->id();
        
        if ($request->filled('offer_id')) {
            $offer = Offer::where('id', $request->offer_id)
                ->where('added_by', $addedBy)
                ->firstOrFail();
        } else {
            $offer = new Offer();
            $offer->tenant_id = $tenantId;
            $offer->created_by = auth()->id();
        }

        $offer->heading = $request->heading;
        $offer->added_by = $addedBy;
        $offer->description = $request->description;
        $offer->url = $request->url;
        $offer->status = $request->status;
        $offer->start_date = $request->start_date;
        $offer->end_date = $request->end_date;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('offers', 'public');
            $offer->image = $path;
        }

        $offer->save();

        return response()->json([
            'success' => true,
            'message' => $request->filled('offer_id')
                ? 'Offer updated successfully.'
                : 'Offer created successfully.',
            'data' => $offer,
        ]);
    }

    public function offerDelete(Request $request)
    {
        $offer = Offer::find($request->id);
        if ($offer) {
            $offer->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Offer deleted successfully.'
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => 'Offer not found.'
        ], 404);
    }
}
