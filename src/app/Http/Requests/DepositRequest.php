<?php

namespace App\Http\Requests;
use Illuminate\Validation\Validator;
use App\Models\Transaction;
use App\Services\TransactionService;

class DepositRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $v) {
            $user = $this->user();
            $amount = (float) $this->input('amount');
            $transactionService = app(TransactionService::class);
            if ($transactionService->depositExceedsDailyLimit($user, $amount)) {
                $v->errors()->add('amount', 'Limite diário de depósito excedido.');
            }

        });
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Informe o valor do depósito.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor deve ser positivo (mínimo 0.01).',
        ];
    }
}
