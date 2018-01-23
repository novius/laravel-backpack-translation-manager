<?php

namespace Novius\Backpack\Translation\Manager\Http\Controllers\Admin;

use Backpack\Base\app\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Translation\FileLoader;
use Novius\Backpack\Translation\Manager\Http\Requests\TranslationRequest;
use Spatie\TranslationLoader\LanguageLine;

class TranslationController extends Controller
{
    protected $data = []; // the information we send to the view

    protected $dictionaries;

    protected $nativeFileLoader;

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request)
    {

        $dictionary = $request->dictionary;
        $language = $request->language;

        // Gets translations by dictionary and language
        if ($dictionary && $language) {
            $this->data['translations'] = $this->getTranslationsItems($dictionary, $language);
        }

        $this->data['selectedDictionary'] = $dictionary;
        $this->data['selectedLanguage'] = $language;
        $this->data['dictionariesByNamespace'] = $this->getTranslationDictionariesByNamespace();
        $this->data['locales'] = $this->getTranslationLocales();

        return view('translation-manager::index', $this->data);
    }

    /**
     * @param $dictionary
     * @param $language
     * @return array
     */
    protected function getTranslationsItems($dictionary, $language)
    {
        // Loads translations from database
        $dbTranslations = $this->getTranslationItemsFromDB($dictionary, $language);

        // Loads translations from files
        $diskTranslations = $this->getTranslationsFromFiles($dictionary, $language);

        // Converts sub arrays to dot notation (see https://laravel.com/docs/4.2/validation#localization)
        $diskTranslations = array_dot($diskTranslations);

        // Removes non-string values
        $diskTranslations = array_filter($diskTranslations, 'is_string');

        // Merges disk translations with DB translations
        $translations = array_map(function($value, $key) use ($dictionary, $language, $dbTranslations) {

            // Returns database translation prior to disk translation if exists
            foreach ($dbTranslations as $dbTranslation) {
                // Key and dictionary must match and a translation must exist for current language
                if ($dbTranslation->key === $key && $dbTranslation->group === $dictionary && isset($dbTranslation->text[$language])) {
                    return $dbTranslation;
                }
            }

            // Creates a new item
            $translation = (new LanguageLine(['group' => $dictionary, 'key' => $key]))->setTranslation($language, $value);

            return $translation;

        }, $diskTranslations, array_keys($diskTranslations));

        // Sorts by key to keep symmetry across languages
        $translations = array_sort($translations, function($translation) {
            return $translation->key;
        });

        return $translations;
    }

    /**
     * Saves translations
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function postIndex(Request $request)
    {
        $translations = $request->translations;
        $dictionary = $request->dictionary;
        $language = $request->language;

        // @todo faire les validations proprement (cf. rules())
        if (empty($dictionary)) {
            throw new \Exception('Dictionary missing');
        }
        if (empty($language)) {
            throw new \Exception('Language missing');
        }

        if (!empty($translations)) {

//            // Converts dot notation to sub arrays (see https://laravel.com/docs/4.2/validation#localization)
//            $translations = array_reduce(array_keys($translations), function($result, $key) use ($translations) {
//                array_set($result, $key, $translations[$key]);
//                return $result;
//            }, []);

            foreach ($translations as $key => $value) {
                if (is_null($value)) {
                    continue;
                }

                // Finds/creates the item in DB
                $translation = LanguageLine::firstOrNew(['key' => $key, 'group' => $dictionary]);
                $translation->setTranslation($language, $value);
                $translation->save();
            }
        }

        return Redirect::back()->withInput([
            'dictionary' => $dictionary,
            'language' => $language,
        ]);
    }

    protected function getTranslationLocales()
    {
        $dictionaries = $this->getTranslationDictionariesWithLocales();

        $locales = array_flatten($dictionaries);
        $locales = array_unique($locales);

        // Converts to key/value with locale as key and display name as value
        $locales = array_map(function($locale) {
            $name = \Locale::getDisplayName($locale);
            $name = mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);

            return $name;
        }, array_combine($locales, $locales));

        asort($locales);

        return $locales;
    }

    /**
     * Gets the available translation dictionaries
     *
     * @return array
     */
    protected function getTranslationDictionaries()
    {
        $dictionariesLocales = $this->getTranslationDictionariesWithLocales();

        $dictionaries = array_keys($dictionariesLocales);
        $dictionaries = array_unique($dictionaries);
        sort($dictionaries);

        return $dictionaries;
    }

    /**
     * Gets the available translation dictionaries
     *
     * @return array
     */
    protected function getTranslationDictionariesByNamespace()
    {
        $dictionariesLocales = $this->getTranslationDictionariesWithLocales();

        // Dictionaries by namespace
        $dictionaries = [];
        foreach ($dictionariesLocales as $dictionary => $locales) {
            list($namespace, $group) = Lang::parseKey($dictionary);
            $dictionaries[$namespace][$dictionary] = $group;
        }

        return $dictionaries;
    }

    /**
     * Gets the available translation dictionaries with their available locales
     *
     * @return array
     */
    protected function getTranslationDictionariesWithLocales()
    {
        if (is_null($this->dictionaries)) {
            $app = resolve('app');
            $files = resolve('files');

            $namespaces = Lang::getLoader()->namespaces();

            // resources/lang/                                                      fr/file.php
            // resources/lang/                                                      fr/dir/file.php
            // resources/lang/                                                      vendor/owner/package/fr/file.php
            // resources/lang/                                                      vendor/owner/package/fr/dir/file.php
            // vendor/novius/laravel-backpack-translation-manager/resources/lang/   fr/file.php
            // vendor/novius/laravel-backpack-translation-manager/resources/lang/   fr/dir/file.php

            // Searches for lang files in lang directories
            $directories = array_merge(array($app['path.lang']), array_values($namespaces));
            foreach ($directories as $directory) {
                foreach ($files->directories($directory) as $langPath) {

                    foreach ($files->allfiles($langPath) as $file) {
                        $info = pathinfo($file);

                        // Extracts the sub path
                        $subLangPath = trim(str_replace($langPath, '', $info['dirname']), DIRECTORY_SEPARATOR);

                        // Extracts the namespace and locale from path
                        $namespace = array_search($directory, $namespaces);
                        if (!empty($subLangPath) && $namespace === false) {
                            list($namespace, $locale, $subLangPath) = array_pad(explode(DIRECTORY_SEPARATOR, $subLangPath, 3), 3, '');
                        } else {
                            $locale = basename($langPath);
                        }

                        // Builds the dictionary path including namespace and group
                        $dictionary = $info['filename'];
                        if (!empty($subLangPath)) {
                            $dictionary = $subLangPath.DIRECTORY_SEPARATOR.$dictionary;
                        }
                        if (!empty($namespace)) {
                            $dictionary = $namespace.'::'.$dictionary;
                        }

                        // Normalizes (eg. fr-FR => fr_FR)
                        $locale = \Locale::canonicalize($locale);

                        $this->dictionaries[$dictionary][] = $locale;
                    }
                }
            }

            // De-duplicates dictionaries locales
            $this->dictionaries = array_map('array_unique', $this->dictionaries);

            uksort($this->dictionaries, "strnatcmp");
        }

        return $this->dictionaries;
    }

    /**
     * Gets the translation items from DB
     *
     * @param $dictionary
     * @return \Illuminate\Support\Collection
     */
    protected function getTranslationItemsFromDB($dictionary)
    {
        $items = LanguageLine::where('group', $dictionary)->orderBy('key')->get();

        return $items;
    }

    /**
     * Gets the translations from files
     *
     * @param $dictionary
     * @param $language
     * @return array
     */
    protected function getTranslationsFromFiles($dictionary, $language)
    {
        // Gets the native loader to prevent loading from DB (see spatie/laravel-translation-loader)
        $fileLoader = $this->getTranslationLoader();

        list($namespace, $group) = Lang::parseKey($dictionary);

        $translations = $fileLoader->load($language, $group, $namespace);

        // Gets available keys in other languages, so we will have a symmetric list of translations in all languages
        $availableLocales = array_get($this->getTranslationDictionariesWithLocales(), $dictionary);
        foreach ($availableLocales as $locale) {
            $localeTranslations = $fileLoader->load($locale, $group, $namespace);
            foreach ($localeTranslations as $key => $value) {
                if (!isset($translations[$key])) {
                    $translations[$key] = '';
                }
            }
        }

        asort($translations);

        return $translations;
    }

    /**
     * Gets the native translation loader (used to get translations from files without those from DB)
     *
     * @return FileLoader
     */
    protected function getTranslationLoader()
    {
        if (is_null($this->nativeFileLoader)) {
            $app = resolve('app');

            // Gets the current loader
            $fileLoader = Lang::getLoader();

            // Instantiate the native loader
            $this->nativeFileLoader = new FileLoader($app['files'], $app['path.lang']);

            // Copies the hints from the current loader into the native loader
            foreach ($fileLoader->namespaces() as $namespace => $hint) {
                $this->nativeFileLoader->addNamespace($namespace, $hint);
            }
        }

        return $this->nativeFileLoader;
    }
}
