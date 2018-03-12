<?php

namespace Novius\Backpack\Translation\Manager\Manager;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Lang;
use Illuminate\Translation\FileLoader;
use Spatie\TranslationLoader\LanguageLine;

class UnifiedTranslationManager
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * The file loader instance.
     *
     * @var \Illuminate\Translation\FileLoader
     */
    protected $nativeFileLoader;

    /**
     * The dictionaries.
     *
     * @var array
     */
    protected $dictionaries;

    /**
     * Creates a new instance of the manager.
     *
     * @param \Illuminate\Contracts\Foundation\Application $application
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     */
    public function __construct(Application $application, Filesystem $filesystem)
    {
        $this->app = $application;
        $this->filesystem = $filesystem;
        $this->loadNativeFileLoader();
        $this->loadDictionaries();
    }

    /**
     * Gets the translation items (instances of LanguageLine)
     *
     * @param string $dictionary
     * @param string $language
     * @return array
     */
    public function getItems($dictionary, $language)
    {
        // Gets the database translations
        $dbTranslations = $this->getFromDB($dictionary);

        // Gets the files translations
        $diskTranslations = $this->getFromFiles($dictionary, $language);

        // Converts sub arrays to dot notation (see https://laravel.com/docs/5.5/validation#localization)
        $diskTranslations = array_dot($diskTranslations);

        // Removes non-string values
        $diskTranslations = array_filter($diskTranslations, 'is_string');

        // Merges disk translations with DB translations
        $translations = array_map(function ($value, $key) use ($dictionary, $language, $dbTranslations) {

            // Returns database translation prior to disk translation if exists
            foreach ($dbTranslations as $dbTranslation) {
                // Key and dictionary must match and a translation must exist for current language
                if ($dbTranslation->key === $key && $dbTranslation->group === $dictionary && isset($dbTranslation->text[$language])) {
                    return $dbTranslation;
                }
            }

            // Creates a new DB item from the disk translation
            $translation = (new LanguageLine(['group' => $dictionary, 'key' => $key]))->setTranslation($language, $value);

            return $translation;
        }, $diskTranslations, array_keys($diskTranslations));

        // Sorts by key to keep symmetry across languages
        $translations = array_sort($translations, function ($translation) {
            return $translation->key;
        });

        return $translations;
    }

    /**
     * Gets the locales from the dictionaries
     *
     * @return array
     */
    public function getLocalesFromDictionaries()
    {
        return array_unique(array_flatten($this->dictionaries));
    }

    /**
     * Gets the available translation locales
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        // Gets the configured locales if defined or from the dictionaries found
        $locales = config('translation-manager.locales') ?? $this->getLocalesFromDictionaries();

        // Converts to key/value with locale as key and display name as value
        $locales = array_map(function ($locale) {
            $name = \Locale::getDisplayName($locale);
            $name = mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);

            return $name;
        }, array_combine($locales, $locales));

        asort($locales);

        return $locales;
    }

    /**
     * Gets the available dictionaries
     *
     * @return array
     */
    public function getDictionaries()
    {
        $dictionaries = array_keys($this->dictionaries);
        $dictionaries = array_unique($dictionaries);
        sort($dictionaries);

        return $dictionaries;
    }

    /**
     * Gets the available dictionaries grouped by namespace
     *
     * @return array
     */
    public function getDictionariesByNamespace()
    {
        // Dictionaries by namespace
        $dictionaries = [];
        foreach ($this->dictionaries as $dictionary => $locales) {
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
    public function getDictionariesWithLocales()
    {
        return $this->dictionaries;
    }

    /**
     * Gets the translations from DB by dictionary
     *
     * @param string $dictionary
     * @return \Illuminate\Support\Collection
     */
    public function getFromDB($dictionary)
    {
        $items = LanguageLine::where('group', $dictionary)->orderBy('key')->get();

        return $items;
    }

    /**
     * Gets the translations from files by dictionary and language
     *
     * @param $dictionary
     * @param $language
     * @return array
     */
    public function getFromFiles($dictionary, $language)
    {
        list($namespace, $group) = Lang::parseKey($dictionary);

        // Uses the native file loader to prevent loading from DB (see spatie/laravel-translation-loader)
        $translations = $this->nativeFileLoader->load($language, $group, $namespace);

        // Gets available keys in other languages, so we will have a symmetric list of translations in all languages
        $availableLocales = array_get($this->dictionaries, $dictionary);
        if (!empty($availableLocales)) {
            foreach ($availableLocales as $locale) {
                $localeTranslations = $this->nativeFileLoader->load($locale, $group, $namespace);
                foreach ($localeTranslations as $key => $value) {
                    if (!isset($translations[$key])) {
                        $translations[$key] = '';
                    }
                }
            }
        }

        asort($translations);

        return $translations;
    }

    /**
     * Searches a phrase in translations by dictionary
     *
     * @param array $translationsByDictionary
     * @param string $searchString
     * @return array
     */
    public function searchInTranslationsByDictionary($translationsByDictionary, $searchString)
    {
        // Extracts the words from the search string
        $searchWords = $this->extractWordsForSearch($searchString);

        // Filters each dictionary by search string
        return collect($translationsByDictionary)
            ->map(function ($translations, $dictionary) use ($searchWords) {
                return collect($translations)->filter(function ($translation) use ($dictionary, $searchWords) {
                    // Builds a searchable string from the dictionary, key and translation
                    $string = $dictionary.' '.$translation->key.' '.implode(' ', $translation->text);
                    $string = implode(' ', $this->extractWordsForSearch($string));

                    // Checks if each word to search is found partially
                    foreach ($searchWords as $searchWord) {
                        if (mb_strpos($string, $searchWord) === false) {
                            return false;
                        }
                    }

                    return true;
                })->all();
            })
            ->filter()
            ->all();
    }

    /**
     * Extracts the words from the search string
     *
     * @param string $searchString
     * @return array
     */
    public function extractWordsForSearch($searchString)
    {
        // Removes extra spaces, diacritics, and forces lowercase
        $searchString = trim($searchString);
        $searchString = strtolower($searchString);
        $searchString = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $searchString);

        // Splits the search string into words
        $searchWords = array_filter(preg_split('`[^a-z0-9\p{Latin}]+`ui', $searchString));

        return $searchWords;
    }

    /**
     * Loads the native file loader
     *
     * @return $this
     */
    protected function loadNativeFileLoader()
    {
        // Gets the current loader
        $fileLoader = Lang::getLoader();

        // Instantiate the native loader
        $this->nativeFileLoader = new FileLoader($this->app['files'], $this->app['path.lang']);

        // Copies the hints from the current loader into the native loader
        foreach ($fileLoader->namespaces() as $namespace => $hint) {
            $this->nativeFileLoader->addNamespace($namespace, $hint);
        }

        return $this;
    }

    /**
     * Loads the dictionaries from disk
     *
     * @return $this
     */
    protected function loadDictionaries()
    {
        // Gets the lang namespaces
        $namespaces = Lang::getLoader()->namespaces();

        // Searches for lang files in lang directories
        $directories = array_merge([$this->app['path.lang']], array_values($namespaces));
        foreach ($directories as $directory) {
            foreach ($this->filesystem->directories($directory) as $langPath) {
                foreach ($this->filesystem->allfiles($langPath) as $file) {
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

        uksort($this->dictionaries, 'strnatcmp');

        return $this;
    }
}
