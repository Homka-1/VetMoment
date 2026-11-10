<?php

namespace App\Http\Requests\Donations;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Форма пожертвования (docs/product/user-flows.md, 2): сумма 200 ₸ – 5 000 000 ₸ целыми тенге,
 * email обязателен (квитанция), имя, «не показывать моё имя», комментарий, согласие с офертой.
 * Права (сбор принимает деньги, приют одобрен) проверяет контроллер — чтобы показать понятное сообщение, а не 403.
 */
class DonationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Поле суммы приходит строкой «5 000» (пресет кладёт его скрипт); без JS — выбранный пресет.
        $raw = trim((string) $this->input('amount'));
        if ($raw === '' && is_numeric($this->input('amount_preset'))) {
            $raw = (string) $this->input('amount_preset');
        }
        $digits = preg_replace('/[\s\x{00A0}₸]/u', '', $raw);

        $this->merge([
            'amount' => $digits === '' ? null : $digits,
            'donor_name' => trim((string) $this->input('donor_name')) ?: null,
            'donor_email' => mb_strtolower(trim((string) $this->input('donor_email'))),
            'comment' => trim((string) $this->input('comment')) ?: null,
            'is_anonymous' => $this->boolean('is_anonymous'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:'.config('payments.min_amount'), 'max:'.config('payments.max_amount')],
            'donor_email' => ['required', 'string', 'max:255', 'email:rfc', 'regex:/@[^@\s]+\.[^@\s.]{2,}$/u'],
            'donor_name' => ['nullable', 'string', 'max:100'],
            'is_anonymous' => ['boolean'],
            'comment' => ['nullable', 'string', 'max:500'],
            'agree' => ['accepted'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $min = Money::format(config('payments.min_amount') * 100);
        $max = Money::format(config('payments.max_amount') * 100);

        return [
            'amount.required' => 'Выберите сумму или введите свою.',
            'amount.integer' => 'Введите сумму целым числом тенге, например 3 000.',
            'amount.min' => "Минимум {$min}. Увеличьте сумму или выберите готовую.",
            'amount.max' => "За один платёж — не больше {$max}.",
            'donor_email.required' => 'Укажите email — пришлём на него квитанцию.',
            'donor_email.email' => 'Проверьте адрес: например, aigerim@mail.kz',
            'donor_email.regex' => 'В адресе нет домена: например, aigerim@mail.kz',
            'agree.accepted' => 'Чтобы перейти к оплате, примите условия оферты.',
            'idempotency_key.*' => 'Форма устарела — обновите страницу и отправьте ещё раз.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['donor_name' => 'имя', 'comment' => 'комментарий'];
    }

    /**
     * @return array{amount: int, donor_name: ?string, donor_email: string, is_anonymous: bool, comment: ?string}
     */
    public function donation(): array
    {
        return [
            'amount' => (int) $this->validated('amount') * 100,
            'donor_name' => $this->validated('donor_name'),
            'donor_email' => $this->validated('donor_email'),
            'is_anonymous' => (bool) $this->validated('is_anonymous'),
            'comment' => $this->validated('comment'),
        ];
    }
}
