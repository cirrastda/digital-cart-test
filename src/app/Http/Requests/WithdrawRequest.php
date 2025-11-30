<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Transaction;
use App\Services\TransactionService;



class WithdrawRequest extends FormRequest
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

            if ($user && $amount > (float) $user->balance) {
                $v->errors()->add('amount', 'Saldo insuficiente para saque.');
            }

            $transactionService = app(TransactionService::class);
            if ($transactionService->withdrawExceedsDailyLimit($user, $amount)) {
                $v->errors()->add('amount', 'Limite diário de saque excedido.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Informe o valor do saque.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor deve ser positivo (mínimo 0.01).',
        ];
    }
}
