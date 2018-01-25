@extends('backpack::layout')

@section('header')
    <section class="content-header">
      <h1>
        {{ trans('translation-manager::crud.title') }}
          <small>{{ trans('translation-manager::crud.subtitle') }}</small>
      </h1>
      <ol class="breadcrumb">
          <li>
              <a href="{{ url(config('backpack.base.route_prefix'), 'dashboard') }}">{{ trans('backpack::crud.admin') }}</a>
          </li>
          <li>
              <a href="{{ url(config('backpack.base.route_prefix').'/'.config('translation-manager.route_prefix')) }}">
                  {{ trans('translation-manager::crud.title') }}
              </a>
          </li>
          <li class="active">
              {{ trans('translation-manager::crud.breadcrumb_list') }}
          </li>
      </ol>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    {{ Form::open(['method' => 'GET']) }}
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>
                                    {{ trans('translation-manager::crud.select_dictionary_label') }}
                                </label>
                                <select class="form-control" id="select_dictionary_by_namespace" name="dictionary" label="Dictionary" type="select" value="">
                                    <option value="">
                                        {{ trans('translation-manager::crud.select_dictionary_default_option') }}
                                    </option>
                                    @foreach ($dictionariesByNamespace as $namespace => $dictionaries)
                                        @
                                        @if (empty($namespace) || $namespace === '*')
                                            @foreach ($dictionaries as $dictionary => $dictionaryName)
                                                <option value="{{ $dictionary }}" {!! $dictionary === $selectedDictionary ? 'selected="selected"' : '' !!}>
                                                    {{ $dictionaryName }}
                                                </option>
                                            @endforeach
                                        @else
                                            <optgroup label="{{ $namespace }}">
                                                @foreach ($dictionaries as $dictionary => $dictionaryName)
                                                    <option value="{{ $dictionary }}" {!! $dictionary === $selectedDictionary ? 'selected="selected"' : '' !!}>
                                                        {{ $dictionaryName }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <!-- load the view from the application if it exists, otherwise load the one in the package -->
                            <!-- text input -->
                            <div class="form-group col-md-6">
                                <label>
                                    {{ trans('translation-manager::crud.select_language_label') }}
                                </label>
                                <select class="form-control" id="select_language" name="language" label="Language" type="select" value="">
                                    <option value="">
                                        {{ trans('translation-manager::crud.select_language_default_option') }}
                                    </option>
                                    @foreach ($locales as $locale => $name)
                                        <option value="{{ $locale }}" {!! $locale === $selectedLanguage? 'selected="selected"' : '' !!}>
                                            {{ $name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    {{ trans('translation-manager::crud.button_search') }}
                                </button>
                            </div>
                        </div>
                    {{ Form::close() }}
                </div>
                {{ Form::open() }}
                    <input type="hidden" name="dictionary" value="{{ $selectedDictionary }}"/>
                    <input type="hidden" name="language" value="{{ $selectedLanguage }}"/>
                    <div class="box-body">
                        <table id="crudTable" class="table table-bordered table-striped display dataTable" role="grid" aria-describedby="crudTable_info">
                            <thead>
                                <tr role="row">
                                    <th>
                                        {{ trans('translation-manager::crud.column_key') }}
                                    </th>
                                    <th>
                                        {{ trans('translation-manager::crud.column_translation') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            @if (isset($translations))
                                @if (!empty($translations))
                                    @foreach ($translations as $translation)
                                    <tr class="odd">
                                        <td valign="top" class="">
                                            {{ $translation->key }}
                                        </td>
                                        <td valign="top" class="">
                                            <input type="text"
                                                   name="translations[{{ $translation->key }}]"
                                                   value="{{ $translation->getTranslation($selectedLanguage) }}"
                                                   class="form-control" />
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr class="odd">
                                        <td valign="top" colspan="3" class="dataTables_empty">
                                            {{ trans('translation-manager::crud.list_no_translations') }}
                                        </td>
                                    </tr>
                                @endif
                            @else
                                <tr class="odd">
                                    <td valign="top" colspan="2" class="dataTables_empty">
                                        {{ trans('translation-manager::crud.list_require_select') }}
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>
                                        {{ trans('translation-manager::crud.column_key') }}
                                    </th>
                                    <th>
                                        {{ trans('translation-manager::crud.column_translation') }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if (isset($translations))
                        <div class="box-footer">
                            <button type="submit" class="btn btn-success">
                                <span class="fa fa-save" role="presentation" aria-hidden="true"></span> &nbsp;
                                <span data-value="save_and_back">
                                    {{ trans('translation-manager::crud.button_save') }}
                                </span>
                            </button>
                        </div>
                    @endif
                {{ Form::close() }}
            </div>
        </div>
    </div>
@endsection
