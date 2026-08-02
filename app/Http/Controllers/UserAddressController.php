<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddresses\UpsertUserAddressRequest;
use App\Models\UserAddress;
use App\Services\ShippingCityService;
use App\Services\UserAddressService;
use Illuminate\Auth\Access\AuthorizationException;

class UserAddressController extends Controller
{
    public function __construct(
        private UserAddressService $userAddressService,
        private ShippingCityService $shippingCityService,
    ) {
    }

    public function index()
    {
        return view('user-addresses.index', [
            'addresses' => $this->userAddressService->forUser((int) auth()->id()),
        ]);
    }

    public function create()
    {
        return view('user-addresses.create', [
            'shippingCities' => $this->shippingCityService->active(),
        ]);
    }

    public function store(UpsertUserAddressRequest $request)
    {
        $this->userAddressService->createForUser((int) auth()->id(), $request->addressData());

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Direccion guardada correctamente.');
    }

    public function edit(UserAddress $address)
    {
        try {
            $address = $this->userAddressService->loadOwnedOrFail($address, (int) auth()->id());
        } catch (AuthorizationException) {
            abort(403);
        }

        return view('user-addresses.edit', [
            'address' => $address,
            'shippingCities' => $this->shippingCityService->active(),
        ]);
    }

    public function update(UpsertUserAddressRequest $request, UserAddress $address)
    {
        try {
            $this->userAddressService->updateForUser($address, (int) auth()->id(), $request->addressData());
        } catch (AuthorizationException) {
            abort(403);
        }

        return redirect()
            ->route('addresses.index')
            ->with('success', 'Direccion actualizada correctamente.');
    }

    public function destroy(UserAddress $address)
    {
        try {
            $this->userAddressService->deleteForUser($address, (int) auth()->id());
        } catch (AuthorizationException) {
            abort(403);
        }

        return back()->with('success', 'Direccion eliminada correctamente.');
    }
}
