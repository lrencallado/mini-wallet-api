<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by auth:sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = auth()->user();
            $receiverId = $this->input('receiver_id');
            $amount = $this->input('amount');

            // Check if user is trying to send money to themselves
            if ($receiverId && $user->id == $receiverId) {
                $validator->errors()->add(
                    'receiver_id',
                    'You cannot send money to yourself.'
                );
                return; // Skip balance check if self-transfer
            }

            // Calculate total debit including 1.5% commission
            $commission = $amount * 0.015;
            $totalDebit = $amount + $commission;

            // Check if user has sufficient balance
            if ($user->balance < $totalDebit) {
                $validator->errors()->add(
                    'amount',
                    'Insufficient balance. You need ' . number_format($totalDebit, 2) .
                    ' (including ' . number_format($commission, 4) . ' commission) but have ' .
                    number_format($user->balance, 2) . '.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'receiver_id.required' => 'Please specify a receiver for this transaction.',
            'receiver_id.exists' => 'The specified receiver does not exist.',
            'receiver_id.different' => 'You cannot send money to yourself.',
            'amount.required' => 'Please specify an amount to transfer.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The minimum transfer amount is 0.01.',
            'amount.max' => 'The maximum transfer amount is 999,999,999.99.',
        ];
    }
}
