<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuotationController extends Controller
{

    public function index(Request $request)
    {
        if (!$request->user()->can('viewAny', Quotation::class))
            abort(403);

        return QuotationResource::collection(Quotation::orderBy('created_at', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:150|min:5',
            'email' => 'required|email|max:191',
            'phone' => 'string|max:50|min:6',
            'name' => 'required|string|max:120|min:3',
            'content' => 'required|string|min:10'
        ]);
        Quotation::create($data);
        //Mail::to($data['email'])->queue(new QuotationReceived($data['name'], $data['subject']));
        return response('', 201);
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->can('viewAny', Quotation::class))
            abort(403);
        return Quotation::findOrFail($id);
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->can('delete', Quotation::class))
            abort(403);
        $Quotation = Quotation::findOrFail($id);
        $Quotation->delete();
    }
}