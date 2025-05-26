<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\MechanicRating;
use App\Services\MechanicRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

class MechanicRatingController extends Controller
{
    protected $ratingService;

    public function __construct(MechanicRatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    /**
     * Show the rating modal for a service
     */
    public function showRatingModal(int $serviceId): JsonResponse
    {
        try {
            $service = Service::with('customer')->findOrFail($serviceId);
            $mechanics = $this->ratingService->getMechanicsForService($serviceId);

            return response()->json([
                'success' => true,
                'service' => [
                    'id' => $service->id,
                    'service_type' => $service->service_type,
                    'date' => $service->created_at->format('d M Y'),
                    'vehicle' => $service->license_plate . ' - ' . $service->car_model,
                    'customer_name' => $service->customer_name,
                    'customer_phone' => $service->phone
                ],
                'mechanics' => $mechanics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found or error loading data'
            ], 404);
        }
    }

    /**
     * Submit a rating for a mechanic
     */
    public function submitRating(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'mechanic_id' => 'required|exists:mechanics,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get service to verify customer phone
            $service = Service::findOrFail($request->service_id);

            // Check if rating already exists
            if (MechanicRating::ratingExists($request->service_id, $request->mechanic_id, $service->phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memberikan rating untuk montir ini pada servis ini'
                ], 409);
            }

            // Create the rating
            $rating = $this->ratingService->createRating([
                'service_id' => $request->service_id,
                'mechanic_id' => $request->mechanic_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'customer_phone' => $service->phone
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Terima kasih! Rating Anda telah berhasil disimpan',
                'rating' => [
                    'id' => $rating->id,
                    'rating' => $rating->rating,
                    'comment' => $rating->comment,
                    'mechanic_name' => $rating->mechanic->name
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan rating: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing ratings for a service
     */
    public function getServiceRatings(int $serviceId): JsonResponse
    {
        try {
            $service = Service::findOrFail($serviceId);
            $ratings = MechanicRating::where('service_id', $serviceId)
                ->with('mechanic')
                ->get()
                ->map(function ($rating) {
                    return [
                        'id' => $rating->id,
                        'mechanic_id' => $rating->mechanic_id,
                        'mechanic_name' => $rating->mechanic->name,
                        'rating' => $rating->rating,
                        'comment' => $rating->comment,
                        'created_at' => $rating->created_at->format('d M Y H:i')
                    ];
                });

            return response()->json([
                'success' => true,
                'ratings' => $ratings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        }
    }
