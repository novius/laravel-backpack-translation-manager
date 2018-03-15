<?php

namespace Novius\Backpack\Translation\Manager\Http\Controllers\Admin;

use Backpack\Base\app\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Novius\Backpack\Translation\Manager\Http\Requests\Admin\TranslationSaveRequest;
use Novius\Backpack\Translation\Manager\Manager\UnifiedTranslationManager;
use Spatie\TranslationLoader\LanguageLine;

class TranslationController extends Controller
{
    protected $data = []; // the information we send to the view

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Displays the interface to manage translations
     *
     * @param Request $request
     * @param \Novius\Backpack\Translation\Manager\Manager\UnifiedTranslationManager $translationManager
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request, UnifiedTranslationManager $translationManager)
    {
        $selectedDictionary = (string) $request->dictionary;
        $selectedLanguage = (string) $request->language;
        $search = (string) $request->search;

        $locales = $translationManager->getAvailableLocales();
        $dictionariesByNamespace = $translationManager->getDictionariesByNamespace();

        // Uses the application's default locale if none selected or not valid
        if (empty($selectedLanguage) || !isset($locales[$selectedLanguage])) {
            $selectedLanguage = config('app.locale');
        }

        // Gets the dictionaries where to search
        $dictionaries = !empty($selectedDictionary) ? [$selectedDictionary] : array_keys($translationManager->getDictionariesWithLocales());

        // Gets the translations by dictionary
        foreach ($dictionaries as $dictionary) {
            $translationsByDictionary[$dictionary] = $translationManager->getItems($dictionary, $selectedLanguage);
        }

        // Filters the translations by search string
        if (!empty($search)) {
            $translationsByDictionary = $translationManager->searchInTranslationsByDictionary($translationsByDictionary, $search);
        }

        $this->data['translationsByDictionary'] = $translationsByDictionary;
        $this->data['selectedDictionary'] = $selectedDictionary;
        $this->data['selectedLanguage'] = $selectedLanguage;
        $this->data['search'] = $search;
        $this->data['dictionariesByNamespace'] = $dictionariesByNamespace;
        $this->data['locales'] = $locales;

        return view('translation-manager::index', $this->data);
    }

    /**
     * Saves translations
     *
     * @param \Novius\Backpack\Translation\Manager\Http\Requests\Admin\TranslationSaveRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function postIndex(TranslationSaveRequest $request)
    {
        $selectedDictionary = (string) $request->dictionary;
        $selectedLanguage = (string) $request->language;
        $search = (string) $request->search;
        $translationsByDictionary = (array) $request->translations;

        if (!empty($translationsByDictionary)) {
            foreach ($translationsByDictionary as $dictionary => $translations) {
                foreach ($translations as $key => $value) {
                    // Finds/creates the item in DB
                    $translation = LanguageLine::firstOrNew(['key' => $key, 'group' => $dictionary]);
                    $translation->setTranslation($selectedLanguage, (string) $value);
                    $translation->save();
                }
            }
        }

        return Redirect::back()->withInput([
            'language' => $selectedLanguage,
            'dictionary' => $selectedDictionary,
            'search' => $search,
        ]);
    }
}
