<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInstrumentRequest;
use App\Http\Resources\InstrumentResource;
use App\Models\Instrument;

class InstrumentController extends Controller
{
    public function store(StoreInstrumentRequest $request): InstrumentResource
    {
        $instrument = Instrument::create($request->safe()->only(['title', 'description']));

        foreach ($request->validated()['questions'] as $index => $questionData) {
            $instrument->questions()->create([
                'prompt'        => $questionData['prompt'],
                'response_type' => $questionData['response_type'],
                'order'         => $questionData['order'] ?? $index,
            ]);
        }

        return new InstrumentResource($instrument->load('questions'));
    }
}