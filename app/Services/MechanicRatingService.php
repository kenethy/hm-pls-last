<?php

namespace App\Services;

use App\Models\MechanicRating;
use App\Models\Service;
use App\Models\Mechanic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MechanicRatingService
{
    /**
     * Create a new rating for a mechanic
     */
    public function createRating(array $data): MechanicRating
    {
        // Validate that rating doesn't already exist
        if (MechanicRating::ratingExists($data['service_id'], $data['mechanic_id'], $data['customer_phone'])) {
            throw new \Exception('Rating already exists for this service-mechanic-customer combination');
        }

        // Get service details for context
        $service = Service::findOrFail($data['service_id']);
        
        // Create the rating with service context
        return MechanicRating::create([
            'service_id' => $data['service_id'],
            'mechanic_id' => $data['mechanic_id'],
            'customer_id' => $service->customer_id,
            'customer_name' => $service->customer_name,
            'customer_phone' => $service->phone,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'service_type' => $service->service_type,
            'vehicle_info' => $service->license_plate . ' - ' . $service->car_model,
            'service_date' => $service->created_at,
        ]);
    }

    /**
     * Get mechanics for a service that can be rated
     */
    public function getMechanicsForService(int $serviceId): Collection
    {
        $service = Service::with('mechanics')->findOrFail($serviceId);
        
        return $service->mechanics->map(function ($mechanic) use ($service) {
            $hasRating = MechanicRating::ratingExists($service->id, $mechanic->id, $service->phone);
            
            return [
                'id' => $mechanic->id,
                'name' => $mechanic->name,
                'specialization' => $mechanic->specialization,
                'has_rating' => $hasRating,
                'existing_rating' => $hasRating ? 
                    MechanicRating::where('service_id', $service->id)
                        ->where('mechanic_id', $mechanic->id)
                        ->where('customer_phone', $service->phone)
                        ->first() : null
            ];
        });
    }

    /**
     * Get performance analytics for a mechanic
     */
    public function getMechanicPerformanceAnalytics(int $mechanicId, $startDate = null, $endDate = null): array
    {
        $query = MechanicRating::forMechanic($mechanicId);
        
        if ($startDate || $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $ratings = $query->get();
        $totalRatings = $ratings->count();
        
        if ($totalRatings === 0) {
            return [
                'mechanic_id' => $mechanicId,
                'total_ratings' => 0,
                'average_rating' => 0,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'recent_ratings' => [],
                'performance_trend' => []
            ];
        }

        $averageRating = $ratings->avg('rating');
        $ratingDistribution = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = $ratings->where('rating', $i)->count();
        }

        // Get recent ratings (last 10)
        $recentRatings = $ratings->sortByDesc('created_at')->take(10)->values();

        // Calculate performance trend (monthly averages for last 6 months)
        $performanceTrend = $this->calculatePerformanceTrend($mechanicId, $startDate, $endDate);

        return [
            'mechanic_id' => $mechanicId,
            'total_ratings' => $totalRatings,
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $ratingDistribution,
            'recent_ratings' => $recentRatings,
            'performance_trend' => $performanceTrend
        ];
    }

    /**
     * Calculate performance trend over time
     */
    private function calculatePerformanceTrend(int $mechanicId, $startDate = null, $endDate = null): array
    {
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : $endDate->copy()->subMonths(6);

        $trend = [];
        $current = $startDate->copy()->startOfMonth();

        while ($current <= $endDate) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $monthlyRatings = MechanicRating::forMechanic($mechanicId)
                ->dateRange($monthStart, $monthEnd)
                ->get();

            $trend[] = [
                'month' => $current->format('Y-m'),
                'month_name' => $current->format('M Y'),
                'average_rating' => $monthlyRatings->count() > 0 ? round($monthlyRatings->avg('rating'), 2) : 0,
                'total_ratings' => $monthlyRatings->count()
            ];

            $current->addMonth();
        }

        return $trend;
    }

    /**
     * Get comparative analytics for all mechanics
     */
    public function getComparativeAnalytics($startDate = null, $endDate = null): array
    {
        $mechanics = Mechanic::active()->with('ratings')->get();
        
        $analytics = $mechanics->map(function ($mechanic) use ($startDate, $endDate) {
            $performance = $this->getMechanicPerformanceAnalytics($mechanic->id, $startDate, $endDate);
            
            return [
                'mechanic' => [
                    'id' => $mechanic->id,
                    'name' => $mechanic->name,
                    'specialization' => $mechanic->specialization
                ],
                'performance' => $performance
            ];
        });

        // Sort by average rating descending
        $sortedAnalytics = $analytics->sortByDesc('performance.average_rating')->values();

        return [
            'mechanics' => $sortedAnalytics,
            'summary' => [
                'total_mechanics' => $mechanics->count(),
                'total_ratings' => $analytics->sum('performance.total_ratings'),
                'overall_average' => $analytics->where('performance.total_ratings', '>', 0)->avg('performance.average_rating'),
                'top_performer' => $sortedAnalytics->first(),
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]
        ];
    }

    /**
     * Export ratings data for reporting
     */
    public function exportRatingsData($startDate = null, $endDate = null): Collection
    {
        $query = MechanicRating::with(['mechanic', 'service', 'customer']);
        
        if ($startDate || $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->orderBy('created_at', 'desc')->get()->map(function ($rating) {
            return [
                'Date' => $rating->created_at->format('Y-m-d H:i:s'),
                'Mechanic' => $rating->mechanic->name,
                'Customer' => $rating->customer_name,
                'Phone' => $rating->customer_phone,
                'Service Type' => $rating->service_type,
                'Vehicle' => $rating->vehicle_info,
                'Rating' => $rating->rating,
                'Stars' => str_repeat('★', $rating->rating) . str_repeat('☆', 5 - $rating->rating),
                'Comment' => $rating->comment,
                'Service Date' => $rating->service_date->format('Y-m-d'),
            ];
        });
    }

    /**
     * Get rating statistics summary
     */
    public function getRatingStatistics($startDate = null, $endDate = null): array
    {
        $query = MechanicRating::query();
        
        if ($startDate || $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $ratings = $query->get();
        $totalRatings = $ratings->count();

        if ($totalRatings === 0) {
            return [
                'total_ratings' => 0,
                'average_rating' => 0,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'satisfaction_rate' => 0,
                'top_rated_mechanics' => [],
                'recent_feedback' => []
            ];
        }

        $averageRating = $ratings->avg('rating');
        $ratingDistribution = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = $ratings->where('rating', $i)->count();
        }

        // Calculate satisfaction rate (4-5 star ratings)
        $satisfiedRatings = $ratings->whereIn('rating', [4, 5])->count();
        $satisfactionRate = ($satisfiedRatings / $totalRatings) * 100;

        // Get top rated mechanics
        $topRatedMechanics = DB::table('mechanic_ratings')
            ->select('mechanic_id', DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as total_ratings'))
            ->when($startDate, function ($query) use ($startDate) {
                return $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->whereDate('created_at', '<=', $endDate);
            })
            ->groupBy('mechanic_id')
            ->having('total_ratings', '>=', 3) // At least 3 ratings
            ->orderByDesc('avg_rating')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $mechanic = Mechanic::find($item->mechanic_id);
                return [
                    'mechanic' => $mechanic ? $mechanic->name : 'Unknown',
                    'average_rating' => round($item->avg_rating, 2),
                    'total_ratings' => $item->total_ratings
                ];
            });

        // Get recent feedback with comments
        $recentFeedback = $ratings->whereNotNull('comment')
            ->sortByDesc('created_at')
            ->take(5)
            ->map(function ($rating) {
                return [
                    'mechanic_name' => $rating->mechanic->name ?? 'Unknown',
                    'customer_name' => $rating->customer_name,
                    'rating' => $rating->rating,
                    'comment' => $rating->comment,
                    'date' => $rating->created_at->format('Y-m-d H:i')
                ];
            });

        return [
            'total_ratings' => $totalRatings,
            'average_rating' => round($averageRating, 2),
            'rating_distribution' => $ratingDistribution,
            'satisfaction_rate' => round($satisfactionRate, 1),
            'top_rated_mechanics' => $topRatedMechanics,
            'recent_feedback' => $recentFeedback
        ];
    }
}
