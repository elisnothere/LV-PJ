<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingCities\UpsertShippingCityRequest;
use App\Models\ShippingCity;
use App\Services\ShippingCityService;
use Illuminate\Http\Request;

class ShippingCityController extends Controller
{
    public function __construct(private ShippingCityService $shippingCityService)
    {
    }

    public function index(Request $request)
    {
        $search = $request->filled('buscar') ? (string) $request->string('buscar') : null;

        return view('shipping-cities.index', [
            'shippingCities' => $this->shippingCityService->paginated($search),
        ]);
    }

    public function create()
    {
        return view('shipping-cities.create');
    }

    public function store(UpsertShippingCityRequest $request)
    {
        $this->shippingCityService->create($request->shippingCityData());

        return redirect()
            ->route('shipping-cities.index')
            ->with('success', 'Ciudad de envio creada correctamente.');
    }

    public function edit(ShippingCity $shippingCity)
    {
        return view('shipping-cities.edit', compact('shippingCity'));
    }

    public function update(UpsertShippingCityRequest $request, ShippingCity $shippingCity)
    {
        $this->shippingCityService->update($shippingCity, $request->shippingCityData());

        return redirect()
            ->route('shipping-cities.index')
            ->with('success', 'Ciudad de envio actualizada correctamente.');
    }

    public function toggleActive(ShippingCity $shippingCity)
    {
        $this->shippingCityService->toggleActive($shippingCity);

        return back()->with('success', 'Estado de la ciudad de envio actualizado.');
    }
}
