<?php

namespace App\Http\Controllers;

use App\Events\QuotationReceived;
use App\Http\Resources\QuotationResource;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

class QuotationController extends Controller
{

    public function index(Request $request): AnonymousResourceCollection
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

    public function store(Request $request): QuotationResource
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

        return new QuotationResource($quotation);
    }

    public function show(Request $request, string $id): QuotationResource
    {
        if (!$request->user()->can('viewAny', Quotation::class))
            abort(403);
        return new QuotationResource(Quotation::findOrFail($id));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (!$request->user()->can('delete', Quotation::class))
            abort(403);
        $quotation = Quotation::findOrFail($id);
        $quotation->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
