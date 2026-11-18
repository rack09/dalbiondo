<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteContact;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteContactController extends Controller
{
    public function show(): JsonResponse
    {
        return $this->contactResponse(SiteContact::query()->findOrFail(SiteContact::ID));
    }

    public function update(Request $request): JsonResponse
    {
        $validNumber = function (string $attribute, string $number, Closure $fail): void {
            if (SiteContact::internationalNumber($number) === null) {
                $label = $attribute === 'phone' ? 'di telefono' : 'WhatsApp';
                $fail("Inserisci un numero {$label} valido.");
            }
        };

        $data = $request->validate([
            'phone' => ['bail', 'present', 'nullable', 'string', 'max:40', $validNumber],
            'whatsapp' => ['bail', 'present', 'nullable', 'string', 'max:40', $validNumber],
        ], [
            'phone.max' => 'Il numero di telefono può contenere al massimo 40 caratteri.',
            'whatsapp.max' => 'Il numero WhatsApp può contenere al massimo 40 caratteri.',
        ]);

        $contact = SiteContact::query()->findOrFail(SiteContact::ID);
        $contact->phone = $data['phone'];
        $contact->whatsapp = $data['whatsapp'];
        $contact->save();

        return $this->contactResponse($contact);
    }

    private function contactResponse(SiteContact $contact): JsonResponse
    {
        $phone = SiteContact::internationalNumber($contact->phone);
        $whatsapp = SiteContact::internationalNumber($contact->whatsapp);

        return response()->json([
            'phone' => $contact->phone,
            'phone_href' => $phone === null ? null : 'tel:+'.$phone,
            'whatsapp' => $contact->whatsapp,
            'whatsapp_url' => $whatsapp === null ? null : 'https://wa.me/'.$whatsapp,
            'address' => [
                'street' => $contact->street_address,
                'postal_code' => $contact->postal_code,
                'locality' => $contact->city.' ('.$contact->province.')',
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
