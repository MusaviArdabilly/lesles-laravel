<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

class LocationController extends Controller
{
    public function getProvinces()
    {
        $provinces = Province::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Provinces fetched successfully',
            'data' => $provinces,
        ]);
    }

    public function getCities(Request $request)
    {
        $validated = $request->validate([
            'province_id' => 'required|exists:indonesia_provinces,id',
        ]);

        // Get the province code first
        $province = Province::findOrFail($validated['province_id']);
        
        $cities = City::where('province_code', $province->code)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Cities fetched successfully',
            'data' => $cities,
        ]);
    }

    public function getDistricts(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:indonesia_cities,id',
        ]);

        // Get the city code first
        $city = City::findOrFail($validated['city_id']);
        
        $districts = District::where('city_code', $city->code)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Districts fetched successfully',
            'data' => $districts,
        ]);
    }

    public function getVillages(Request $request)
    {
        $validated = $request->validate([
            'district_id' => 'required|exists:indonesia_districts,id',
        ]);

        // Get the district code first
        $district = District::findOrFail($validated['district_id']);
        
        $villages = Village::where('district_code', $district->code)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Villages fetched successfully',
            'data' => $villages,
        ]);
    }

    public function createLocation(Request $request)
    {
        $validated = $request->validate([
            'province_id' => 'required|exists:indonesia_provinces,id',
            'city_id' => 'required|exists:indonesia_cities,id',
            'district_id' => 'required|exists:indonesia_districts,id',
            'village_id' => 'required|exists:indonesia_villages,id',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        // Check if location already exists
        $existingLocation = Location::with(['province', 'city', 'district', 'village'])
            ->where('province_id', $validated['province_id'])
            ->where('city_id', $validated['city_id'])
            ->where('district_id', $validated['district_id'])
            ->where('village_id', $validated['village_id'])
            ->where(function($query) use ($validated) {
                // Check name and address if provided
                if ($validated['name']) {
                    $query->where('name', $validated['name']);
                } else {
                    $query->whereNull('name');
                }
                
                if ($validated['address']) {
                    $query->where('address', $validated['address']);
                } else {
                    $query->whereNull('address');
                }
            })
            ->first();

        if ($existingLocation) {
            return response()->json([
                'success' => true,
                'message' => 'Location already exists',
                'data' => $existingLocation,
            ]);
        }

        $location = Location::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'data' => $location->load(['province', 'city', 'district', 'village']),
        ], 201);
    }

    public function getLocations(Request $request)
    {
        $query = Location::with(['province', 'city', 'district', 'village']);

        // Filter by administrative levels
        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->has('village_id')) {
            $query->where('village_id', $request->village_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $locations = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Locations fetched successfully',
            'data' => $locations,
        ]);
    }

    public function getLocation($id)
    {
        $location = Location::with(['province', 'city', 'district', 'village'])->find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Location fetched successfully',
            'data' => $location,
        ]);
    }
}
