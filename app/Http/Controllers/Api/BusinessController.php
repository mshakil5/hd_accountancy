<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $clients = Client::where('client_credential_id', $request->user()->id)
            ->where('status', 1)
            ->with(['clientType', 'manager'])
            ->withCount('receipts')
            ->latest()
            ->get()
            ->map(function ($c) {

                return [
                    'id'            => $c->id,
                    'name'          => trim($c->name . ' ' . $c->last_name),
                    'business_name' => $c->business_name,
                    'company_name'  => $c->company_name,
                    'company_number' => $c->company_number,
                    'type_of_business' => $c->type_of_business,
                    'client_type'   => $c->clientType?->name,
                    'manager'       => $c->manager?->name,
                    'city'          => $c->city,
                    'receipt_count' => $c->receipts_count,
                ];
            });

        return response()->json(['data' => $clients], 200);
    }

    public function show(Request $request, $id)
    {
        $client = Client::where('id', $id)
            ->where('client_credential_id', $request->user()->id)
            ->with(['clientType', 'manager', 'properties'])
            ->firstOrFail();

        $type = strtolower($client->clientType?->name ?? '');

        $data = [
            'id'            => $client->id,
            'name'          => $client->name,
            'email'         => $client->email,
            'phone'         => $client->phone,

            'client_type'   => $client->clientType?->name,
            'manager'       => $client->manager?->name,

            'agreement_date' => $client->agreement_date,
            'cessation_date' => $client->cessation_date,

            'city'          => $client->city,
            'country'       => $client->country,
            'postcode'      => $client->postcode,
        ];

        if ($type === 'sole trade' || $type === 'self assessment' || $type === 'landlord') {
            $data += [
                'dob'              => $client->dob,
                'address_line1'    => $client->address_line1,
                'address_line2'    => $client->address_line2,
                'utr_number'       => $client->utr_number,
                'ni_number'        => $client->ni_number,
                'photo_id_saved'   => $client->photo_id_saved,
                'hmrc_authorization' => $client->hmrc_authorization,
            ];
        }

        if ($type === 'self assessment') {
            $data['type_of_business'] = $client->type_of_business;
        }

        if ($type === 'limited company' || $type === 'vat registered company') {
            $data += [
                'company_number' => $client->company_number,
                'registered_address_line1' => $client->registered_address_line1,
                'registered_address_line2' => $client->registered_address_line2,
                'trading_address_line1' => $client->trading_address_line1,
                'trading_address_line2' => $client->trading_address_line2,
                'trading_city' => $client->trading_city,
                'trading_country' => $client->trading_country,
                'trading_postcode' => $client->trading_postcode,
            ];
        }

        if ($type === 'partnership') {
            $data += [
                'trading_address_line1' => $client->trading_address_line1,
                'trading_address_line2' => $client->trading_address_line2,
                'trading_city' => $client->trading_city,
                'trading_country' => $client->trading_country,
                'trading_postcode' => $client->trading_postcode,
            ];
        }

        $data['properties'] = $client->properties->map(fn($p) => [
            'id'      => $p->id,
            'address' => $p->address,
        ]);

        return response()->json(['data' => $data], 200);
    }
}