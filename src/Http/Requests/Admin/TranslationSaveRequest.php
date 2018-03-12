<?php

namespace Novius\Backpack\Translation\Manager\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Novius\Backpack\Translation\Manager\Manager\UnifiedTranslationManager;

class TranslationSaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @param UnifiedTranslationManager $translationManager
     * @return array
     */
    public function rules(UnifiedTranslationManager $translationManager)
    {
        return [
            'language' => [
                'required',
                Rule::in(array_keys($translationManager->getAvailableLocales())),
            ],
        ];
    }
}
