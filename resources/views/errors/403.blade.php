@extends('errors::minimal')

@section('title', __('Dostęp zabroniony'))
@section('code', '403')
@section('message', __($exception->getMessage() ?: 'Nie masz uprawnień, żeby tu zajrzeć.'))
