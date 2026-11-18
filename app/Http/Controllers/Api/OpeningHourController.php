<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpeningHour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OpeningHourController extends Controller
{
    public function index(): JsonResponse
    {
        $hours = OpeningHour::query()
            ->orderBy('weekday')
            ->get(['weekday', 'periods'])
            ->map(fn (OpeningHour $day): array => [
                'weekday' => $day->weekday,
                'periods' => $this->mergeAdjacentPeriods($day->periods),
            ]);

        return response()->json($hours);
    }

    public function update(Request $request, OpeningHour $openingHour): JsonResponse
    {
        $data = $request->validate([
            'periods' => ['present', 'array', 'list'],
            'periods.*' => ['array:opens_at,closes_at'],
            'periods.*.opens_at' => ['required', 'date_format:H:i'],
            'periods.*.closes_at' => ['required', 'date_format:H:i'],
        ], [
            'present' => 'Invia le fasce orarie del giorno.',
            'array' => 'Controlla le fasce orarie.',
            'list' => 'Controlla le fasce orarie.',
            'required' => 'Inserisci entrambi gli orari per ogni fascia.',
            'date_format' => 'Inserisci un orario valido.',
        ]);

        $periods = collect($data['periods'])->sortBy('opens_at')->values()->all();
        $previousClose = null;

        foreach ($periods as $period) {
            if ($period['opens_at'] >= $period['closes_at']) {
                throw ValidationException::withMessages([
                    'periods' => ['La chiusura deve essere successiva all’apertura.'],
                ]);
            }

            if ($previousClose !== null && $period['opens_at'] < $previousClose) {
                throw ValidationException::withMessages([
                    'periods' => ['Le fasce orarie non possono sovrapporsi.'],
                ]);
            }

            $previousClose = $period['closes_at'];
        }

        $openingHour->periods = $this->mergeAdjacentPeriods($periods);
        $openingHour->save();

        return response()->json($openingHour->only(['weekday', 'periods']));
    }

    private function mergeAdjacentPeriods(array $periods): array
    {
        $mergedPeriods = [];

        foreach ($periods as $period) {
            $previous = array_key_last($mergedPeriods);

            if ($previous !== null && $mergedPeriods[$previous]['closes_at'] === $period['opens_at']) {
                $mergedPeriods[$previous]['closes_at'] = $period['closes_at'];
                continue;
            }

            $mergedPeriods[] = $period;
        }

        return $mergedPeriods;
    }
}
