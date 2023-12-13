<?php

namespace App\Http\Controllers;

use App\Events\QuotationReceived;
use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class QuotationController extends Controller
{

    public function index(Request $request)
    {
        if (!$request->user()->can('viewAny', Quotation::class))
            abort(403);
        $results = QueryBuilder::for(Quotation::orderBy('created_at', 'desc'))->allowedFilters([
            AllowedFilter::callback('global', function (Builder $query, $value) {
                $query->where('id', '=', $value)
                    ->orWhere('subject', 'LIKE', "%$value%")
                    ->orWhere('email', 'LIKE', "%$value%")
                    ->orWhere('phone', 'LIKE', "%$value%")
                    ->orWhere('name', 'LIKE', "%$value%")
                    ->orWhere('content', 'LIKE', "%$value%");
            })
        ])->paginate(25);
        return QuotationResource::collection($results);
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
        $quotation = Quotation::create($data);
        event(new QuotationReceived($quotation));
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
