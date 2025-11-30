<?php

namespace App\Http\Requests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransferRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'recipient' => ['required', 'string', 'exists:users,email'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $v) {
            $user = $this->user();
            $amount = (float) $this->input('amount');

            if ($user && $amount > (float) $user->balance) {
                $v->errors()->add('amount', 'Saldo insuficiente para transferência.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Informe o valor da transferência.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor deve ser positivo (mínimo 0.01).',
            'recipient.required' => 'Informe o destinatário.',
            'recipient.exists' => 'Destinatário não encontrado.',
        ];
    }
}