<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => 'required|in:airtelmoney,moovmoney,card',
            'phone' => [
                'required_if:gateway,airtelmoney,moovmoney',
                'string',
                'regex:/^(074|077|076|060|062|066|065)\d{6}$/',
            ],
            'operator' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Le numéro doit être un numéro Airtel (074, 077, 076) ou Moov (060, 062, 066, 065) à 9 chiffres.',
            'phone.required_if' => 'Le numéro de téléphone est requis pour les paiements mobile money.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $gateway = $this->input('gateway');
            $phone = $this->input('phone');

            if ($gateway && $phone) {
                $isAirtelNumber = preg_match('/^(074|077|076)/', $phone);
                $isMoovNumber = preg_match('/^(060|062|066|065)/', $phone);

                if ($gateway === 'airtelmoney' && !$isAirtelNumber) {
                    $validator->errors()->add('phone', 'Pour Airtel Money, utilisez un numéro commençant par 074, 077 ou 076.');
                }

                if ($gateway === 'moovmoney' && !$isMoovNumber) {
                    $validator->errors()->add('phone', 'Pour Moov Money, utilisez un numéro commençant par 060, 062, 066 ou 065.');
                }
            }
        });
    }
}